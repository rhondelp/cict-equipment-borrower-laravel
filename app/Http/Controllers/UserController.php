<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Equipment;
use App\Models\User as UserModel;
use App\Support\OfficeHours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $inventory = $this->inventorySummary();

        return view('login', compact('inventory'));
    }

    public function welcome()
    {
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
        $loanDays = (int) config('office.loan_days', 1);

        return view('welcome', compact('inventory', 'shelf', 'hours', 'loanDays'));
    }

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
        $users = UserModel::with(['classSchedules' => fn ($query) => $query->withCount('borrowTransactions')])
            ->withCount(['borrowTransactions', 'itemRequests', 'classSchedules'])
            ->withSum([
                'borrowTransactions as units_out' => fn ($query) => $query->out(),
            ], 'quantity')
            ->withCount([
                'borrowTransactions as overdue_count' => fn ($query) => $query->overdue(),
                'itemRequests as pending_requests_count' => fn ($query) => $query->where('status', 'Pending'),
            ])
            ->with(['suspender', 'roleOverrider'])
            ->orderBy('name')
            ->get();

        $instructors = UserModel::where('user_type', 'Instructor')->orderBy('name')->get();

        $needsAttention = $users->filter(
            fn ($user) => $user->isDeactivated() || $user->isSuspended()
        )->values();

        $instructorRequests = $users->filter(
            fn ($user) => $user->hasPendingInstructorRequest() && ! $user->isDeactivated()
        )->sortBy(fn ($user) => $user->instructor_requested_at->timestamp)->values();

        return view('admin.user', compact('users', 'instructors', 'needsAttention', 'instructorRequests'));
    }

    public function update(Request $request)
    {
        $userId = $request->id;

        $validated = $request->validate([
            'user_type' => 'required|in:Admin,Instructor,Student',
            'name' => 'required|string|max:255',
            'email' => "required|string|email|max:255|unique:users,email,{$userId}",
            'password' => 'nullable|string|min:4|confirmed',
            'contact_number' => 'nullable|string|max:15',
            'course' => 'nullable|string|max:50',
            'year_level' => ['nullable', Rule::in(UserModel::YEAR_LEVELS)],
            'role_override_reason' => 'nullable|string|max:500',
        ], [
            'year_level.in' => 'Choose a year level from the list.',
        ]);

        $user = UserModel::findOrFail($userId);

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

        $isStudent = $validated['user_type'] === 'Student';
        $user->course = $isStudent ? ($validated['course'] ?? null) : null;
        $user->year_level = $isStudent ? ($validated['year_level'] ?? null) : null;

        if ($roleChanged) {
            $user->user_type = $validated['user_type'];
            $user->role_overridden_at = now();
            $user->role_overridden_by = auth()->id();
            $user->role_override_reason = $validated['role_override_reason'];
            $user->instructor_requested_at = null;
        }

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $changes = ActivityLog::changes($before, $this->accountSnapshot($user));
        $passwordSet = ! empty($validated['password']);
        if ($changes !== [] || $passwordSet) {
            $words = array_filter([ActivityLog::changeWords($changes, ['contact_number' => 'contact number', 'year_level' => 'year level']), $passwordSet ? 'password reset by an admin' : null]);
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

    private function accountSnapshot(UserModel $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'contact_number' => $user->contact_number,
            'course' => $user->course,
            'year_level' => $user->year_level,
        ];
    }

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
            ? ' '.$out.' '.str('unit')->plural($out).' still with them — those borrowed units stay tracked.'
            : '';

        return redirect()->back()->with('success', $user->name.' deactivated and can no longer sign in.'.$note);
    }

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
            $user->name.' suspended — they can still sign in and see their borrowed items, but cannot borrow.');
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

    public function destroy(string $id)
    {
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
