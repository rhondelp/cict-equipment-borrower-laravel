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

    public function destroy($id)
    {
        $schedule = ClassSchedule::findOrFail($id);

        // borrow_transactions.class_schedule_id is onDelete('set null'), so any
        // transaction pointing here keeps its row and simply loses the link.
        $schedule->delete();

        return redirect()->back()->with('success', 'Class schedule deleted successfully.');
    }
}
