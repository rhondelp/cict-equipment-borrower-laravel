<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Equipment;
use App\Models\ReturnLog;
use App\Models\ReturnLogNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReturnLogsController extends Controller
{
    public function index()
    {
        $logs = ReturnLog::with([
            'borrower', 'receiver', 'equipment', 'borrowTransaction', 'resolver', 'notes.author',
        ])
            ->orderBy('return_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $followUp = $logs->filter(fn ($log) => $log->needsFollowUp())->values();
        $archive = $logs->reject(fn ($log) => $log->needsFollowUp())->values();

        $incomplete = $logs->filter(fn ($log) => $log->receiver === null)->values();

        return view('admin.logs', compact('logs', 'followUp', 'archive', 'incomplete'));
    }

    public function itemHistory($equipmentId)
    {
        $equipment = Equipment::findOrFail($equipmentId);

        $logs = ReturnLog::with(['borrower', 'receiver', 'borrowTransaction', 'resolver', 'notes.author'])
            ->whereHas('borrowTransaction', fn ($query) => $query->where('equipment_id', $equipment->id))
            ->orderBy('return_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $incidents = $logs->filter(fn ($log) => $log->isIncident());

        return view('admin.logs-item', [
            'equipment' => $equipment,
            'logs' => $logs,
            'incidents' => $incidents,
            'unresolved' => $incidents->filter(fn ($log) => ! $log->isResolved())->count(),
        ]);
    }

    public function resolve(Request $request, $id)
    {
        $validated = $request->validate([
            'resolution' => 'required|string|min:5|max:1000',
        ], [
            'resolution.required' => 'Say what was done about it — this is the only record of the outcome.',
            'resolution.min' => 'Give a usable account of the outcome (at least 5 characters).',
        ]);

        $log = ReturnLog::findOrFail($id);

        if (! $log->isIncident()) {
            return back()->with('error', 'That return was logged in good condition — there is nothing to resolve.');
        }

        if ($log->isResolved()) {
            return back()->with('error', 'That incident already has a resolution recorded. Add a note instead.');
        }

        $log->resolution = $validated['resolution'];
        $log->resolved_at = now();
        $log->resolved_by = Auth::id();
        $log->save();

        ActivityLog::record('return_incident_resolved', [
            'loan' => $log->borrowTransaction,
            'quantity' => $log->borrowTransaction?->quantity,
            'status_from' => 'Unresolved',
            'status_to' => 'Resolved',
            'details' => 'Resolved: '.$validated['resolution'],
            'meta' => ['return_log_id' => $log->id, 'condition' => $log->condition, 'resolution' => $validated['resolution']],
        ]);

        return back()->with('success', 'Resolution recorded. The entry moves out of the follow-up list.');
    }

    public function addNote(Request $request, $id)
    {
        $validated = $request->validate([
            'body' => 'required|string|min:3|max:1000',
        ], [
            'body.required' => 'Write the correction — the original entry is not changed.',
        ]);

        $log = ReturnLog::findOrFail($id);

        $note = ReturnLogNote::create([
            'return_log_id' => $log->id,
            'user_id' => Auth::id(),
            'body' => $validated['body'],
        ]);

        ActivityLog::record('return_note_added', [
            'loan' => $log->borrowTransaction,
            'details' => 'Correction: '.$validated['body'],
            'meta' => ['return_log_id' => $log->id, 'note_id' => $note->id],
        ]);

        return back()->with('success', 'Note added. The original entry is unchanged.');
    }
}
