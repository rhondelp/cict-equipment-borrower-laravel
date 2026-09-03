<?php
namespace App\Http\Controllers;

use App\Models\User as UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
// Import with alias

class User extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('login');
    }

    public function adminUser()
    {
        $users = UserModel::with('classSchedules')->get();
    $instructors = UserModel::where('user_type', 'Instructor')->get();

    dd($instructors);



        // return view('admin.user', compact('users', 'instructors'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = UserModel::findOrFail($id); // Use the alias
        return view('user.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $userId = $request->id;

        // Validate input, ignoring unique email check for the current user
        $validated = $request->validate([
            'user_type' => 'required|in:Admin,Instructor,Student',
            'name'      => 'required|string|max:255',
            'email'     => "required|string|email|max:255|unique:users,email,{$userId}",
            'password'       => 'nullable|string|min:4|confirmed',
            'contact_number' => 'nullable|string|max:15',
        ]);

        $user = UserModel::findOrFail($userId);
    // Update the user fields
        $user->name           = $validated['name'];
        $user->email          = $validated['email'];
        $user->user_type      = $validated['user_type'];
        $user->contact_number = $validated['contact_number'] ?? null;


        // If password is provided, hash and update it
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->back()->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
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
