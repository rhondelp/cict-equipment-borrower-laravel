<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        // return view('admin.equipment');
        $equipment = Equipment::all();
        return view('admin.equipment', compact('equipment'));

    }

    public function availableEquipment()
    {
        $availableEquipments = Equipment::where('status', 'Available')
            ->where('available_quantity', '>', 0)
            ->get();
        return view('admin.transaction', compact('availableEquipments'));
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
        //$
        $validated = $request->validate([
            'equipment_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'quantity' => 'required|integer|min:0',
            'available_quantity' => 'required|integer|min:0|lte:quantity',
            'status' => 'required|in:Available,Unavailable',
        ]);
        Equipment::create($validated);
        return redirect()->back()->with('success', 'Equipment added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Equipment $equipment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Equipment $equipment)
    {
        //

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        //
        $validated = $request->validate([
            'id' => 'required|integer|exists:equipment,id',
            'equipment_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'quantity' => 'required|integer|min:0',
            'available_quantity' => 'required|integer|min:0|lte:quantity',
            'status' => 'required|in:Available,Unavailable',
        ]);

        $equipment = Equipment::findOrFail($validated['id']);
        $equipment->update(collect($validated)->except('id')->all());

        return redirect()->back()->with('success', 'Equipment updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
        $equipment = Equipment::findOrFail($id);
        $equipment->delete();
        return redirect()->back()->with('success', 'Equipment deleted successfully.');
    }
}
