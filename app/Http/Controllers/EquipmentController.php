<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    public function index()
    {
        $equipment = $this->withLoanAggregates()->orderBy('equipment_name')->get();
        $categories = Equipment::categoriesInUse();

        return view('admin.equipment', compact('equipment', 'categories'));
    }

    /**
     * `available_quantity` and `status` are no longer on the form. Both are
     * derived from the loans, so a new item starts fully available and the
     * status follows from the stock.
     *
     * `loan_type` is required, but a post without it (an older form, a
     * script) means Returnable, which is what every item was before the
     * field existed.
     */
    public function store(Request $request)
    {
        $request->mergeIfMissing(['loan_type' => Equipment::LOAN_RETURNABLE]);

        $validated = $request->validate([
            'equipment_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:60',
            'loan_type' => ['required', Rule::in(array_keys(Equipment::LOAN_TYPES))],
            'quantity' => 'required|integer|min:1',
        ]);
        $validated['category'] = Equipment::canonicalCategory($validated['category'] ?? null);

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
     * recomputed as total − out − issued, which also repairs any drift.
     *
     * The loan type can change only while nothing is out. A loan already out
     * was made under the old type's terms, and switching underneath it would
     * leave it due by a date it was never given, or turn it into a hand-over
     * nobody recorded. A post without `loan_type` keeps the current one,
     * rather than quietly resetting the item to Returnable.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:equipment,id',
            'equipment_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:60',
            'loan_type' => ['sometimes', 'required', Rule::in(array_keys(Equipment::LOAN_TYPES))],
            'quantity' => 'required|integer|min:0',
        ]);
        $validated['category'] = Equipment::canonicalCategory($validated['category'] ?? null);

        $result = DB::transaction(function () use ($validated) {
            $equipment = Equipment::where('id', $validated['id'])->lockForUpdate()->firstOrFail();
            $out = $equipment->unitsOut();
            $loanType = $validated['loan_type'] ?? $equipment->loan_type;

            if ($loanType !== $equipment->loan_type && $out > 0) {
                return ['field' => 'loan_type', 'error' => $equipment->equipment_name.' has '.$out.' '.str('unit')->plural($out)
                    .' out on loan, so its loan type cannot change from '.$equipment->loanTypeLabel()
                    .' to '.Equipment::LOAN_TYPES[$loanType].'. Check '.($out === 1 ? 'it' : 'them').' in first.'];
            }

            if ($validated['quantity'] < $out) {
                return ['error' => $out.' '.str('unit')->plural($out).' of '.$equipment->equipment_name
                    .' '.($out === 1 ? 'is' : 'are').' still out on loan, so the total cannot go below '.$out.'. Check them in first.'];
            }

            // Issued units left for good but still count in the total owned.
            $issued = $equipment->unitsIssued();
            if ($validated['quantity'] < $out + $issued) {
                return ['error' => $issued.' '.str('unit')->plural($issued).' of '.$equipment->equipment_name
                    .' '.($issued === 1 ? 'has' : 'have').' been issued and '.$out.' '.($out === 1 ? 'is' : 'are')
                    .' out on loan, so the total cannot go below '.($out + $issued).'.'];
            }

            $equipment->equipment_name = $validated['equipment_name'];
            $equipment->description = $validated['description'] ?? null;
            $equipment->category = $validated['category'];
            $equipment->loan_type = $loanType;
            $equipment->quantity = $validated['quantity'];
            $equipment->available_quantity = $validated['quantity'] - $out - $issued;
            $equipment->status = $equipment->lendableStatus();
            $equipment->save();

            return ['equipment' => $equipment];
        });

        if (isset($result['error'])) {
            return redirect()->back()->withErrors([$result['field'] ?? 'quantity' => $result['error']])->withInput();
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
            ], 'quantity')
            ->withSum([
                'borrowTransactions as units_issued' => fn ($query) => $query->whereNull('voided_at')
                    ->where('status', 'Issued'),
            ], 'quantity');
    }
}
