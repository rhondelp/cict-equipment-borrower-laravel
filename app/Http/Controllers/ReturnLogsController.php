<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\ReturnLog;
use App\Models\ReturnLogNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The return log — an audit record, read when something is damaged, missing or
 * disputed rather than browsed.
 *
 * Two properties hold this screen up, and both are enforced here rather than by
 * leaving buttons off a template:
 *
 *  - **A log row is immutable.** There is no update and no destroy action on
 *    this controller, and no route to one. A correction is appended as a note
 *    carrying its own author and timestamp, so the original reading survives.
 *  - **An incident is open until someone records what was done.** That is what
 *    lets the screen lead with what still needs follow-up instead of showing a
 *    reverse-chronological wall in which a damaged return from March and one
 *    from this morning look identical.
 */
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

        // Split rather than sorted: these are two different readings of the
        // same table. The top one is work; the bottom one is history.
        $followUp = $logs->filter(fn ($log) => $log->needsFollowUp())->values();
        $archive = $logs->reject(fn ($log) => $log->needsFollowUp())->values();

        // A return with no receiving staff member is an incomplete record —
        // nobody can be asked what condition it arrived in.
        $incomplete = $logs->filter(fn ($log) => $log->receiver === null)->values();

        return view('admin.logs', compact('logs', 'followUp', 'archive', 'incomplete'));
    }

    /**
     * Every return ever recorded for one item, so a pattern of damage is
     * visible. A single damaged return is an accident; the fourth is a fact
     * about the equipment, and it is invisible on a date-ordered list.
     */
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

    /**
     * Record what was done about an incident. This closes the follow-up, and is
     * the one field on a log row that is written after the fact — it is not a
     * correction to the record, it is an outcome attached to it.
     */
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

        return back()->with('success', 'Resolution recorded. The entry moves out of the follow-up list.');
    }

    /**
     * Append a correction. Never an edit: the row keeps whatever it originally
     * said, and the note sits beside it with who wrote it and when.
     */
    public function addNote(Request $request, $id)
    {
        $validated = $request->validate([
            'body' => 'required|string|min:3|max:1000',
        ], [
            'body.required' => 'Write the correction — the original entry is not changed.',
        ]);

        $log = ReturnLog::findOrFail($id);

        ReturnLogNote::create([
            'return_log_id' => $log->id,
            'user_id' => Auth::id(),
            'body' => $validated['body'],
        ]);

        return back()->with('success', 'Note added. The original entry is unchanged.');
    }
}
