<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipmentController extends Controller
{
    public function index()
    {
        $equipment = $this->withLoanAggregates()->orderBy('equipment_name')->get();

        return view('admin.equipment', compact('equipment'));
    }

    /**
     * `available_quantity` and `status` are no longer on the form. Both are
     * derived from the loans, so a new item starts fully available and the
     * status follows from the stock.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipment_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'quantity' => 'required|integer|min:1',
        ]);

        $equipment = new Equipment($validated);
        $equipment->available_quantity = $validated['quantity'];
        $equipment->status = $equipment->lendableStatus();
        $equipment->save();

        return redirect()->back()->with('success', $equipment->equipment_name.' added — '.$validated['quantity'].' of '.$validated['quantity'].' available.');
    }

    /**
     * Only the total owned is editable, and it cannot be pushed below the units
     * that are out with borrowers: "3 units are out on loan" is a fact about
     * the loans, not a number an admin gets to overrule. Availability is then
     * recomputed as total − out, which also repairs any drift.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:equipment,id',
            'equipment_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'quantity' => 'required|integer|min:0',
        ]);

        $result = DB::transaction(function () use ($validated) {
            $equipment = Equipment::where('id', $validated['id'])->lockForUpdate()->firstOrFail();
            $out = $equipment->unitsOut();

            if ($validated['quantity'] < $out) {
                return ['error' => $out.' '.str('unit')->plural($out).' of '.$equipment->equipment_name
                    .' '.($out === 1 ? 'is' : 'are').' still out on loan, so the total cannot go below '.$out.'. Check them in first.'];
            }

            $equipment->equipment_name = $validated['equipment_name'];
            $equipment->description = $validated['description'] ?? null;
            $equipment->quantity = $validated['quantity'];
            $equipment->available_quantity = $validated['quantity'] - $out;
            $equipment->status = $equipment->lendableStatus();
            $equipment->save();

            return ['equipment' => $equipment];
        });

        if (isset($result['error'])) {
            return redirect()->back()->withErrors(['quantity' => $result['error']])->withInput();
        }

        $equipment = $result['equipment'];

        return redirect()->back()->with('success', $equipment->equipment_name.' updated — now reads '
            .$equipment->available_quantity.' of '.$equipment->quantity.' available.');
    }

    /**
     * The non-destructive default offered by the remove dialog. The item stops
     * being lendable; every loan, request and return log that names it stays
     * readable, and units already out stay tracked until they come back.
     */
    public function retire($id)
    {
        $equipment = Equipment::findOrFail($id);

        if ($equipment->isRetired()) {
            return redirect()->back()->with('error', $equipment->equipment_name.' is already retired.');
        }

        $out = $equipment->unitsOut();
        $equipment->retired_at = now();
        $equipment->status = $equipment->lendableStatus();
        $equipment->save();

        $note = $out > 0
            ? ' '.$out.' '.str('unit')->plural($out).' still out — the loans stay tracked.'
            : '';

        return redirect()->back()->with('success', $equipment->equipment_name.' retired and is no longer lendable.'.$note);
    }

    public function restore($id)
    {
        $equipment = Equipment::findOrFail($id);
        $equipment->retired_at = null;
        $equipment->status = $equipment->lendableStatus();
        $equipment->save();

        return redirect()->back()->with('success', $equipment->equipment_name.' is lendable again.');
    }

    /**
     * Hard delete, refused while anything points at the row. The cascade on
     * borrow_transactions and item_requests would take the history with it,
     * which is exactly what the confirm dialog promises will not happen.
     */
    public function destroy($id)
    {
        $equipment = Equipment::findOrFail($id);
        $out = $equipment->unitsOut();

        if ($out > 0) {
            return redirect()->back()->with('error',
                'Cannot delete '.$equipment->equipment_name.' — '.$out.' '.str('unit')->plural($out)
                .' '.($out === 1 ? 'is' : 'are').' still out with borrowers. Retire it instead.');
        }

        $references = $equipment->historyCount();
        if ($references > 0) {
            return redirect()->back()->with('error',
                'Cannot delete '.$equipment->equipment_name.' — '.$references.' '.str('record')->plural($references)
                .' reference it. Retire it instead to keep the history.');
        }

        $name = $equipment->equipment_name;
        $equipment->delete();

        return redirect()->back()->with('success', $name.' deleted. It had no loan history.');
    }

    /**
     * One query for the whole list: units out per item, plus the reference
     * counts the remove dialog quotes. Without these the equipment table fired
     * three queries a row to draw a single confirm.
     */
    private function withLoanAggregates()
    {
        return Equipment::query()
            ->withCount(['borrowTransactions', 'itemRequests'])
            ->withSum([
                'borrowTransactions as units_out' => fn ($query) => $query->whereNull('voided_at')
                    ->whereIn('status', ['Borrowed', 'Overdue']),
            ], 'quantity');
    }
}
