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
}
