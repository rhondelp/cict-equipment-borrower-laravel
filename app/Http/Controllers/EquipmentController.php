<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index()
    {
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

    public function store(Request $request)
    {
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

    public function update(Request $request)
    {
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

    public function destroy($id)
    {
        $equipment = Equipment::findOrFail($id);
        $equipment->delete();

        return redirect()->back()->with('success', 'Equipment deleted successfully.');
    }
}
