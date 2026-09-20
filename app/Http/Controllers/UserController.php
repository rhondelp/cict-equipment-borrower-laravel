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
            ])
            ->orderBy('name')
            ->get();

        $instructors = UserModel::where('user_type', 'Instructor')->orderBy('name')->get();

        return view('admin.user', compact('users', 'instructors'));
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
        ]);

        $user = UserModel::findOrFail($userId);
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->user_type = $validated['user_type'];
        $user->contact_number = $validated['contact_number'] ?? null;

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
