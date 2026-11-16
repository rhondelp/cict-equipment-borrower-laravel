<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    private function brokerConfig(): array
    {
        $name = config('auth.defaults.passwords');

        return config("auth.passwords.{$name}") ?? [];
    }

    private function tokenState(string $email): ?array
    {
        $config = $this->brokerConfig();

        try {
            $row = DB::table($config['table'] ?? 'password_reset_tokens')
                ->where('email', $email)
                ->first();
        } catch (\Throwable $e) {
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

    public function request(Request $request)
    {
        $config = $this->brokerConfig();

        if ($request->boolean('new')) {
            $request->session()->forget('reset_link_sent_to');
        }

        $sentTo = $request->session()->get('reset_link_sent_to');
        $tokenState = $sentTo ? $this->tokenState($sentTo) : null;

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

    public function email(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
        ]);

        $email = strtolower(trim($request->input('email')));

        $user = User::where('email', $email)->first();

        if ($user && $user->isDeactivated()) {
            return redirect()->route('password.request')
                ->withInput(['email' => $email])
                ->withErrors(['email' => 'This account has been deactivated, so a new password will not let you back in. Ask the equipment office to restore it first.']);
        }

        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            ActivityLog::record('password_reset_requested', [
                'actor' => auth()->user(),
                'actor_name' => auth()->user()?->name ?? ActivityLog::ACTOR_ANONYMOUS,
                'subject' => $user,
                'details' => 'Password reset link sent to '.$email.'.',
            ]);

            $request->session()->put('reset_link_sent_to', $email);

            return redirect()->route('password.request')->with('status', __($status));
        }

        if ($status === Password::RESET_THROTTLED) {
            $request->session()->put('reset_link_sent_to', $email);

            return redirect()->route('password.request')
                ->withErrors(['email' => __($status)]);
        }

        return redirect()->route('password.request')
            ->withInput(['email' => $email])
            ->withErrors(['email' => __($status)]);
    }

    public function reset(Request $request, string $token)
    {
        $config = $this->brokerConfig();
        $email = strtolower(trim((string) $request->query('email', '')));
        $user = $email !== '' ? User::where('email', $email)->first() : null;

        $valid = $user !== null
            && ! $request->session()->has('reset_link_expired')
            && Password::broker()->tokenExists($user, $token);

        $expiresAt = $valid ? ($this->tokenState($email)['expires_at'] ?? null) : null;
        $secondsLeft = $expiresAt ? max(0, (int) now()->diffInSeconds($expiresAt, false)) : 0;

        return view('reset-password', [
            'token' => $token,
            'email' => $email,
            'expired' => ! $valid || $secondsLeft === 0,
            'secondsLeft' => $secondsLeft,
            'expireMinutes' => (int) ($config['expire'] ?? 60),
            'emailName' => Str::before($email, '@'),
            'initials' => $user ? $this->initials($user->name) : '',
        ]);
    }

    public function update(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email')));
        $emailName = Str::before($email, '@');

        $request->validate([
            'token' => 'required|string',
            'email' => 'required|string|email|max:255',
            'password' => [
                'required',
                'string',
                PasswordRule::min(8)->letters()->numbers(),
                function (string $attribute, mixed $value, \Closure $fail) use ($emailName) {
                    if ($emailName !== '' && str_contains(mb_strtolower((string) $value), mb_strtolower($emailName))) {
                        $fail('The password cannot contain your email name.');
                    }
                },
            ],
        ]);

        $status = Password::reset(
            ['email' => $email] + $request->only('password', 'token'),
            function (User $user, string $password) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))
                        ->where('user_id', $user->getAuthIdentifier())
                        ->where('id', '!=', $request->session()->getId())
                        ->delete();
                }

                ActivityLog::record('password_reset_completed', [
                    'actor' => $user,
                    'subject' => $user,
                    'details' => 'Password reset from an emailed link; other sessions signed out.',
                ]);

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('password_reset', true)
                ->withInput(['email' => $email]);
        }

        if (in_array($status, [Password::INVALID_TOKEN, Password::INVALID_USER], true)) {
            return redirect()
                ->route('password.reset', ['token' => (string) $request->input('token'), 'email' => $email])
                ->with('reset_link_expired', true);
        }

        return back()->withErrors(['password' => __($status)]);
    }

    private function initials(?string $name): string
    {
        return collect(preg_split('/\s+/', trim((string) $name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
