<?php

namespace App\Http\Controllers;

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
        // Oldest first. A queue is worked in order, and `id` breaks ties so two
        // requests filed the same day keep a stable position between renders.
        $requests = ItemRequest::with(['user', 'equipment', 'decider'])
            ->orderBy('requested_date')
            ->orderBy('id')
            ->get();

        // Split here rather than in the view: the queue that needs a decision
        // and the archive of decisions already made are two different screens
        // stacked on one page, not one table with a status column.
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

    /**
     * What each borrower in the queue is already holding — units out and how
     * many of those loans are late.
     *
     * One grouped query for the whole queue rather than two per row: this sits
     * next to every name on the page, and a request list of twenty would
     * otherwise fire forty queries to draw a subtitle.
     *
     * @param  list<int>  $userIds
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function borrowerStanding(array $userIds)
    {
        if ($userIds === []) {
            return collect();
        }

        $today = Carbon::today()->toDateString();

        return BorrowTransaction::query()
            ->whereIn('user_id', $userIds)
            ->whereNull('voided_at')
            ->whereIn('status', ['Borrowed', 'Overdue'])
            ->groupBy('user_id')
            ->selectRaw('user_id')
            ->selectRaw('SUM(quantity) as units_out')
            ->selectRaw('SUM(CASE WHEN return_date < ? THEN 1 ELSE 0 END) as overdue_loans', [$today])
            ->get()
            ->keyBy('user_id');
    }

    /**
     * The queue, annotated with the two things an admin actually needs to know
     * before saying yes: what approving would leave on the shelf, and anything
     * that makes this a bad yes.
     *
     * `leaves` is computed **cumulatively**. Availability alone answers "can I
     * fill this one", but the admin is working down a list, and three pending
     * requests for four units of a six-unit item are individually fillable and
     * collectively not. Walking the queue in the order it will be worked and
     * subtracting as it goes is the only reading that matches what will
     * actually happen.
     *
     * Nothing here authorises anything: requestActions() re-checks under a row
     * lock before it deducts. This is the screen telling the truth in advance.
     *
     * @return array<int, array<string, mixed>>
     */
    private function reviewQueue($pending, $standing): array
    {
        $queue = [];
        $claimed = [];   // equipment_id => units already spoken for by earlier rows

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
                // Fillable on its own, but earlier requests in this queue want
                // the same units. Approving those first leaves this one short.
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

        // A decline is a message to a person, so it carries a reason. The rule
        // is enforced here and not only in the form: the field is required on
        // the server, and there is no path that writes Declined without one.
        $rules = ['id' => 'required|exists:item_requests,id'];
        if ($isDecline) {
            $rules['reason'] = 'required|string|min:5|max:500';
        }
        $validated = $request->validate($rules, [
            'reason.required' => 'Say why you are declining — the borrower sees this.',
            'reason.min' => 'Give the borrower a usable reason (at least 5 characters).',
        ]);

        $itemRequest = ItemRequest::with(['equipment', 'user'])->findOrFail($validated['id']);

        // Authorization / idempotency: only pending requests can be processed
        if (! $itemRequest->isPending()) {
            return back()->with('error', 'Request has already been '.strtolower($itemRequest->status).'.');
        }

        if ($isApprove) {
            try {
                DB::transaction(function () use ($itemRequest) {
                    // Lock equipment to guard race on available_quantity
                    $equipment = Equipment::where('id', $itemRequest->equipment_id)->lockForUpdate()->firstOrFail();

                    if ($equipment->isRetired()) {
                        throw ValidationException::withMessages([
                            'quantity' => $equipment->equipment_name.' has been retired and can no longer be lent out.',
                        ]);
                    }

                    // Deduct stock
                    $equipment->reserveStock(
                        $itemRequest->quantity,
                        'Cannot approve — only '.$equipment->available_quantity.' of '.$equipment->equipment_name
                        .' available and this request needs '.$itemRequest->quantity.'.'
                    );

                    // Flip request
                    $itemRequest->status = 'Approved';
                    $itemRequest->decided_at = now();
                    $itemRequest->decided_by = Auth::id();
                    $itemRequest->save();

                    // Auto-create BorrowTransaction so approved requests don't sit idle.
                    // Dates default to today / +7 days; purpose falls back to remarks.
                    BorrowTransaction::create([
                        'user_id' => $itemRequest->user_id,
                        'equipment_id' => $itemRequest->equipment_id,
                        'borrow_date' => Carbon::today()->toDateString(),
                        'return_date' => Carbon::today()->addDays((int) config('office.loan_days', 7))->toDateString(),
                        'quantity' => $itemRequest->quantity,
                        'purpose' => $itemRequest->remarks ? mb_substr($itemRequest->remarks, 0, 250) : 'Approved item request #'.$itemRequest->id,
                        'status' => 'Borrowed',
                        'remarks' => $itemRequest->remarks,
                        'class_schedule_id' => null,
                    ]);
                });
            } catch (ValidationException $e) {
                return back()->withErrors($e->errors())->withInput();
            }

            $due = Carbon::today()->addDays((int) config('office.loan_days', 7))->format('M j');

            return back()->with('success', 'Approved — '.$itemRequest->quantity.' × '
                .($itemRequest->equipment->equipment_name ?? 'item').' is now out with '
                .($itemRequest->user->name ?? 'the borrower').', due '.$due.'.');
        }

        if ($isDecline) {
            $itemRequest->status = 'Declined';
            $itemRequest->decision_reason = $validated['reason'];
            $itemRequest->decided_at = now();
            $itemRequest->decided_by = Auth::id();
            $itemRequest->save();

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

        // A suspended account signs in, sees its loans, and cannot borrow.
        // Checked on the server because the borrower's form is not the only
        // way to reach this action.
        $borrower = Auth::user();
        if ($borrower && $borrower->isSuspended()) {
            return back()->withErrors([
                'quantity' => 'Borrowing is suspended on your account'
                    .($borrower->suspension_reason ? ' — '.$borrower->suspension_reason : '')
                    .'. Speak to the equipment office.',
            ])->withInput();
        }

        // Overdue items pause borrowing — the rule the terms and the landing
        // page both state. Checked here, on the server, for the same reason as
        // suspension: the borrower's form is not the only way in.
        $overdue = BorrowTransaction::where('user_id', Auth::id())
            ->whereNull('voided_at')
            ->whereIn('status', ['Borrowed', 'Overdue'])
            ->whereDate('return_date', '<', Carbon::today()->toDateString())
            ->with('equipment')
            ->orderBy('return_date')
            ->first();

        if ($overdue) {
            return back()->withErrors([
                'quantity' => 'You have an overdue item — '.($overdue->equipment->equipment_name ?? 'equipment')
                    .', due '.$overdue->return_date->format('M j')
                    .'. Return it before requesting more.',
            ])->withInput();
        }

        $equipment = Equipment::findOrFail($validated['equipment_id']);

        if ($error = $this->availabilityError($equipment, $validated['quantity'])) {
            return back()->withErrors(['quantity' => $error])->withInput();
        }

        ItemRequest::create([
            'user_id' => Auth::id(),
            'equipment_id' => $validated['equipment_id'],
            'quantity' => $validated['quantity'],
            'status' => 'Pending',
            'requested_date' => Carbon::now()->toDateString(),
            'remarks' => $validated['remarks'] ?? null,
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

        // A decided request is a record of what was decided. Editing one would
        // change the thing the admin approved after the fact.
        if (! $itemRequest->isPending()) {
            return back()->with('error', 'This request was already '.strtolower($itemRequest->status)
                .' and can no longer be changed.');
        }

        if ($itemRequest->equipment && ($error = $this->availabilityError($itemRequest->equipment, $validated['quantity']))) {
            return back()->withErrors(['quantity' => $error])->withInput();
        }

        $itemRequest->update([
            'quantity' => $validated['quantity'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Request updated — now asking for '.$validated['quantity'].' × '
                .($itemRequest->equipment->equipment_name ?? 'item').'.');
    }

    public function destroy($id)
    {
        $itemRequest = ItemRequest::with('equipment')->findOrFail($id);
        $this->assertOwner($itemRequest);

        // Withdrawing is only for a request nobody has acted on yet. Once it is
        // approved or declined it belongs to the decision log.
        if (! $itemRequest->isPending()) {
            return back()->with('error', 'This request was already '.strtolower($itemRequest->status)
                .' — it stays in your history.');
        }

        $name = $itemRequest->equipment->equipment_name ?? 'item';
        $itemRequest->delete();

        return redirect()
            ->back()
            ->with('success', 'Withdrew your request for '.$name.'.');
    }

    /**
     * The cap the request form enforces client-side, restated here so the
     * server is the one that decides. Returns null when the ask is fillable.
     */
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

    /**
     * A borrower may only modify their own item requests.
     */
    private function assertOwner(ItemRequest $itemRequest): void
    {
        if ($itemRequest->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
    }
}
