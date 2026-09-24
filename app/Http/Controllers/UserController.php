<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\User as UserModel;
use Illuminate\Http\Request;
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
        try {
            $lendable = Equipment::lendable()->get(['quantity']);
            $inventory = [
                'units' => (int) $lendable->sum('quantity'),
                'types' => $lendable->count(),
            ];
        } catch (\Throwable $e) {
            $inventory = null;
        }

        return view('login', compact('inventory'));
    }

    public function adminUser()
    {
        // The aggregates the rows and the remove dialog quote — loaded once for
        // the whole list rather than three queries per row.
        $users = UserModel::with(['classSchedules' => fn ($query) => $query->withCount('borrowTransactions')])
            ->withCount(['borrowTransactions', 'itemRequests', 'classSchedules'])
            ->withSum([
                'borrowTransactions as units_out' => fn ($query) => $query->whereNull('voided_at')
                    ->whereIn('status', ['Borrowed', 'Overdue']),
            ], 'quantity')
            ->withCount([
                'borrowTransactions as overdue_count' => fn ($query) => $query->whereNull('voided_at')
                    ->whereIn('status', ['Borrowed', 'Overdue'])
                    ->whereDate('return_date', '<', now()->toDateString()),
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

        return view('admin.user', compact('users', 'instructors', 'needsAttention'));
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
            // Required only when the submitted role contradicts the address.
            'role_override_reason' => 'nullable|string|max:500',
        ]);

        $user = UserModel::findOrFail($userId);

        // Role is derived from the school email domain. An admin may still set
        // it by hand — they are behind userType:Admin, and sometimes the
        // register is simply wrong — but overriding the derivation is a
        // privilege decision, so it takes a reason and it is recorded. An
        // unexplained role change is the one edit on this screen nobody can
        // reconstruct afterwards.
        $derived = UserModel::roleForEmail($validated['email']);
        $isOverride = $derived !== null && $derived !== $validated['user_type'];

        if ($isOverride && blank($validated['role_override_reason'] ?? null)) {
            return redirect()->back()
                ->withErrors(['role_override_reason' => 'That email implies '.$derived
                    .'. Say why this account is '.$validated['user_type'].' instead — the reason is recorded.'])
                ->withInput();
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->contact_number = $validated['contact_number'] ?? null;

        if ($isOverride) {
            $user->user_type = $validated['user_type'];
            $user->role_overridden_at = now();
            $user->role_overridden_by = auth()->id();
            $user->role_override_reason = $validated['role_override_reason'];
        } else {
            // Back in step with the address, so the override record goes too.
            $user->user_type = $derived ?? $validated['user_type'];
            $user->role_overridden_at = null;
            $user->role_overridden_by = null;
            $user->role_override_reason = null;
        }

        // If password is provided, hash and update it
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->back()->with('success', $user->name.' updated.');
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

        return redirect()->back()->with('success',
            $user->name.' suspended — they can still sign in and see their loans, but cannot borrow.');
    }

    public function liftSuspension(string $id)
    {
        $user = UserModel::findOrFail($id);

        if (! $user->isSuspended()) {
            return redirect()->back()->with('error', $user->name.' is not suspended.');
        }

        $user->suspended_at = null;
        $user->suspension_reason = null;
        $user->suspended_by = null;
        $user->save();

        return redirect()->back()->with('success', $user->name.' can borrow again.');
    }

    public function reactivate(string $id)
    {
        $user = UserModel::findOrFail($id);
        $user->deactivated_at = null;
        $user->save();

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
        $user->delete();

        return redirect()->back()->with('success', $name.' deleted. They had no borrowing history.');
    }
}
