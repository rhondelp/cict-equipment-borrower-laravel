<?php

namespace App\Http\Controllers;

use App\Models\BorrowTransaction;
use App\Models\Equipment;
use App\Models\ItemRequest;
use App\Models\Notification;
use App\Models\ReturnLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticateUser extends Controller
{
    public function adminView()
    {
        $equipments = Equipment::with(['borrowTransactions', 'itemRequests'])->get();
        $users = User::with(['borrowTransactions', 'itemRequests'])->get();
        $transactions = BorrowTransaction::with(['user', 'equipment'])->get();
        $requests = ItemRequest::with(['user', 'equipment'])->get();
        $returnLogs = ReturnLog::with(['borrower', 'receiver', 'equipment'])->latest()->get();

        return view('admin.dashboard', compact('equipments', 'users', 'transactions', 'requests', 'returnLogs'));
    }

    public function borrowerView()
    {
        $userId = Auth::id();
        $requests = ItemRequest::where('user_id', $userId)->with('equipment')->get();
        $transactions = BorrowTransaction::where('user_id', $userId)->with('equipment')->get();
        $equipments = Equipment::all();
        // Read-only feed for the dashboard bell. Rows are written elsewhere by
        // BorrowTransactionController::sendReturnAlertNotification.
        $notifications = Notification::where('user_id', $userId)->latest()->take(10)->get();

        return view('borrower.dashboard', compact('requests', 'transactions', 'equipments', 'notifications'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            if (Auth::attempt($credentials)) {
                $request->session()->regenerate();

                $user = Auth::user();
                $msg = 'Welcome back, '.$user->name.'!';
                // FIX: flash both 'welcome' (legacy) and 'success' so shared alerts + existing checks show it
                $request->session()->flash('welcome', $msg);
                $request->session()->flash('success', $msg);

                if ($user->user_type === 'Admin') {
                    return redirect()->intended(route('admin.dashboard'));
                } else {
                    return redirect()->intended(route('borrower.dashboard'));
                }
            }

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');

        } catch (\Exception $e) {
            \Log::error('Login error: '.$e->getMessage());

            return back()->withErrors([
                'email' => 'Something went wrong. Please try again later.',
            ]);
        }
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function registerUser()
    {
        return view('register');
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'user_type' => 'required|in:Instructor,Student',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:4|confirmed',
            'contact_number' => 'nullable|string|max:15',
        ]);

        $user = User::create([
            'user_type' => $validatedData['user_type'],
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'contact_number' => $validatedData['contact_number'] ?? null,
        ]);

        return redirect()->back()->with('success', 'User added successfully!');
    }
}

