<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use Illuminate\Http\Request;

class ClassScheduleController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'year_level' => 'required|string|max:255',
            'block_name' => 'required|string|max:255',
            'subject_code' => 'required|string|max:255',
            'subject_name' => 'required|string|max:255',
            'schedule_time' => 'required|string|max:255',
            'room' => 'required|string|max:255',
        ]);

        ClassSchedule::create($validated);

        return redirect()->back()->with('success', 'Class schedule added successfully.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:class_schedules,id',
            'user_id' => 'required|exists:users,id',
            'year_level' => 'required|string|max:255',
            'block_name' => 'required|string|max:255',
            'subject_code' => 'required|string|max:255',
            'subject_name' => 'required|string|max:255',
            'schedule_time' => 'required|string|max:255',
            'room' => 'required|string|max:255',
        ]);

        $schedule = ClassSchedule::findOrFail($validated['id']);
        $schedule->update(collect($validated)->except('id')->all());

        return redirect()->back()->with('success', 'Class schedule updated successfully.');
    }

    /**
     * borrow_transactions.class_schedule_id is onDelete('set null'), so a
     * delete here does not destroy a loan — but it does silently erase which
     * class each of those loans was for. That is history, so the delete is
     * refused while any loan still points here.
     */
    public function destroy($id)
    {
        $schedule = ClassSchedule::withCount('borrowTransactions')->findOrFail($id);

        if ($schedule->borrow_transactions_count > 0) {
            return redirect()->back()->with('error',
                'Cannot delete '.$schedule->subject_code.' — '.$schedule->borrow_transactions_count.' '
                .str('loan')->plural($schedule->borrow_transactions_count).' '
                .($schedule->borrow_transactions_count === 1 ? 'is' : 'are').' recorded against it.');
        }

        $label = $schedule->subject_code.' — '.$schedule->subject_name;
        $schedule->delete();

        return redirect()->back()->with('success', $label.' removed. No loans referenced it.');
    }
}
