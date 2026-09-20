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

        return view('admin.request', compact('requests', 'pending', 'decided'));
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
                        'return_date' => Carbon::today()->addDays(7)->toDateString(),
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

            $due = Carbon::today()->addDays(7)->format('M j');

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
