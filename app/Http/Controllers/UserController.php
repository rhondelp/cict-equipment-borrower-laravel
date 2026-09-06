<?php

namespace App\Http\Controllers;

use App\Models\User as UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return view('login');
    }

    public function adminUser()
    {
        $users = UserModel::with('classSchedules')->get();
        $instructors = UserModel::where('user_type', 'Instructor')->get();

        return view('admin.user', compact('users', 'instructors'));
    }

    public function show(string $id)
    {
        $user = UserModel::findOrFail($id);

        return view('user.show', compact('user'));
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

        return redirect()->back()->with('success', 'User updated successfully');
    }

    public function destroy(string $id)
    {
        // Prevent an admin from deleting their own account while logged in.
        if ((int) $id === (int) auth()->id()) {
            return redirect()->back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $user = UserModel::findOrFail($id);
        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully.');
    }
}
