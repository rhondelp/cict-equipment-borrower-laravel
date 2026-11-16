<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\ItemRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ItemRequestController extends Controller
{
    public function index()
    {
        $requests = ItemRequest::with(['user', 'equipment', 'decider'])
            ->orderBy('requested_date')
            ->orderBy('id')
            ->get();

        $pending = $requests->where('status', 'Pending')->values();
        $decided = $requests->where('status', '!=', 'Pending')->sortByDesc(
            fn ($request) => $request->decided_at ?? $request->updated_at
        )->values();

        $standing = $this->borrowerStanding($pending->pluck('user_id')->filter()->unique()->all());

        return view('admin.request', [
            'requests' => $requests,
            'pending' => $pending,
            'decided' => $decided,
            'standing' => $standing,
            'queue' => $this->reviewQueue($pending, $standing),
        ]);
    }

    private function borrowerStanding(array $userIds)
    {
        if ($userIds === []) {
            return collect();
        }

        [$overdueCase, $bindings] = BorrowTransaction::overdueCaseSql();

        return BorrowTransaction::query()
            ->whereIn('user_id', $userIds)
            ->out()
            ->groupBy('user_id')
            ->selectRaw('user_id')
            ->selectRaw('SUM(quantity) as units_out')
            ->selectRaw("SUM({$overdueCase}) as overdue_loans", $bindings)
            ->get()
            ->keyBy('user_id');
    }

    private function reviewQueue($pending, $standing): array
    {
        $queue = [];
        $claimed = [];

        foreach ($pending as $request) {
            $equipment = $request->equipment;
            $available = (int) ($equipment->available_quantity ?? 0);
            $already = $claimed[$request->equipment_id] ?? 0;
            $leaves = $available - $already - (int) $request->quantity;

            $conflicts = [];

            if ($equipment === null) {
                $conflicts[] = ['key' => 'missing', 'text' => 'The item this asks for has been deleted.'];
            } elseif ($equipment->isRetired()) {
                $conflicts[] = ['key' => 'retired', 'text' => $equipment->equipment_name.' has been retired and can no longer be lent out.'];
            } elseif (! $request->canBeFilled()) {
                $conflicts[] = ['key' => 'stock', 'text' => 'Asks for '.$request->quantity.' but only '
                    .$available.' '.($available === 1 ? 'is' : 'are').' on the shelf.'];
            } elseif ($leaves < 0) {
                $conflicts[] = ['key' => 'overlap', 'text' => 'Earlier requests in this queue already claim '
                    .$already.' of the '.$available.' available — approving them first leaves this one short.'];
            }

            $their = $standing->get($request->user_id);
            $overdue = (int) ($their->overdue_loans ?? 0);
            if ($overdue > 0) {
                $conflicts[] = ['key' => 'overdue', 'text' => ($request->user->name ?? 'This borrower').' has '
                    .$overdue.' overdue '.str('item')->plural($overdue).' still out.'];
            }

            $queue[$request->id] = [
                'leaves' => max($leaves, 0),
                'available' => $available,
                'total' => (int) ($equipment->quantity ?? 0),
                'would_overdraw' => $leaves < 0,
                'conflicts' => $conflicts,
                'units_out' => (int) ($their->units_out ?? 0),
                'overdue_loans' => $overdue,
            ];

            $claimed[$request->equipment_id] = $already + (int) $request->quantity;
        }

        return $queue;
    }

    public function requestActions(Request $request)
    {
        $isApprove = $request->route()->getName() === 'admin.request.approve';
        $isDecline = $request->route()->getName() === 'admin.request.decline';

        $rules = ['id' => 'required|exists:item_requests,id'];
        if ($isDecline) {
            $rules['reason'] = 'required|string|min:5|max:500';
        }
        $validated = $request->validate($rules, [
            'reason.required' => 'Say why you are declining — the borrower sees this.',
            'reason.min' => 'Give the borrower a usable reason (at least 5 characters).',
        ]);

        $itemRequest = ItemRequest::with(['equipment', 'user'])->findOrFail($validated['id']);

        if (! $itemRequest->isPending()) {
            return back()->with('error', 'Request has already been '.strtolower($itemRequest->status).'.');
        }

        if ($isApprove) {
            try {
                $loan = DB::transaction(function () use ($itemRequest) {
                    $equipment = Equipment::where('id', $itemRequest->equipment_id)->lockForUpdate()->firstOrFail();

                    if ($equipment->isRetired()) {
                        throw ValidationException::withMessages([
                            'quantity' => $equipment->equipment_name.' has been retired and can no longer be lent out.',
                        ]);
                    }

                    $shortMessage = 'Cannot approve — only '.$equipment->available_quantity.' of '.$equipment->equipment_name
                        .' available and this request needs '.$itemRequest->quantity.'.';

                    $itemRequest->status = 'Approved';
                    $itemRequest->decided_at = now();
                    $itemRequest->decided_by = Auth::id();
                    $itemRequest->save();

                    $schedule = match ($equipment->loan_type) {
                        Equipment::LOAN_TIME_LIMITED => [
                            'borrow_date' => now(),
                            'return_date' => now()->addMinutes((int) config('office.time_limited_minutes', 60)),
                            'timed' => true,
                            'status' => 'Borrowed',
                        ],
                        Equipment::LOAN_NON_RETURNABLE => [
                            'borrow_date' => Carbon::today(),
                            'return_date' => null,
                            'timed' => false,
                            'status' => 'Issued',
                        ],
                        default => [
                            'borrow_date' => Carbon::today(),
                            'return_date' => Carbon::today()->addDays((int) config('office.loan_days', 1)),
                            'timed' => false,
                            'status' => 'Borrowed',
                        ],
                    };

                    $loan = BorrowTransaction::create($schedule + [
                        'user_id' => $itemRequest->user_id,
                        'equipment_id' => $itemRequest->equipment_id,
                        'quantity' => $itemRequest->quantity,
                        'purpose' => $itemRequest->remarks ? mb_substr($itemRequest->remarks, 0, 250) : 'Approved item request #'.$itemRequest->id,
                        'remarks' => $itemRequest->remarks,
                        'class_schedule_id' => null,
                    ]);
                    $loan->setRelation('equipment', $equipment);
                    $loan->setRelation('user', $itemRequest->user);

                    $equipment->reserveStock($itemRequest->quantity, $shortMessage, $loan);

                    ActivityLog::record('request_approved', [
                        'loan' => $loan,
                        'quantity' => $itemRequest->quantity,
                        'status_from' => 'Pending',
                        'status_to' => 'Approved',
                        'details' => 'Approved request #'.$itemRequest->id.': '.ActivityLog::units($loan).' for '
                            .($itemRequest->user->name ?? 'a deleted account').'.',
                        'meta' => array_filter(['request_id' => $itemRequest->id, 'remarks' => $itemRequest->remarks]),
                    ]);
                    ActivityLog::recordHandover($loan, ['meta' => ['request_id' => $itemRequest->id]]);

                    return $loan;
                });
            } catch (ValidationException $e) {
                return back()->withErrors($e->errors())->withInput();
            }

            $what = $itemRequest->quantity.' × '.($itemRequest->equipment->equipment_name ?? 'item');
            $who = $itemRequest->user->name ?? 'the borrower';

            if ($loan->status === 'Issued') {
                return back()->with('success', 'Approved — '.$what.' issued to '.$who.', not expected back.');
            }

            $due = $loan->timed ? $loan->return_date->format('g:i A') : $loan->return_date->format('M j');

            return back()->with('success', 'Approved — '.$what.' is now out with '.$who.', due '.$due.'.');
        }

        if ($isDecline) {
            $itemRequest->status = 'Declined';
            $itemRequest->decision_reason = $validated['reason'];
            $itemRequest->decided_at = now();
            $itemRequest->decided_by = Auth::id();
            $itemRequest->save();

            ActivityLog::record('request_declined', [
                'subject' => $itemRequest->user,
                'equipment' => $itemRequest->equipment,
                'quantity' => $itemRequest->quantity,
                'status_from' => 'Pending',
                'status_to' => 'Declined',
                'details' => 'Declined: '.$validated['reason'],
                'meta' => ['request_id' => $itemRequest->id, 'reason' => $validated['reason']],
            ]);

            return back()->with('success', 'Declined. '.($itemRequest->user->name ?? 'The borrower').' will see your reason.');
        }

        return back()->with('error', 'Unknown action.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'quantity' => 'required|integer|min:1',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $borrower = Auth::user();
        if ($borrower && $borrower->isSuspended()) {
            return back()->withErrors([
                'quantity' => 'Borrowing is suspended on your account'
                    .($borrower->suspension_reason ? ' — '.$borrower->suspension_reason : '')
                    .'. Speak to the equipment office.',
            ])->withInput();
        }

        $overdue = BorrowTransaction::where('user_id', Auth::id())
            ->overdue()
            ->with('equipment')
            ->orderBy('return_date')
            ->first();

        if ($overdue) {
            return back()->withErrors([
                'quantity' => 'You have an overdue item — '.($overdue->equipment->equipment_name ?? 'equipment')
                    .', due '.$overdue->return_date->format($overdue->timed ? 'M j, g:i A' : 'M j')
                    .'. Return it before requesting more.',
            ])->withInput();
        }

        $equipment = Equipment::findOrFail($validated['equipment_id']);

        if ($error = $this->availabilityError($equipment, $validated['quantity'])) {
            return back()->withErrors(['quantity' => $error])->withInput();
        }

        $itemRequest = ItemRequest::create([
            'user_id' => Auth::id(),
            'equipment_id' => $validated['equipment_id'],
            'quantity' => $validated['quantity'],
            'status' => 'Pending',
            'requested_date' => Carbon::now()->toDateString(),
            'remarks' => $validated['remarks'] ?? null,
        ]);

        ActivityLog::record('request_submitted', [
            'subject' => $borrower,
            'equipment' => $equipment,
            'quantity' => (int) $itemRequest->quantity,
            'status_to' => 'Pending',
            'details' => 'Requested '.$itemRequest->quantity.' × '.$equipment->equipment_name.'.',
            'meta' => array_filter(['request_id' => $itemRequest->id, 'remarks' => $itemRequest->remarks]),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Request sent — '.$validated['quantity'].' × '.$equipment->equipment_name
                .'. Nothing is held for you until an admin approves it.');
    }

    public function update(Request $request, ItemRequest $itemRequest)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:item_requests,id',
            'quantity' => 'required|integer|min:1',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $itemRequest = ItemRequest::with('equipment')->findOrFail($validated['id']);
        $this->assertOwner($itemRequest);

        if (! $itemRequest->isPending()) {
            return back()->with('error', 'This request was already '.strtolower($itemRequest->status)
                .' and can no longer be changed.');
        }

        if ($itemRequest->equipment && ($error = $this->availabilityError($itemRequest->equipment, $validated['quantity']))) {
            return back()->withErrors(['quantity' => $error])->withInput();
        }

        $before = ['quantity' => $itemRequest->quantity, 'remarks' => $itemRequest->remarks];

        $itemRequest->update([
            'quantity' => $validated['quantity'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        $changes = ActivityLog::changes($before, ['quantity' => $itemRequest->quantity, 'remarks' => $itemRequest->remarks]);
        if ($changes !== []) {
            ActivityLog::record('request_updated', [
                'subject' => Auth::user(),
                'equipment' => $itemRequest->equipment,
                'quantity' => (int) $itemRequest->quantity,
                'status_from' => 'Pending',
                'status_to' => 'Pending',
                'details' => 'Changed request #'.$itemRequest->id.': '.ActivityLog::changeWords($changes).'.',
                'meta' => ['request_id' => $itemRequest->id, 'changes' => $changes],
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Request updated — now asking for '.$validated['quantity'].' × '
                .($itemRequest->equipment->equipment_name ?? 'item').'.');
    }

    public function destroy($id)
    {
        $itemRequest = ItemRequest::with('equipment')->findOrFail($id);
        $this->assertOwner($itemRequest);

        if (! $itemRequest->isPending()) {
            return back()->with('error', 'This request was already '.strtolower($itemRequest->status)
                .' — it stays in your history.');
        }

        $name = $itemRequest->equipment->equipment_name ?? 'item';
        $itemRequest->delete();

        ActivityLog::record('request_cancelled', [
            'subject' => Auth::user(),
            'equipment' => $itemRequest->equipment,
            'quantity' => $itemRequest->quantity,
            'status_from' => 'Pending',
            'status_to' => 'Cancelled',
            'details' => 'Withdrew request #'.$itemRequest->id.' for '.$itemRequest->quantity.' × '.$name.'.',
            'meta' => ['request_id' => $itemRequest->id],
        ]);

        return redirect()
            ->back()
            ->with('success', 'Withdrew your request for '.$name.'.');
    }

    private function availabilityError(Equipment $equipment, int $quantity): ?string
    {
        if ($equipment->isRetired()) {
            return $equipment->equipment_name.' has been retired and can no longer be borrowed.';
        }

        if ($equipment->available_quantity < 1) {
            return 'There is none of '.$equipment->equipment_name.' on the shelf right now.';
        }

        if ($quantity > $equipment->available_quantity) {
            return 'Only '.$equipment->available_quantity.' of '.$equipment->equipment_name
                .' '.($equipment->available_quantity === 1 ? 'is' : 'are').' available — please lower the quantity.';
        }

        return null;
    }

    private function assertOwner(ItemRequest $itemRequest): void
    {
        if ($itemRequest->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
    }
}
