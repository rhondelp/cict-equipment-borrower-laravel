<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Forgot / reset password flow.
 *
 * Token generation, storage in password_reset_tokens, expiry and throttling are
 * all handled by the Password broker configured in config/auth.php — nothing
 * here is hand-rolled. Validation stays inline, matching the rest of the app.
 */
class PasswordResetController extends Controller
{
    /** Show the "email me a reset link" form. */
    public function request()
    {
        return view('forgot-password');
    }

    /** Send the reset link. */
    public function email(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
        ]);

        // Note: the broker returns INVALID_USER for an unknown address, so the
        // form reports "we can't find a user with that email address" — Laravel's
        // default, and the same thing registration already reveals via its
        // unique:users rule. Return RESET_LINK_SENT unconditionally here if the
        // flow should stop confirming which addresses are registered.
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    /** Show the "choose a new password" form. */
    public function reset(Request $request, string $token)
    {
        return view('reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /** Complete the reset. */
    public function update(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|string|email|max:255',
            // min:4 matches the existing rule used by registration and the
            // admin user form, so the whole app agrees on password length.
            'password' => 'required|string|min:4|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
