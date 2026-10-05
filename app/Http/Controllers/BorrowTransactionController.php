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
            // Within a rank, the most urgent due moment leads. dueAt() is the
            // end of the day for a date-only loan and the exact time for a
            // timed one, so a loan due at 2 PM sorts ahead of one due "today".
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

        // Settled: nothing is coming back from a returned loan or an issue.
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

        // The dashboard offers one slip per booking, so the slip lists every
        // item that went out with this one.
        $bookingLoans = $transaction->bookingLoans();

        return view('borrower.receipt', compact('transaction', 'bookingLoans'));
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

        $lateLabel = null;

        try {
            $transaction = DB::transaction(function () use ($validated, &$lateLabel) {
                $transaction = BorrowTransaction::where('id', $validated['id'])->lockForUpdate()->firstOrFail();

                if ($transaction->isVoided()) {
                    throw ValidationException::withMessages(['id' => 'This loan was voided and cannot be checked in.']);
                }
                if ($transaction->isReturned()) {
                    throw ValidationException::withMessages(['id' => 'This loan is already checked in.']);
                }
                if ($transaction->isIssued()) {
                    throw ValidationException::withMessages([
                        'id' => 'This item was issued, not lent, so nothing is due back to check in. Void the record if it was entered by mistake.',
                    ]);
                }

                // Read before the status flips: once Returned, the loan has
                // no "late" left to describe.
                $lateLabel = $transaction->isOverdue() ? $transaction->timingLabel() : null;

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
        $late = $lateLabel ? ' Logged as '.$lateLabel.'.' : '';

        return redirect()->back()->with('success', 'Checked in — '.$transaction->quantity.' × '
            .($transaction->equipment->equipment_name ?? 'item').' back on the shelf.'.$late);
    }

    /**
     * Record a handover. Several items can go out at once, and each one is
     * recorded by its own loan type, read from the equipment row under the
     * lock — never from the request:
     *
     *   returnable      Borrowed, due by the end of the return date (timed = false)
     *   time_limited    Borrowed, due at the exact return moment (timed = true)
     *   non_returnable  Issued, no return date; its units count in unitsIssued()
     *
     * One borrow field and one return field serve the whole selection, so
     * the form sends a datetime when any item is time-limited, and a
     * returnable item in the same booking takes the date part of it.
     */
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

        // No `status` field: what the loan becomes is decided by each item's
        // loan type below. Whether a due date is needed depends on the same
        // thing, so `return_date` is only `nullable` here and required below.
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

        // The multi-equipment loop runs inside one transaction with row locking
        // to prevent a race and to avoid partial writes when one item is short.
        try {
            $result = DB::transaction(function () use ($validated) {
                // Every selected row is locked before any is touched, in id
                // order, so the loan types the dates are checked against are
                // the ones the rows are saved under.
                $items = Equipment::whereIn('id', array_unique($validated['equipment']))
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

                [$borrowAt, $dueAt] = $this->handoverMoments($items, $validated['borrow_date'], $validated['return_date'] ?? null);

                $tally = ['out' => 0, 'issued' => 0, 'timed' => false];

                foreach ($validated['equipment'] as $equipmentId) {
                    // quantities may be keyed as string numeric; handle missing key gracefully
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

                    // An issue comes off the shelf exactly like a loan; it is
                    // simply never released by a check-in.
                    $equipment->reserveStock(
                        $quantity,
                        "Only {$equipment->available_quantity} of {$equipment->equipment_name} available — this loan needs {$quantity}."
                    );

                    $issued = $equipment->isNonReturnable();
                    $timed = $equipment->isTimeLimited();

                    BorrowTransaction::create([
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

        return redirect()->back()->with('success', ($result['out'] > 0 ? 'Loan' : 'Issue').' recorded — '.implode('; ', $parts).'.');
    }

    /**
     * The borrow and due moments for one handover, checked against what is
     * actually being handed over. The server decides here, whatever the form
     * showed:
     *
     *   - nothing but non-returnable items: no due date needed, any sent is ignored;
     *   - anything returnable or time-limited: a due date is required;
     *   - anything time-limited: both fields need a time, and the due moment
     *     must come after the borrow moment;
     *   - returnable items are due on or after the day they go out.
     *
     * @return array{0: Carbon, 1: ?Carbon}
     */
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

    /** "2026-10-05T14:30" or "2026-10-05 14:30" carries a time; "2026-10-05" does not. */
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

        // The canned email is a return reminder, and nothing issued is due
        // back. The loans screen draws no email button for an issue; this is
        // the server saying the same thing to anything that posts anyway.
        if ($type !== 'custom' && $transaction->isIssued()) {
            return response()->json(['message' => 'This item was issued, not lent — there is nothing to remind about.'], 422);
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
        // running — they read Overdue off dueAt() — but the mail does. The
        // overdue() scope is the same rule: a timed loan by its time, a
        // date-only loan by its day, and it only ever matches loans that are
        // out, so an Issued row is never touched. This runs once a day, so a
        // timed loan that falls due after the run is flipped on the next one.
        BorrowTransaction::where('status', 'Borrowed')
            ->overdue()
            ->update(['status' => 'Overdue']);

        // Loans due back today that are still Borrowed. Issued rows have no
        // return date and are not Borrowed, so they are never reminded; a timed
        // loan's reminder names its time (reminderBody()).
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
     * Issued records are not editable either; they are void-only.
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

                // An issue is a finished hand-over with nothing due back, so
                // there is no date or quantity left to adjust. A wrong one is
                // voided and recorded again.
                if ($transaction->isIssued()) {
                    throw ValidationException::withMessages([
                        'id' => 'Issued items cannot be edited. Void the record if it was entered by mistake, then record it again.',
                    ]);
                }

                // The loan keeps the kind it was made as: a timed loan stays
                // due at a time, a date-only loan stays due by a day.
                [$borrowAt, $dueAt] = $this->editedMoments($transaction->timed, $validated['borrow_date'], $validated['return_date']);

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

                    // Moving a loan to an item of another loan type would give
                    // it terms it was never made under: a due time on a
                    // date-only loan, or an issue that still expects a return.
                    $wanted = $transaction->timed ? Equipment::LOAN_TIME_LIMITED : Equipment::LOAN_RETURNABLE;
                    if ($equipment->loan_type !== $wanted) {
                        throw ValidationException::withMessages([
                            'equipment_id' => $equipment->equipment_name.' is '.$equipment->loanTypeLabel().', and this loan is '
                                .Equipment::LOAN_TYPES[$wanted].'. Pick another '.Equipment::LOAN_TYPES[$wanted]
                                .' item, or void this loan and record a new one.',
                        ]);
                    }

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

                $transaction->update(['borrow_date' => $borrowAt, 'return_date' => $dueAt] + $validated);
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        return redirect()->back()->with('success', 'Loan updated.');
    }

    /**
     * Borrow and due moments for an edited loan, by its own kind. A timed
     * loan needs a time on both and must fall due after it went out; a
     * date-only loan keeps the date part of each, stored at 00:00:00.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function editedMoments(bool $timed, string $borrowInput, string $returnInput): array
    {
        $borrowAt = Carbon::parse($borrowInput);
        $dueAt = Carbon::parse($returnInput);

        if (! $timed) {
            return [$borrowAt->startOfDay(), $dueAt->startOfDay()];
        }

        if (! $this->hasTime($borrowInput) || ! $this->hasTime($returnInput)) {
            throw ValidationException::withMessages([
                'return_date' => 'This is a time-limited loan. Give the time it went out and the time it is due back.',
            ]);
        }

        if (! $dueAt->gt($borrowAt)) {
            throw ValidationException::withMessages([
                'return_date' => 'The due time must be after the moment it was taken out.',
            ]);
        }

        return [$borrowAt, $dueAt];
    }

    /**
     * The non-destructive default offered by the remove dialog: the record
     * stays visible and marked as an error, and any units it was holding go
     * back on the shelf. That covers an issue too: voided, it stops counting
     * in unitsIssued(), so its units are released like an open loan's.
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

            if ($transaction->isOut() || $transaction->isIssued()) {
                $equipment = Equipment::where('id', $transaction->equipment_id)->lockForUpdate()->firstOrFail();
                $equipment->releaseStock($transaction->quantity);
            }

            $transaction->voided_at = now();
            $transaction->void_reason = $validated['void_reason'];
            $transaction->save();

            return $transaction;
        });

        return redirect()->back()->with('success', ($transaction->status === 'Issued' ? 'Issue' : 'Loan').' #'.$transaction->id
            .' voided. It stays in the log, and its units are back in stock.');
    }

    /**
     * Hard delete, refused while the record is part of the history: an open
     * loan is tracking units that are physically elsewhere, a returned one
     * is the return log's reason for existing, and an issue is what keeps its
     * units off the shelf — deleting it would leave available_quantity short
     * with nothing to explain why.
     */
    public function destroy($id)
    {
        $transaction = BorrowTransaction::with('returnLog')->findOrFail($id);

        if ($transaction->isOut()) {
            return redirect()->back()->with('error',
                'Cannot delete loan #'.$transaction->id.' — it is still open and '.$transaction->quantity.' '
                .str('unit')->plural($transaction->quantity).' are out. Check it in or void it instead.');
        }

        if ($transaction->isIssued()) {
            return redirect()->back()->with('error',
                'Cannot delete issue #'.$transaction->id.' — its '.$transaction->quantity.' '
                .str('unit')->plural($transaction->quantity).' are counted as given out. Void it instead.');
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
        $due = $transaction->return_date?->format($transaction->timed ? 'M j \a\t g:i A' : 'M j') ?? 'the agreed date';

        if ($transaction->isOverdue()) {
            // A timed loan is late by minutes or hours; timingLabel() says
            // which. A date-only loan keeps the day count it always had.
            $late = $transaction->timed
                ? $transaction->timingLabel()
                : $transaction->daysLate().' '.str('day')->plural($transaction->daysLate()).' late';

            return "Hello {$name}, {$item}{$qty} was due on {$due} and is now {$late}. "
                .'Please return it to the CICT equipment room today.';
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
