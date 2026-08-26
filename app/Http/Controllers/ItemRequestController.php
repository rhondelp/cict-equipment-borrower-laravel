<?php

namespace App\Http\Controllers;

use App\Models\ItemRequest;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ItemRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $requests = ItemRequest::all();
        // $users = User::all();
        // return view('admin.request', compact('requests', 'users'));

        // With eager loading
        $requests = ItemRequest::with(['user', 'equipment'])->get();
        return view('admin.request', compact('requests'));

        // return $requests;
    }

    public function requestActions(Request $request)
    {
        $itemRequest = ItemRequest::findOrFail($request->id);

        if ($request->route()->getName() === 'admin.request.approve') {
            $itemRequest->status = 'Approved';
        } elseif ($request->route()->getName() === 'admin.request.decline') {
            $itemRequest->status = 'Declined';
        }

        $itemRequest->save();

        return back()->with('success', 'Request has been ' . strtolower($itemRequest->status) . '.');
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
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

    /**
     * Display the specified resource.
     */
    public function show(ItemRequest $itemRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ItemRequest $itemRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ItemRequest $itemRequest)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:item_requests,id',
            'quantity' => 'required|integer|min:1',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $itemRequest = ItemRequest::findOrFail($validated['id']);

        // A borrower may only update their own requests.
        if ($itemRequest->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $itemRequest->update([
            'quantity' => $validated['quantity'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Request has been updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
        $itemRequest = ItemRequest::findOrFail($id);

        // A borrower may only delete their own requests.
        if ($itemRequest->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $itemRequest->delete();

        return redirect()
            ->back()
            ->with('success', 'Your item request has been deleted successfully.');
    }
}
