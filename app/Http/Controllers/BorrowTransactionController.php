<?php

namespace App\Http\Controllers;

use App\Mail\ReturnNotification;
use App\Models\BorrowTransaction;
use App\Models\ClassSchedule;
use App\Models\Equipment;
use App\Models\Notification;
use App\Models\ReturnLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        // Worst first. The default view of this screen is the open loans, and
        // an open-loans list is worked in order of how much trouble each one
        // is in — not in the order the loans happened to be created.
        //
        // Sorted here rather than in SQL because "overdue" is derived from the
        // due date against today, and the stored status lags behind it until
        // the nightly sweep runs. Ranking on the stored column would put a loan
        // that is three days late behind one that is not late at all.
        $transactions = $transactions->sortBy([
            fn ($a, $b) => $this->queueRank($a) <=> $this->queueRank($b),
            // Within a rank, the most urgent due date leads.
            fn ($a, $b) => ($a->return_date?->timestamp ?? PHP_INT_MAX) <=> ($b->return_date?->timestamp ?? PHP_INT_MAX),
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

    /**
     * Print-friendly slip for a single transaction.
     *
     * Scoped to the signed-in borrower: the route sits in the borrower group, so
     * without this ownership check any borrower could read another's slip by id.
     */
    /**
     * Where a loan sits in the work queue: overdue, then due soon, then simply
     * out, then everything already settled.
     *
     * @return int lower sorts first
     */
    private function queueRank(BorrowTransaction $transaction): int
    {
        if ($transaction->isVoided()) {
            return 4;
        }

        if ($transaction->isReturned()) {
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

        return view('borrower.receipt', compact('transaction'));
    }

    /**
     * Check a loan back in. This replaced the inline status dropdown: an admin
     * no longer picks a status from a list — they record the physical event of
     * equipment coming back, and the status follows from that.
     *
     * Only one direction exists. A returned loan is history; lending the same
     * item again is a new loan, not an edit of the old one.
     */
    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:borrow_transactions,id',
            // Legacy values stay accepted so rows written before the
            // vocabulary changed are still valid; only the four above are
            // offered on the form.
            'condition' => 'required|in:'.implode(',', array_merge(ReturnLog::CONDITIONS, ReturnLog::LEGACY_CONDITIONS)),
            // Anything other than Good is an incident, and an incident nobody
            // described is useless to whoever reads the log next.
            'remarks' => 'required_unless:condition,Good|nullable|string|max:255',
        ], [
            'remarks.required_unless' => 'Describe the problem so the return log says what is wrong.',
        ]);

        try {
            $transaction = DB::transaction(function () use ($validated) {
                $transaction = BorrowTransaction::where('id', $validated['id'])->lockForUpdate()->firstOrFail();

                if ($transaction->isVoided()) {
                    throw ValidationException::withMessages(['id' => 'This loan was voided and cannot be checked in.']);
                }
                if ($transaction->isReturned()) {
                    throw ValidationException::withMessages(['id' => 'This loan is already checked in.']);
                }

                // Lock equipment row to prevent concurrent race on available_quantity
                $equipment = Equipment::where('id', $transaction->equipment_id)->lockForUpdate()->firstOrFail();
                $equipment->releaseStock($transaction->quantity);

                ReturnLog::create([
                    'borrow_transaction_id' => $transaction->id,
                    'return_date' => now(),
                    'condition' => $validated['condition'],
                    'remarks' => ($validated['remarks'] ?? null) ?: null,
                    'user_id' => auth()->id(),
                ]);

                $transaction->status = 'Returned';
                $transaction->save();

                return $transaction;
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        $transaction->loadMissing('equipment');
        $late = $transaction->return_date && $transaction->return_date->startOfDay()->lt(now()->startOfDay())
            ? ' Logged as '.(int) $transaction->return_date->startOfDay()->diffInDays(now()->startOfDay()).' days late.'
            : '';

        return redirect()->back()->with('success', 'Checked in — '.$transaction->quantity.' × '
            .($transaction->equipment->equipment_name ?? 'item').' back on the shelf.'.$late);
    }

    public function store(Request $request)
    {
        // Filter out empty placeholder values from multi-select (prevents "equipment.0 is invalid" validation error)
        if ($request->has('equipment')) {
            $filteredEquip = array_values(array_filter((array) $request->input('equipment'), fn ($v) => $v !== '' && $v !== null));
            $request->merge(['equipment' => $filteredEquip]);
        }
        if ($request->has('quantities')) {
            $filteredQty = array_filter((array) $request->input('quantities'), fn ($v, $k) => $k !== '' && $v !== '' && $v !== null, ARRAY_FILTER_USE_BOTH);
            $request->merge(['quantities' => $filteredQty]);
        }

        // No `status` field: handing equipment over is the only thing this form
        // does, so the loan is created out. Whether it later reads Out or
        // Overdue is a question the due date answers.
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'equipment' => 'required|array|min:1',
            'equipment.*' => 'exists:equipment,id',
            'quantities' => 'required|array|min:1',
            'quantities.*' => 'integer|min:1',
            'borrow_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:borrow_date',
            'purpose' => 'required|string|max:255',
            'remarks' => 'nullable|string',
            'class_schedule_id' => 'nullable|exists:class_schedules,id',
        ]);

        $userId = $validated['user_id'];
        $borrowDate = $validated['borrow_date'];
        $returnDate = $validated['return_date'];
        $remarks = $validated['remarks'] ?? null;
        $classScheduleId = $validated['class_schedule_id'] ?? null;
        $purpose = $validated['purpose'];

        // The multi-equipment loop runs inside one transaction with row locking
        // to prevent a race and to avoid partial writes when one item is short.
        try {
            $created = DB::transaction(function () use ($validated, $userId, $borrowDate, $returnDate, $remarks, $classScheduleId, $purpose) {
                $units = 0;

                foreach ($validated['equipment'] as $equipmentId) {
                    // quantities may be keyed as string numeric; handle missing key gracefully
                    if (! isset($validated['quantities'][$equipmentId])) {
                        throw ValidationException::withMessages(['quantity' => "Quantity missing for equipment #{$equipmentId}."]);
                    }
                    $quantity = (int) $validated['quantities'][$equipmentId];

                    $equipment = Equipment::where('id', $equipmentId)->lockForUpdate()->firstOrFail();

                    if ($equipment->isRetired()) {
                        throw ValidationException::withMessages([
                            'quantity' => $equipment->equipment_name.' has been retired and can no longer be lent out.',
                        ]);
                    }

                    $equipment->reserveStock(
                        $quantity,
                        "Only {$equipment->available_quantity} of {$equipment->equipment_name} available — this loan needs {$quantity}."
                    );

                    BorrowTransaction::create([
                        'user_id' => $userId,
                        'equipment_id' => $equipmentId,
                        'borrow_date' => $borrowDate,
                        'return_date' => $returnDate,
                        'quantity' => $quantity,
                        'purpose' => $purpose,
                        'status' => 'Borrowed',
                        'remarks' => $remarks,
                        'class_schedule_id' => $classScheduleId,
                    ]);

                    $units += $quantity;
                }

                return $units;
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $due = Carbon::parse($returnDate)->format('M j');

        return redirect()->back()->with('success', 'Loan recorded — '.$created.' '
            .str('unit')->plural($created).' out, due back '.$due.'.');
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

        $details = $type === 'custom'
            ? ['title' => 'Message from Admin', 'body' => $message]
            : ['title' => $transaction->isOverdue() ? 'Overdue notice' : 'Return Reminder', 'body' => $this->reminderBody($transaction)];

        // Kept inline (not extracted to safe()) because the user-facing JSON
        // response embeds $e->getMessage() — centralizing the catch would lose that.
        try {
            Mail::to($transaction->user->email)->send(new ReturnNotification($details));
        } catch (\Exception $e) {
            \Log::error('Manual email failed for transaction '.$id.': '.$e->getMessage());

            return response()->json(['message' => 'Failed to send email: '.$e->getMessage()], 500);
        }

        // Recorded against the loan, not just sent. Without this the screen
        // cannot answer "have we chased this one yet?", and an admin looking at
        // an overdue item has no way to tell a first nudge from a fourth.
        // Written after the send so a failed send leaves no record of one.
        $this->safe(
            fn () => Notification::create([
                'user_id' => $transaction->user->id,
                'borrow_transaction_id' => $transaction->id,
                'message' => $details['body'],
                'notification_type' => $details['title'],
                'send_date' => Carbon::now(),
            ]),
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

        // Keep the stored enum in step with the calendar so the reminder queries
        // below can find their targets. The screens no longer depend on this
        // running — they read Overdue off the due date — but the mail does.
        BorrowTransaction::where('status', 'Borrowed')
            ->whereNull('voided_at')
            ->whereDate('return_date', '<', $today)
            ->update(['status' => 'Overdue']);

        // Find all borrow transactions with return_date == today and status still "Borrowed"
        $transactions = BorrowTransaction::with(['user', 'equipment'])
            ->whereNull('voided_at')
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

                // Skip users who already received a return notice today
                // (guards against double sends when multiple triggers run the same day).
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

                // Save the notification into DB inside a transaction to keep mail+DB consistent
                $this->safe(
                    fn () => DB::transaction(function () use ($transaction, $details) {
                        // Double-check inside transaction to avoid race
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
                    }),
                    'Notification DB log failed for transaction '.$transaction->id
                );

                $sent++;
            }
        }

        return $sent.' return notifications sent and logged for today.';
    }

    /**
     * Edit an open loan. `status` is gone from the payload: it is derived from
     * the due date and from whether the equipment has come back, so there is no
     * longer a control that could set it to something the data contradicts.
     *
     * Returned and voided loans are not editable — they are the audit trail.
     */
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
                        'id' => 'This loan is closed. Closed loans are the return history and cannot be edited.',
                    ]);
                }

                $oldEquipmentId = $transaction->equipment_id;
                $newEquipmentId = $validated['equipment_id'];
                $oldQty = $transaction->quantity;
                $newQty = $validated['quantity'];

                if ($oldEquipmentId != $newEquipmentId) {
                    // Lock both equipment rows in consistent order to avoid deadlock
                    $ids = collect([$oldEquipmentId, $newEquipmentId])->sort()->values();
                    $locked = Equipment::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
                    $oldEquipment = $locked[$oldEquipmentId];
                    $equipment = $locked[$newEquipmentId];

                    $oldEquipment->releaseStock($oldQty);
                    $equipment->reserveStock(
                        $newQty,
                        "Only {$equipment->available_quantity} of {$equipment->equipment_name} available — this loan needs {$newQty}."
                    );
                } else {
                    $equipment = Equipment::where('id', $oldEquipmentId)->lockForUpdate()->firstOrFail();

                    // The loan is open both before and after, so only the delta moves.
                    $diff = $newQty - $oldQty;
                    if ($diff > 0) {
                        $equipment->reserveStock(
                            $diff,
                            "Only {$equipment->available_quantity} more of {$equipment->equipment_name} available."
                        );
                    } else {
                        $equipment->releaseStock(-$diff);
                    }
                }

                $transaction->update($validated);
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        return redirect()->back()->with('success', 'Loan updated.');
    }

    /**
     * The non-destructive default offered by the remove dialog: the record
     * stays visible and marked as an error, and any units it was holding go
     * back on the shelf.
     */
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

            if ($transaction->isOut()) {
                $equipment = Equipment::where('id', $transaction->equipment_id)->lockForUpdate()->firstOrFail();
                $equipment->releaseStock($transaction->quantity);
            }

            $transaction->voided_at = now();
            $transaction->void_reason = $validated['void_reason'];
            $transaction->save();

            return $transaction;
        });

        return redirect()->back()->with('success', 'Loan #'.$transaction->id
            .' voided. It stays in the log, and its units are back in stock.');
    }

    /**
     * Hard delete, refused while the record is part of the history: an open
     * loan is tracking units that are physically elsewhere, and a returned one
     * is the return log's reason for existing.
     */
    public function destroy($id)
    {
        $transaction = BorrowTransaction::with('returnLog')->findOrFail($id);

        if ($transaction->isOut()) {
            return redirect()->back()->with('error',
                'Cannot delete loan #'.$transaction->id.' — it is still open and '.$transaction->quantity.' '
                .str('unit')->plural($transaction->quantity).' are out. Check it in or void it instead.');
        }

        if ($transaction->returnLog) {
            return redirect()->back()->with('error',
                'Cannot delete loan #'.$transaction->id.' — it is part of the return history. Void it instead.');
        }

        $reference = $transaction->id;
        $transaction->delete();

        return redirect()->back()->with('success', 'Loan #'.$reference.' deleted. Nothing referenced it.');
    }

    /** One wording for the reminder mail, used by both the job and the manual send. */
    private function reminderBody(BorrowTransaction $transaction): string
    {
        $name = $transaction->user->name ?? 'there';
        $item = $transaction->equipment->equipment_name ?? 'the equipment you borrowed';
        $qty = $transaction->quantity > 1 ? ' ×'.$transaction->quantity : '';
        $due = $transaction->return_date?->format('M j') ?? 'the agreed date';

        if ($transaction->isOverdue()) {
            $late = $transaction->daysLate();

            return "Hello {$name}, {$item}{$qty} was due on {$due} and is now {$late} "
                .str('day')->plural($late).' late. Please return it to the CICT equipment room today.';
        }

        return "Hello {$name}, this is a reminder that {$item}{$qty} is due back on {$due}. "
            .'You can return it to the CICT equipment room during office hours.';
    }

    /**
     * Run $action, logging any exception under $context. Returns true on
     * success, false on caught \Throwable. Callers keep their own response
     * shaping (JSON / redirect / continue) so the helper stays
     * responsibility-light.
     */
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
