<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Equipment;
use App\Models\User as UserModel;
use App\Support\OfficeHours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * The sign-in page.
     *
     * The two figures on the brand panel are read live rather than written
     * into the template: a number that says "89 units" while the shelf holds
     * 40 is decoration, and the point of that panel is to orient someone who
     * has not signed in yet.
     *
     * Wrapped because this is the one page that has to render when the
     * database does not answer — the sign-in form itself needs nothing from
     * it, so a failure here drops the line instead of the page.
     */
    public function index()
    {
        $inventory = $this->inventorySummary();

        return view('login', compact('inventory'));
    }

    /**
     * The landing page. Same live inventory line as the sign-in page, from the
     * same query, so the two public pages can never quote different figures.
     */
    public function welcome()
    {
        // One read of the lendable stock feeds both the shelf card and the
        // figures band, classified exactly as the inventory page classifies it:
        // retired items excluded, state from Equipment::availabilityState().
        // Dropped, not faked, when the database does not answer.
        try {
            $lendable = Equipment::lendable()
                ->get(['id', 'equipment_name', 'quantity', 'available_quantity', 'retired_at']);
        } catch (\Throwable $e) {
            $lendable = null;
        }

        $inventory = $lendable === null ? null : [
            'units' => (int) $lendable->sum('quantity'),
            'types' => $lendable->count(),
        ];

        // At most five rows, and the ones someone should know about first:
        // nothing left, then running low, then partly out, then fully stocked.
        // Within a state the scarcest leads, then by name for a stable order.
        $priority = ['out' => 0, 'low' => 1, 'partial' => 2, 'all-in' => 3];
        $shelf = ($lendable ?? collect())
            ->map(fn ($item) => ['item' => $item, 'state' => $item->availabilityState()])
            ->sortBy([
                fn ($a, $b) => ($priority[$a['state']['key']] ?? 9) <=> ($priority[$b['state']['key']] ?? 9),
                fn ($a, $b) => ($a['item']->available_quantity / max(1, $a['item']->quantity))
                    <=> ($b['item']->available_quantity / max(1, $b['item']->quantity)),
                fn ($a, $b) => strcmp($a['item']->equipment_name, $b['item']->equipment_name),
            ])
            ->take(5)
            ->values();

        $hours = OfficeHours::fromConfig();
        $loanDays = (int) config('office.loan_days', 7);

        return view('welcome', compact('inventory', 'shelf', 'hours', 'loanDays'));
    }

    /**
     * Units and item types currently lendable, or null when the database does
     * not answer — the public pages drop the line rather than the page.
     *
     * @return array{units: int, types: int}|null
     */
    private function inventorySummary(): ?array
    {
        try {
            $lendable = Equipment::lendable()->get(['quantity']);

            return [
                'units' => (int) $lendable->sum('quantity'),
                'types' => $lendable->count(),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function adminUser()
    {
        // The aggregates the rows and the remove dialog quote — loaded once for
        // the whole list rather than three queries per row.
        $users = UserModel::with(['classSchedules' => fn ($query) => $query->withCount('borrowTransactions')])
            ->withCount(['borrowTransactions', 'itemRequests', 'classSchedules'])
            ->withSum([
                'borrowTransactions as units_out' => fn ($query) => $query->out(),
            ], 'quantity')
            ->withCount([
                // dueAt() as SQL: a timed loan is late by its time, a date-only
                // one by its day, and an Issued row never.
                'borrowTransactions as overdue_count' => fn ($query) => $query->overdue(),
                // A person's standing is not just what they hold — it is also
                // what they are waiting on. Counted here so the row can say it
                // without firing a query of its own.
                'itemRequests as pending_requests_count' => fn ($query) => $query->where('status', 'Pending'),
            ])
            ->with(['suspender', 'roleOverrider'])
            ->orderBy('name')
            ->get();

        $instructors = UserModel::where('user_type', 'Instructor')->orderBy('name')->get();

        // The actionable list. There is no account-approval gate in this
        // system — registration creates a working account — so the accounts
        // that need a decision are the ones a human has already acted on:
        // closed, or allowed to sign in but stopped from borrowing.
        $needsAttention = $users->filter(
            fn ($user) => $user->isDeactivated() || $user->isSuspended()
        )->values();

        // Signed up saying they teach. Their accounts already work as Student
        // accounts; an admin confirms or declines each one here.
        $instructorRequests = $users->filter(
            fn ($user) => $user->hasPendingInstructorRequest() && ! $user->isDeactivated()
        )->sortBy(fn ($user) => $user->instructor_requested_at->timestamp)->values();

        return view('admin.user', compact('users', 'instructors', 'needsAttention', 'instructorRequests'));
    }

    public function update(Request $request)
    {
        $userId = $request->id;

        // Validate input, ignoring unique email check for the current user
        $validated = $request->validate([
            'user_type' => 'required|in:Admin,Instructor,Student',
            'name' => 'required|string|max:255',
            'email' => "required|string|email|max:255|unique:users,email,{$userId}",
            'password' => 'nullable|string|min:4|confirmed',
            'contact_number' => 'nullable|string|max:15',
            // Required only when the submitted role differs from the stored one.
            'role_override_reason' => 'nullable|string|max:500',
        ]);

        $user = UserModel::findOrFail($userId);

        // Everyone is on the same school domain, so nothing derives a role
        // any more — it is whatever an admin sets. That makes every change a
        // privilege decision: it takes a reason and it is recorded, because an
        // unexplained role change is the one edit on this screen nobody can
        // reconstruct afterwards.
        $roleChanged = $validated['user_type'] !== $user->user_type;

        if ($roleChanged && blank($validated['role_override_reason'] ?? null)) {
            return redirect()->back()
                ->withErrors(['role_override_reason' => 'Say why this account is changing from '.$user->user_type
                    .' to '.$validated['user_type'].' — the reason is recorded.'])
                ->withInput();
        }

        $before = $this->accountSnapshot($user);
        $roleBefore = $user->user_type;

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->contact_number = $validated['contact_number'] ?? null;

        if ($roleChanged) {
            $user->user_type = $validated['user_type'];
            $user->role_overridden_at = now();
            $user->role_overridden_by = auth()->id();
            $user->role_override_reason = $validated['role_override_reason'];
            // Any role decision settles a pending instructor request.
            $user->instructor_requested_at = null;
        }

        // If password is provided, hash and update it
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // The account fields and the role are two events: an edit, and a
        // privilege decision with its own reason. A password is never logged,
        // only the fact that it was set.
        $changes = ActivityLog::changes($before, $this->accountSnapshot($user));
        $passwordSet = ! empty($validated['password']);
        if ($changes !== [] || $passwordSet) {
            $words = array_filter([ActivityLog::changeWords($changes, ['contact_number' => 'contact number']), $passwordSet ? 'password reset by an admin' : null]);
            ActivityLog::record('user_updated', [
                'subject' => $user,
                'details' => 'Edited the account of '.$user->name.': '.implode(', ', $words).'.',
                'meta' => array_filter(['changes' => $changes, 'password_changed' => $passwordSet ?: null]),
            ]);
        }

        if ($roleChanged) {
            ActivityLog::record('user_role_changed', [
                'subject' => $user,
                'status_from' => $roleBefore,
                'status_to' => $user->user_type,
                'details' => 'Role changed '.$roleBefore.' to '.$user->user_type.': '.$validated['role_override_reason'],
                'meta' => ['reason' => $validated['role_override_reason']],
            ]);
        }

        return redirect()->back()->with('success', $user->name.' updated.');
    }

    /** The account fields an admin edits, as the log records them. Never the password. */
    private function accountSnapshot(UserModel $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'contact_number' => $user->contact_number,
        ];
    }

    /**
     * Grant an instructor request made at sign-up. Recorded through the same
     * role_overridden_* columns as any other role change, so the row says who
     * switched it on and when.
     */
    public function confirmInstructor(string $id)
    {
        $user = UserModel::findOrFail($id);

        if (! $user->hasPendingInstructorRequest()) {
            return redirect()->back()->with('error', $user->name.' has no instructor request waiting.');
        }

        $user->user_type = 'Instructor';
        $user->instructor_requested_at = null;
        $user->role_overridden_at = now();
        $user->role_overridden_by = auth()->id();
        $user->role_override_reason = 'Confirmed instructor request from sign-up';
        $user->save();

        ActivityLog::record('instructor_confirmed', [
            'subject' => $user,
            'status_from' => 'Student',
            'status_to' => 'Instructor',
            'details' => 'Confirmed '.$user->name.' as an instructor, as requested at sign-up.',
        ]);

        return redirect()->back()->with('success', $user->name.' is now an instructor.');
    }

    /** Leave the account as a Student and clear the request. */
    public function declineInstructor(string $id)
    {
        $user = UserModel::findOrFail($id);

        if (! $user->hasPendingInstructorRequest()) {
            return redirect()->back()->with('error', $user->name.' has no instructor request waiting.');
        }

        $user->instructor_requested_at = null;
        $user->save();

        ActivityLog::record('instructor_declined', [
            'subject' => $user,
            'status_from' => $user->user_type,
            'status_to' => $user->user_type,
            'details' => 'Declined the instructor request of '.$user->name.'; the account stays a '.strtolower($user->user_type).'.',
        ]);

        return redirect()->back()->with('success', $user->name.' stays a student — instructor request declined.');
    }

    /**
     * The non-destructive default offered by the remove dialog. The account
     * stops working at the login screen; every loan, request and return log
     * that names this person is untouched.
     */
    public function deactivate(string $id)
    {
        if ((int) $id === (int) auth()->id()) {
            return redirect()->back()->with('error', 'You cannot deactivate your own account.');
        }

        $user = UserModel::findOrFail($id);

        if ($user->isDeactivated()) {
            return redirect()->back()->with('error', $user->name.' is already deactivated.');
        }

        $out = $user->unitsOut();
        $user->deactivated_at = now();
        $user->save();

        ActivityLog::record('user_deactivated', [
            'subject' => $user,
            'status_from' => 'Active',
            'status_to' => 'Deactivated',
            'details' => 'Deactivated the account of '.$user->name
                .($out > 0 ? '; '.$out.' '.str('unit')->plural($out).' still with them' : '').'.',
            'meta' => ['units_out' => $out],
        ]);

        $note = $out > 0
            ? ' '.$out.' '.str('unit')->plural($out).' still with them — those loans stay tracked.'
            : '';

        return redirect()->back()->with('success', $user->name.' deactivated and can no longer sign in.'.$note);
    }

    /**
     * Suspension: the account keeps working, and stops being able to borrow.
     *
     * Distinct from deactivation on purpose. Deactivating someone who owes two
     * items also locks them out of the screen that tells them what they owe,
     * which is the wrong sanction for the thing it is usually used for.
     */
    public function suspend(Request $request, string $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ], [
            'reason.required' => 'Say why — the borrower is shown this, and it is the only record of the decision.',
            'reason.min' => 'Give a usable reason (at least 5 characters).',
        ]);

        if ((int) $id === (int) auth()->id()) {
            return redirect()->back()->with('error', 'You cannot suspend your own account.');
        }

        $user = UserModel::findOrFail($id);

        if ($user->isSuspended()) {
            return redirect()->back()->with('error', $user->name.' is already suspended.');
        }

        $user->suspended_at = now();
        $user->suspension_reason = $validated['reason'];
        $user->suspended_by = auth()->id();
        $user->save();

        ActivityLog::record('user_suspended', [
            'subject' => $user,
            'status_from' => 'Active',
            'status_to' => 'Suspended',
            'details' => 'Suspended '.$user->name.' from borrowing: '.$validated['reason'],
            'meta' => ['reason' => $validated['reason']],
        ]);

        return redirect()->back()->with('success',
            $user->name.' suspended — they can still sign in and see their loans, but cannot borrow.');
    }

    public function liftSuspension(string $id)
    {
        $user = UserModel::findOrFail($id);

        if (! $user->isSuspended()) {
            return redirect()->back()->with('error', $user->name.' is not suspended.');
        }

        $reason = $user->suspension_reason;
        $user->suspended_at = null;
        $user->suspension_reason = null;
        $user->suspended_by = null;
        $user->save();

        ActivityLog::record('suspension_lifted', [
            'subject' => $user,
            'status_from' => 'Suspended',
            'status_to' => 'Active',
            'details' => 'Lifted the suspension of '.$user->name.'; they can borrow again.',
            'meta' => array_filter(['previous_reason' => $reason]),
        ]);

        return redirect()->back()->with('success', $user->name.' can borrow again.');
    }

    public function reactivate(string $id)
    {
        $user = UserModel::findOrFail($id);
        $wasDeactivated = $user->isDeactivated();
        $user->deactivated_at = null;
        $user->save();

        // Reactivating an account that was never deactivated changes nothing.
        if ($wasDeactivated) {
            ActivityLog::record('user_reactivated', [
                'subject' => $user,
                'status_from' => 'Deactivated',
                'status_to' => 'Active',
                'details' => 'Reactivated the account of '.$user->name.'; they can sign in again.',
            ]);
        }

        return redirect()->back()->with('success', $user->name.' can sign in again.');
    }

    /**
     * Hard delete, refused while anything points at the row. The FKs cascade,
     * so deleting a person with history would silently take their loans and
     * requests — and the stock those loans account for — with them.
     */
    public function destroy(string $id)
    {
        // Prevent an admin from deleting their own account while logged in.
        if ((int) $id === (int) auth()->id()) {
            return redirect()->back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $user = UserModel::findOrFail($id);
        $out = $user->unitsOut();

        if ($out > 0) {
            return redirect()->back()->with('error',
                'Cannot delete '.$user->name.' — '.$out.' '.str('unit')->plural($out).' '
                .($out === 1 ? 'is' : 'are').' still out with them. Deactivate the account instead.');
        }

        $references = $user->historyCount();
        if ($references > 0) {
            return redirect()->back()->with('error',
                'Cannot delete '.$user->name.' — '.$references.' '.str('record')->plural($references)
                .' reference them. Deactivate the account instead to keep the history.');
        }

        $name = $user->name;

        DB::transaction(function () use ($user) {
            $user->delete();

            // The row is gone: the entry names the person by snapshot and
            // keeps the id in meta, since a key to it would not save.
            ActivityLog::record('user_deleted', [
                'subject' => $user,
                'subject_user_id' => null,
                'status_from' => $user->user_type,
                'details' => 'Deleted the '.strtolower($user->user_type).' account of '.$user->name.'. They had no borrowing history.',
                'meta' => ['user_id' => $user->id, 'user_type' => $user->user_type],
            ]);
        });

        return redirect()->back()->with('success', $name.' deleted. They had no borrowing history.');
    }
}
