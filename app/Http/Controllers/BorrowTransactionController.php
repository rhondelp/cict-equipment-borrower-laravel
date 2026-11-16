<?php

namespace App\Http\Controllers;

use App\Mail\ReturnNotification;
use App\Models\ActivityLog;
use App\Models\BorrowTransaction;
use App\Models\ClassSchedule;
use App\Models\Equipment;
use App\Models\Notification;
use App\Models\ReturnLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class BorrowTransactionController extends Controller
{
    public function index()
    {
        $transactions = BorrowTransaction::with(['user', 'equipment', 'classSchedule.instructor', 'returnLog', 'reminders'])
            ->orderByDesc('borrow_date')
            ->orderByDesc('id')
            ->get();

        $transactions = $transactions->sortBy([
            fn ($a, $b) => $this->queueRank($a) <=> $this->queueRank($b),
            fn ($a, $b) => ($a->dueAt()?->timestamp ?? PHP_INT_MAX) <=> ($b->dueAt()?->timestamp ?? PHP_INT_MAX),
        ])->values();

        $users = User::whereNull('deactivated_at')->orderBy('name')->get();
        $equipment = Equipment::lendable()->orderBy('equipment_name')->get();
        $classSchedules = ClassSchedule::with('instructor')
            ->whereHas('instructor', function ($query) {
                $query->where('user_type', 'Instructor');
            })
            ->get();

        return view('admin.transaction', compact('transactions', 'users', 'equipment', 'classSchedules'));
    }

    private function queueRank(BorrowTransaction $transaction): int
    {
        if ($transaction->isVoided()) {
            return 4;
        }

        if ($transaction->isReturned() || $transaction->isIssued()) {
            return 3;
        }

        if ($transaction->isOverdue()) {
            return 0;
        }

        $days = $transaction->daysUntilDue();

        return ($days !== null && $days <= 1) ? 1 : 2;
    }

    public function receipt($id)
    {
        $transaction = BorrowTransaction::with(['user', 'equipment'])->findOrFail($id);

        if ($transaction->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $bookingLoans = $transaction->bookingLoans();

        return view('borrower.receipt', compact('transaction', 'bookingLoans'));
    }

    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:borrow_transactions,id',
            'condition' => 'required|in:'.implode(',', array_merge(ReturnLog::CONDITIONS, ReturnLog::LEGACY_CONDITIONS)),
            'remarks' => 'required_unless:condition,Good|nullable|string|max:255',
        ], [
            'remarks.required_unless' => 'Describe the problem so the return log says what is wrong.',
        ]);

        $lateLabel = null;

        try {
            $transaction = DB::transaction(function () use ($validated, &$lateLabel) {
                $transaction = BorrowTransaction::where('id', $validated['id'])->lockForUpdate()->firstOrFail();

                if ($transaction->isVoided()) {
                    throw ValidationException::withMessages(['id' => 'This borrow was voided and cannot be checked in.']);
                }
                if ($transaction->isReturned()) {
                    throw ValidationException::withMessages(['id' => 'This borrow is already checked in.']);
                }
                if ($transaction->isIssued()) {
                    throw ValidationException::withMessages([
                        'id' => 'This item was issued, not lent, so nothing is due back to check in. Void the record if it was entered by mistake.',
                    ]);
                }

                $lateLabel = $transaction->isOverdue() ? $transaction->timingLabel() : null;
                $statusBefore = ActivityLog::loanStatus($transaction);

                $equipment = Equipment::where('id', $transaction->equipment_id)->lockForUpdate()->firstOrFail();
                $equipment->releaseStock($transaction->quantity, $transaction);

                $returnLog = ReturnLog::create([
                    'borrow_transaction_id' => $transaction->id,
                    'return_date' => now(),
                    'condition' => $validated['condition'],
                    'remarks' => ($validated['remarks'] ?? null) ?: null,
                    'user_id' => auth()->id(),
                ]);

                $transaction->status = 'Returned';
                $transaction->save();

                ActivityLog::record('loan_checked_in', [
                    'loan' => $transaction,
                    'quantity' => $transaction->quantity,
                    'status_from' => $statusBefore,
                    'status_to' => 'Returned',
                    'details' => 'Checked in '.ActivityLog::units($transaction).' from '.($transaction->user->name ?? 'a deleted account')
                        .' in '.$validated['condition'].' condition'.($lateLabel ? ', '.$lateLabel : '').'.',
                    'meta' => array_filter([
                        'condition' => $validated['condition'],
                        'remarks' => $returnLog->remarks,
                        'return_log_id' => $returnLog->id,
                        'late' => $lateLabel,
                    ]),
                ]);

                return $transaction;
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        $transaction->loadMissing('equipment');
        $late = $lateLabel ? ' Logged as '.$lateLabel.'.' : '';

        return redirect()->back()->with('success', 'Checked in — '.$transaction->quantity.' × '
            .($transaction->equipment->equipment_name ?? 'item').' back on the shelf.'.$late);
    }

    public function store(Request $request)
    {
        if ($request->has('equipment')) {
            $filteredEquip = array_values(array_filter((array) $request->input('equipment'), fn ($v) => $v !== '' && $v !== null));
            $request->merge(['equipment' => $filteredEquip]);
        }
        if ($request->has('quantities')) {
            $filteredQty = array_filter((array) $request->input('quantities'), fn ($v, $k) => $k !== '' && $v !== '' && $v !== null, ARRAY_FILTER_USE_BOTH);
            $request->merge(['quantities' => $filteredQty]);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'equipment' => 'required|array|min:1',
            'equipment.*' => 'exists:equipment,id',
            'quantities' => 'required|array|min:1',
            'quantities.*' => 'integer|min:1',
            'borrow_date' => 'required|date',
            'return_date' => 'nullable|date',
            'purpose' => 'required|string|max:255',
            'remarks' => 'nullable|string',
            'class_schedule_id' => 'nullable|exists:class_schedules,id',
        ]);

        try {
            $result = DB::transaction(function () use ($validated) {
                $items = Equipment::whereIn('id', array_unique($validated['equipment']))
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

                [$borrowAt, $dueAt] = $this->handoverMoments($items, $validated['borrow_date'], $validated['return_date'] ?? null);

                $tally = ['out' => 0, 'issued' => 0, 'timed' => false];

                foreach ($validated['equipment'] as $equipmentId) {
                    if (! isset($validated['quantities'][$equipmentId])) {
                        throw ValidationException::withMessages(['quantity' => "Quantity missing for equipment #{$equipmentId}."]);
                    }
                    $quantity = (int) $validated['quantities'][$equipmentId];

                    $equipment = $items->get($equipmentId)
                        ?? throw ValidationException::withMessages(['equipment' => "Equipment #{$equipmentId} no longer exists."]);

                    if ($equipment->isRetired()) {
                        throw ValidationException::withMessages([
                            'quantity' => $equipment->equipment_name.' has been retired and can no longer be lent out.',
                        ]);
                    }

                    $issued = $equipment->isNonReturnable();
                    $timed = $equipment->isTimeLimited();

                    $loan = BorrowTransaction::create([
                        'user_id' => $validated['user_id'],
                        'equipment_id' => $equipmentId,
                        'borrow_date' => $timed ? $borrowAt : $borrowAt->copy()->startOfDay(),
                        'return_date' => $issued ? null : ($timed ? $dueAt : $dueAt->copy()->startOfDay()),
                        'timed' => $timed,
                        'quantity' => $quantity,
                        'purpose' => $validated['purpose'],
                        'status' => $issued ? 'Issued' : 'Borrowed',
                        'remarks' => $validated['remarks'] ?? null,
                        'class_schedule_id' => $validated['class_schedule_id'] ?? null,
                    ]);
                    $loan->setRelation('equipment', $equipment);

                    $equipment->reserveStock(
                        $quantity,
                        "Only {$equipment->available_quantity} of {$equipment->equipment_name} available — this borrow needs {$quantity}.",
                        $loan
                    );

                    ActivityLog::recordHandover($loan);

                    $tally[$issued ? 'issued' : 'out'] += $quantity;
                    $tally['timed'] = $tally['timed'] || $timed;
                }

                return $tally + ['due' => $dueAt];
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $parts = [];
        if ($result['out'] > 0) {
            $due = $result['timed']
                ? $result['due']->format($result['due']->isToday() ? 'g:i A' : 'M j, g:i A')
                : $result['due']->format('M j');
            $parts[] = $result['out'].' '.str('unit')->plural($result['out']).' out, due back '.$due;
        }
        if ($result['issued'] > 0) {
            $parts[] = $result['issued'].' '.str('unit')->plural($result['issued']).' issued, not expected back';
        }

        return redirect()->back()->with('success', ($result['out'] > 0 ? 'Borrow' : 'Issue').' recorded — '.implode('; ', $parts).'.');
    }

    private function handoverMoments(Collection $items, string $borrowInput, ?string $returnInput): array
    {
        $needsReturn = $items->reject(fn (Equipment $item) => $item->isNonReturnable());
        $timedItems = $items->filter(fn (Equipment $item) => $item->isTimeLimited());
        $names = fn (Collection $set) => $set->pluck('equipment_name')->join(', ', ' and ');

        if ($timedItems->isNotEmpty() && ! $this->hasTime($borrowInput)) {
            throw ValidationException::withMessages([
                'borrow_date' => 'Time-limited items need the time they are taken out, not just the date ('.$names($timedItems).').',
            ]);
        }

        $borrowAt = Carbon::parse($borrowInput);

        if ($needsReturn->isEmpty()) {
            return [$borrowAt, null];
        }

        if (blank($returnInput)) {
            throw ValidationException::withMessages([
                'return_date' => 'Set when '.$names($needsReturn).' '.($needsReturn->count() === 1 ? 'is' : 'are')
                    .' due back. Only non-returnable items go out without a due date.',
            ]);
        }

        $dueAt = Carbon::parse($returnInput);

        if ($timedItems->isNotEmpty()) {
            if (! $this->hasTime($returnInput)) {
                throw ValidationException::withMessages([
                    'return_date' => 'Time-limited items are due back at a time, not just a date ('.$names($timedItems).'). Add the time.',
                ]);
            }
            if (! $dueAt->gt($borrowAt)) {
                throw ValidationException::withMessages([
                    'return_date' => 'The due time must be after the moment it is taken out.',
                ]);
            }
        }

        if ($dueAt->copy()->startOfDay()->lt($borrowAt->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'return_date' => 'The due date must fall on or after the day it is taken out.',
            ]);
        }

        return [$borrowAt, $dueAt];
    }

    private function hasTime(?string $value): bool
    {
        return (bool) preg_match('/\d{1,2}:\d{2}/', (string) $value);
    }

    public function sendManualEmail(Request $request, $id)
    {
        $transaction = BorrowTransaction::with(['user', 'equipment'])->findOrFail($id);

        if (! $transaction->user || ! $transaction->user->email) {
            return response()->json(['message' => 'User has no email address.'], 400);
        }

        $type = $request->input('type');
        $message = $request->input('message');

        if ($type === 'custom' && empty(trim((string) $message))) {
            return response()->json(['message' => 'Custom message cannot be empty.'], 422);
        }

        if ($type !== 'custom' && $transaction->isIssued()) {
            return response()->json(['message' => 'This item was issued, not lent — there is nothing to remind about.'], 422);
        }

        $details = $type === 'custom'
            ? ['title' => 'Message from Admin', 'body' => $message]
            : ['title' => $transaction->isOverdue() ? 'Overdue notice' : 'Return Reminder', 'body' => $this->reminderBody($transaction)];

        try {
            Mail::to($transaction->user->email)->send(new ReturnNotification($details));
        } catch (\Exception $e) {
            \Log::error('Manual email failed for transaction '.$id.': '.$e->getMessage());

            return response()->json(['message' => 'Failed to send email: '.$e->getMessage()], 500);
        }

        $this->safe(
            fn () => DB::transaction(function () use ($transaction, $details) {
                Notification::create([
                    'user_id' => $transaction->user->id,
                    'borrow_transaction_id' => $transaction->id,
                    'message' => $details['body'],
                    'notification_type' => $details['title'],
                    'send_date' => Carbon::now(),
                ]);

                ActivityLog::record('loan_reminder_sent', [
                    'loan' => $transaction,
                    'quantity' => $transaction->quantity,
                    'details' => $details['title'].' sent to '.$transaction->user->name.' about '.ActivityLog::units($transaction).'.',
                    'meta' => ['notification_type' => $details['title'], 'message' => $details['body']],
                ]);
            }),
            'Reminder log failed for transaction '.$id
        );

        return response()->json([
            'message' => 'Email sent to '.$transaction->user->email.'.',
            'sent_at' => Carbon::now()->format('M j, g:i A'),
        ]);
    }

    public function sendReturnAlertNotification()
    {
        $today = Carbon::today()->toDateString();

        DB::transaction(function () {
            $due = BorrowTransaction::with(['user', 'equipment'])
                ->where('status', 'Borrowed')
                ->overdue()
                ->lockForUpdate()
                ->get();

            foreach ($due as $loan) {
                $flipped = BorrowTransaction::whereKey($loan->id)->where('status', 'Borrowed')
                    ->update(['status' => 'Overdue']);

                if ($flipped) {
                    ActivityLog::record('loan_marked_overdue', [
                        'actor' => null,
                        'loan' => $loan,
                        'quantity' => $loan->quantity,
                        'status_from' => 'Borrowed',
                        'status_to' => 'Overdue',
                        'details' => ActivityLog::units($loan).' with '.($loan->user->name ?? 'a deleted account')
                            .' marked overdue — it was '.ActivityLog::dueWords($loan).'.',
                    ]);
                }
            }
        });

        $transactions = BorrowTransaction::with(['user', 'equipment'])
            ->out()
            ->whereDate('return_date', $today)
            ->where('status', 'Borrowed')
            ->get();

        $sent = 0;

        foreach ($transactions as $transaction) {
            if ($transaction->user && $transaction->user->email) {
                $details = [
                    'title' => 'Return Reminder',
                    'body' => $this->reminderBody($transaction),
                ];

                $alreadyNotified = Notification::where('user_id', $transaction->user->id)
                    ->where('notification_type', 'Return Notice')
                    ->whereDate('send_date', $today)
                    ->exists();

                if ($alreadyNotified) {
                    continue;
                }

                if (! $this->safe(
                    fn () => Mail::to($transaction->user->email)->send(new ReturnNotification($details)),
                    'Return notification mail failed for transaction '.$transaction->id
                )) {
                    continue;
                }

                $this->safe(
                    fn () => DB::transaction(function () use ($transaction, $details) {
                        $exists = Notification::where('user_id', $transaction->user->id)
                            ->where('notification_type', 'Return Notice')
                            ->whereDate('send_date', Carbon::today()->toDateString())
                            ->exists();
                        if (! $exists) {
                            Notification::create([
                                'user_id' => $transaction->user->id,
                                'message' => $details['body'],
                                'notification_type' => 'Return Notice',
                                'send_date' => Carbon::now(),
                            ]);
                        }

                        ActivityLog::record('loan_reminder_sent', [
                            'actor' => null,
                            'loan' => $transaction,
                            'quantity' => $transaction->quantity,
                            'details' => 'Return Notice sent to '.$transaction->user->name.' about '.ActivityLog::units($transaction).'.',
                            'meta' => ['notification_type' => 'Return Notice', 'message' => $details['body']],
                        ]);
                    }),
                    'Notification DB log failed for transaction '.$transaction->id
                );

                $sent++;
            }
        }

        return $sent.' return notifications sent and logged for today.';
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:borrow_transactions,id',
            'user_id' => 'required|exists:users,id',
            'equipment_id' => 'required|exists:equipment,id',
            'borrow_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:borrow_date',
            'quantity' => 'required|integer|min:1',
            'purpose' => 'required|string|max:255',
            'remarks' => 'nullable|string',
            'class_schedule_id' => 'nullable|exists:class_schedules,id',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $transaction = BorrowTransaction::where('id', $validated['id'])->lockForUpdate()->firstOrFail();

                if ($transaction->isVoided() || $transaction->isReturned()) {
                    throw ValidationException::withMessages([
                        'id' => 'This borrow is closed. Closed borrows are the return history and cannot be edited.',
                    ]);
                }

                if ($transaction->isIssued()) {
                    throw ValidationException::withMessages([
                        'id' => 'Issued items cannot be edited. Void the record if it was entered by mistake, then record it again.',
                    ]);
                }

                [$borrowAt, $dueAt] = $this->editedMoments($transaction->timed, $validated['borrow_date'], $validated['return_date']);

                $oldEquipmentId = $transaction->equipment_id;
                $newEquipmentId = $validated['equipment_id'];
                $oldQty = $transaction->quantity;
                $newQty = $validated['quantity'];

                $before = $this->loanSnapshot($transaction);
                $statusBefore = ActivityLog::loanStatus($transaction);

                if ($oldEquipmentId != $newEquipmentId) {
                    $ids = collect([$oldEquipmentId, $newEquipmentId])->sort()->values();
                    $locked = Equipment::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
                    $oldEquipment = $locked[$oldEquipmentId];
                    $equipment = $locked[$newEquipmentId];

                    $wanted = $transaction->timed ? Equipment::LOAN_TIME_LIMITED : Equipment::LOAN_RETURNABLE;
                    if ($equipment->loan_type !== $wanted) {
                        throw ValidationException::withMessages([
                            'equipment_id' => $equipment->equipment_name.' is '.$equipment->loanTypeLabel().', and this borrow is '
                                .Equipment::LOAN_TYPES[$wanted].'. Pick another '.Equipment::LOAN_TYPES[$wanted]
                                .' item, or void this borrow and record a new one.',
                        ]);
                    }

                    $oldEquipment->releaseStock($oldQty, $transaction);
                    $equipment->reserveStock(
                        $newQty,
                        "Only {$equipment->available_quantity} of {$equipment->equipment_name} available — this borrow needs {$newQty}.",
                        $transaction
                    );
                } else {
                    $equipment = Equipment::where('id', $oldEquipmentId)->lockForUpdate()->firstOrFail();

                    $diff = $newQty - $oldQty;
                    if ($diff > 0) {
                        $equipment->reserveStock(
                            $diff,
                            "Only {$equipment->available_quantity} more of {$equipment->equipment_name} available.",
                            $transaction
                        );
                    } else {
                        $equipment->releaseStock(-$diff, $transaction);
                    }
                }

                $transaction->update(['borrow_date' => $borrowAt, 'return_date' => $dueAt] + $validated);
                $transaction->load(['user', 'equipment', 'classSchedule']);

                $changes = ActivityLog::changes($before, $this->loanSnapshot($transaction));
                if ($changes !== []) {
                    ActivityLog::record('loan_updated', [
                        'loan' => $transaction,
                        'quantity' => $transaction->quantity,
                        'status_from' => $statusBefore,
                        'status_to' => ActivityLog::loanStatus($transaction),
                        'details' => 'Edited borrow #'.$transaction->id.': '.ActivityLog::changeWords($changes, [
                            'borrower' => 'borrower', 'equipment' => 'item', 'borrow_date' => 'borrow date',
                            'return_date' => 'due date', 'class_schedule' => 'class',
                        ]).'.',
                        'meta' => ['changes' => $changes],
                    ]);
                }
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        return redirect()->back()->with('success', 'Borrow updated.');
    }

    private function loanSnapshot(BorrowTransaction $loan): array
    {
        $format = $loan->timed ? 'M j, Y g:i A' : 'M j, Y';

        return [
            'borrower' => $loan->user?->name,
            'equipment' => $loan->equipment?->equipment_name,
            'quantity' => $loan->quantity,
            'borrow_date' => $loan->borrow_date?->format($format),
            'return_date' => $loan->return_date?->format($format),
            'purpose' => $loan->purpose,
            'remarks' => $loan->remarks,
            'class_schedule' => $loan->classSchedule?->subject_code,
        ];
    }

    private function editedMoments(bool $timed, string $borrowInput, string $returnInput): array
    {
        $borrowAt = Carbon::parse($borrowInput);
        $dueAt = Carbon::parse($returnInput);

        if (! $timed) {
            return [$borrowAt->startOfDay(), $dueAt->startOfDay()];
        }

        if (! $this->hasTime($borrowInput) || ! $this->hasTime($returnInput)) {
            throw ValidationException::withMessages([
                'return_date' => 'This is a time-limited borrow. Give the time it went out and the time it is due back.',
            ]);
        }

        if (! $dueAt->gt($borrowAt)) {
            throw ValidationException::withMessages([
                'return_date' => 'The due time must be after the moment it was taken out.',
            ]);
        }

        return [$borrowAt, $dueAt];
    }

    public function void(Request $request, $id)
    {
        $validated = $request->validate([
            'void_reason' => 'required|string|min:5|max:500',
        ], [
            'void_reason.required' => 'Say why this record is being voided — it stays in the log.',
        ]);

        $transaction = DB::transaction(function () use ($id, $validated) {
            $transaction = BorrowTransaction::where('id', $id)->lockForUpdate()->firstOrFail();

            if ($transaction->isVoided()) {
                return $transaction;
            }

            $statusBefore = ActivityLog::loanStatus($transaction);
            $released = $transaction->isOut() || $transaction->isIssued();

            if ($released) {
                $equipment = Equipment::where('id', $transaction->equipment_id)->lockForUpdate()->firstOrFail();
                $equipment->releaseStock($transaction->quantity, $transaction);
            }

            $transaction->voided_at = now();
            $transaction->void_reason = $validated['void_reason'];
            $transaction->save();

            ActivityLog::record('loan_voided', [
                'loan' => $transaction,
                'quantity' => $transaction->quantity,
                'status_from' => $statusBefore,
                'status_to' => 'Void',
                'details' => 'Voided the record of '.ActivityLog::units($transaction).' to '
                    .($transaction->user->name ?? 'a deleted account').': '.$validated['void_reason'],
                'meta' => ['void_reason' => $validated['void_reason'], 'units_released' => $released ? $transaction->quantity : 0],
            ]);

            return $transaction;
        });

        return redirect()->back()->with('success', ($transaction->status === 'Issued' ? 'Issue' : 'Borrow').' #'.$transaction->id
            .' voided. It stays in the log, and its units are back in stock.');
    }

    public function destroy($id)
    {
        $transaction = BorrowTransaction::with('returnLog')->findOrFail($id);

        if ($transaction->isOut()) {
            return redirect()->back()->with('error',
                'Cannot delete borrow #'.$transaction->id.' — it is still open and '.$transaction->quantity.' '
                .str('unit')->plural($transaction->quantity).' are out. Check it in or void it instead.');
        }

        if ($transaction->isIssued()) {
            return redirect()->back()->with('error',
                'Cannot delete issue #'.$transaction->id.' — its '.$transaction->quantity.' '
                .str('unit')->plural($transaction->quantity).' are counted as given out. Void it instead.');
        }

        if ($transaction->returnLog) {
            return redirect()->back()->with('error',
                'Cannot delete borrow #'.$transaction->id.' — it is part of the return history. Void it instead.');
        }

        $reference = $transaction->id;
        $transaction->loadMissing(['user', 'equipment']);

        DB::transaction(function () use ($transaction) {
            $statusBefore = ActivityLog::loanStatus($transaction);
            $transaction->delete();

            ActivityLog::record('loan_deleted', [
                'equipment' => $transaction->equipment,
                'subject' => $transaction->user,
                'borrow_transaction_id' => null,
                'quantity' => $transaction->quantity,
                'status_from' => $statusBefore,
                'details' => 'Deleted borrow #'.$transaction->id.' ('.ActivityLog::units($transaction).' to '
                    .($transaction->user->name ?? 'a deleted account').').',
                'meta' => ['loan_id' => $transaction->id, 'void_reason' => $transaction->void_reason],
            ]);
        });

        return redirect()->back()->with('success', 'Borrow #'.$reference.' deleted. Nothing referenced it.');
    }

    private function reminderBody(BorrowTransaction $transaction): string
    {
        $name = $transaction->user->name ?? 'there';
        $item = $transaction->equipment->equipment_name ?? 'the equipment you borrowed';
        $qty = $transaction->quantity > 1 ? ' ×'.$transaction->quantity : '';
        $due = $transaction->return_date?->format($transaction->timed ? 'M j \a\t g:i A' : 'M j') ?? 'the agreed date';

        if ($transaction->isOverdue()) {
            $late = $transaction->timed
                ? $transaction->timingLabel()
                : $transaction->daysLate().' '.str('day')->plural($transaction->daysLate()).' late';

            return "Hello {$name}, {$item}{$qty} was due on {$due} and is now {$late}. "
                .'Please return it to the CICT equipment room today.';
        }

        return "Hello {$name}, this is a reminder that {$item}{$qty} is due back on {$due}. "
            .'You can return it to the CICT equipment room during office hours.';
    }

    private function safe(callable $action, string $context): bool
    {
        try {
            $action();

            return true;
        } catch (\Throwable $e) {
            \Log::error($context.': '.$e->getMessage());

            return false;
        }
    }
}
