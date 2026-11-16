<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
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

        $schedule = ClassSchedule::create($validated);

        ActivityLog::record('schedule_added', [
            'subject' => $schedule->instructor,
            'details' => 'Added '.$this->label($schedule).' for '.($schedule->instructor->name ?? 'a deleted account').'.',
            'meta' => ['schedule_id' => $schedule->id] + $this->snapshot($schedule),
        ]);

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
        $before = $this->snapshot($schedule);
        $schedule->update(collect($validated)->except('id')->all());
        $schedule->load('instructor');

        $changes = ActivityLog::changes($before, $this->snapshot($schedule));
        if ($changes !== []) {
            ActivityLog::record('schedule_updated', [
                'subject' => $schedule->instructor,
                'details' => 'Edited '.$this->label($schedule).': '.ActivityLog::changeWords($changes).'.',
                'meta' => ['schedule_id' => $schedule->id, 'changes' => $changes],
            ]);
        }

        return redirect()->back()->with('success', 'Class schedule updated successfully.');
    }

    private function label(ClassSchedule $schedule): string
    {
        return $schedule->subject_code.' — '.$schedule->subject_name.' ('.$schedule->year_level.' '.$schedule->block_name.')';
    }

    private function snapshot(ClassSchedule $schedule): array
    {
        return [
            'instructor' => $schedule->instructor?->name,
            'year_level' => $schedule->year_level,
            'block_name' => $schedule->block_name,
            'subject_code' => $schedule->subject_code,
            'subject_name' => $schedule->subject_name,
            'schedule_time' => $schedule->schedule_time,
            'room' => $schedule->room,
        ];
    }

    public function destroy($id)
    {
        $schedule = ClassSchedule::withCount('borrowTransactions')->findOrFail($id);

        if ($schedule->borrow_transactions_count > 0) {
            return redirect()->back()->with('error',
                'Cannot delete '.$schedule->subject_code.' — '.$schedule->borrow_transactions_count.' '
                .str('borrow')->plural($schedule->borrow_transactions_count).' '
                .($schedule->borrow_transactions_count === 1 ? 'is' : 'are').' recorded against it.');
        }

        $label = $schedule->subject_code.' — '.$schedule->subject_name;
        $schedule->delete();

        ActivityLog::record('schedule_deleted', [
            'subject' => $schedule->instructor,
            'details' => 'Removed '.$this->label($schedule).' from '.($schedule->instructor->name ?? 'a deleted account').'.',
            'meta' => ['schedule_id' => $schedule->id] + $this->snapshot($schedule),
        ]);

        return redirect()->back()->with('success', $label.' removed. No borrows referenced it.');
    }
}
