<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
    /**
     * The broker name the app is configured against, and its two numbers.
     *
     * Both are surfaced in the page copy rather than written into it: the link
     * really does last `expire` minutes and the broker really does refuse a
     * second send inside `throttle` seconds, so a page that quotes different
     * figures is telling people something the server will contradict.
     */
    private function brokerConfig(): array
    {
        $name = config('auth.defaults.passwords');

        return config("auth.passwords.{$name}") ?? [];
    }

    /**
     * What the outstanding reset token for an address actually says — when it
     * was issued, when it dies, and how long the broker will keep refusing a
     * resend.
     *
     * Read from the broker's own table rather than from a second clock kept in
     * the session: the session copy would survive a token the broker has
     * already replaced or consumed, and then the countdown on screen would be
     * describing something that no longer exists. Null when no token is
     * outstanding, which is also what a used link looks like.
     */
    private function tokenState(string $email): ?array
    {
        $config = $this->brokerConfig();

        try {
            $row = DB::table($config['table'] ?? 'password_reset_tokens')
                ->where('email', $email)
                ->first();
        } catch (\Throwable $e) {
            // The page still has to render if the table cannot be read; it
            // simply loses the live countdown and quotes the durations instead.
            return null;
        }

        if (! $row || ! ($row->created_at ?? null)) {
            return null;
        }

        $sentAt = Carbon::parse($row->created_at);
        $elapsed = max(0, (int) $sentAt->diffInSeconds(now()));

        return [
            'sent_at' => $sentAt,
            'expires_at' => $sentAt->copy()->addMinutes((int) ($config['expire'] ?? 60)),
            'cooldown' => max(0, (int) ($config['throttle'] ?? 60) - $elapsed),
        ];
    }

    /** Show the "email me a reset link" form, or the confirmation after one went out. */
    public function request(Request $request)
    {
        $config = $this->brokerConfig();

        // Set by email() below, and kept in the session rather than flashed:
        // someone who has just been told to go and check their inbox is very
        // likely to reload this tab, and a confirmation that vanishes on
        // refresh is the same dead end the screen exists to fix.
        // "Use a different address" is the way out, and it is this ?new=1.
        if ($request->boolean('new')) {
            $request->session()->forget('reset_link_sent_to');
        }

        $sentTo = $request->session()->get('reset_link_sent_to');
        $tokenState = $sentTo ? $this->tokenState($sentTo) : null;

        // No token left means the link has been used — the broker deletes it on
        // a successful reset — so the confirmation is describing something that
        // is over. Drop back to the form rather than counting down to nothing.
        if ($sentTo && ! $tokenState) {
            $request->session()->forget('reset_link_sent_to');
            $sentTo = null;
        }

        return view('forgot-password', [
            'sentTo' => $sentTo,
            'tokenState' => $tokenState,
            'expireMinutes' => (int) ($config['expire'] ?? 60),
            'throttleSeconds' => (int) ($config['throttle'] ?? 60),
        ]);
    }

    /** Send the reset link. Also the Resend button on the confirmation state. */
    public function email(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
        ]);

        $email = strtolower(trim($request->input('email')));

        // A deactivated account can be reset today and still cannot sign in
        // afterwards, because AuthenticateUser::login refuses it separately.
        // That is the loop this screen exists to break: the reset appears to
        // work, so people do it again. Refused here, with the one instruction
        // that actually helps.
        $user = User::where('email', $email)->first();

        if ($user && $user->isDeactivated()) {
            return redirect()->route('password.request')
                ->withInput(['email' => $email])
                ->withErrors(['email' => 'This account has been deactivated, so a new password will not let you back in. Ask the equipment office to restore it first.']);
        }

        // Note: the broker returns INVALID_USER for an unknown address, so the
        // form reports "we can't find a user with that email address" — Laravel's
        // default, and the same thing registration already reveals via its
        // unique:users rule. Return RESET_LINK_SENT unconditionally here if the
        // flow should stop confirming which addresses are registered.
        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            $request->session()->put('reset_link_sent_to', $email);

            return redirect()->route('password.request')->with('status', __($status));
        }

        // A refused resend keeps the confirmation state rather than dropping
        // back to an empty form — the link that is already in their inbox is
        // still the one they need, and the countdown says when to try again.
        if ($status === Password::RESET_THROTTLED) {
            $request->session()->put('reset_link_sent_to', $email);

            return redirect()->route('password.request')
                ->withErrors(['email' => __($status)]);
        }

        return redirect()->route('password.request')
            ->withInput(['email' => $email])
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
