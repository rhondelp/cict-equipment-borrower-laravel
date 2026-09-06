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
        $requests = ItemRequest::with(['user', 'equipment'])->get();

        return view('admin.request', compact('requests'));
    }

    public function requestActions(Request $request)
    {
        $request->validate(['id' => 'required|exists:item_requests,id']);
        $itemRequest = ItemRequest::findOrFail($request->id);

        // Authorization / idempotency: only pending requests can be processed
        if ($itemRequest->status !== 'Pending') {
            return back()->with('error', 'Request has already been '.strtolower($itemRequest->status).'.');
        }

        $isApprove = $request->route()->getName() === 'admin.request.approve';
        $isDecline = $request->route()->getName() === 'admin.request.decline';

        if ($isApprove) {
            try {
                DB::transaction(function () use ($itemRequest) {
                    // Lock equipment to guard race on available_quantity
                    $equipment = Equipment::where('id', $itemRequest->equipment_id)->lockForUpdate()->firstOrFail();

                    if ($equipment->available_quantity < $itemRequest->quantity) {
                        throw ValidationException::withMessages(['quantity' => 'Not enough '.$equipment->equipment_name.' available (have '.$equipment->available_quantity.', need '.$itemRequest->quantity.').']);
                    }

                    // Deduct stock
                    $equipment->available_quantity -= $itemRequest->quantity;
                    $equipment->status = $equipment->available_quantity > 0 ? 'Available' : 'Unavailable';
                    $equipment->save();

                    // Flip request
                    $itemRequest->status = 'Approved';
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

            return back()->with('success', 'Request approved and transaction created. Stock deducted.');
        }

        if ($isDecline) {
            $itemRequest->status = 'Declined';
            $itemRequest->save();

            return back()->with('success', 'Request has been declined.');
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
            ->with('success', 'Your item request has been submitted successfully.');
    }

    public function update(Request $request, ItemRequest $itemRequest)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:item_requests,id',
            'quantity' => 'required|integer|min:1',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $itemRequest = ItemRequest::findOrFail($validated['id']);
        $this->assertOwner($itemRequest);

        $itemRequest->update([
            'quantity' => $validated['quantity'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Request has been updated successfully.');
    }

    public function destroy($id)
    {
        $itemRequest = ItemRequest::findOrFail($id);
        $this->assertOwner($itemRequest);

        $itemRequest->delete();

        return redirect()
            ->back()
            ->with('success', 'Your item request has been deleted successfully.');
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
