@extends("components.default")

@section("title", "Choose a new password - CICT Equipment Borrower System")

{{-- This page renders validation errors next to the field.
     components/alerts.blade.php checks for this section and skips its
     SweetAlert modal when it is present. --}}
@section("inline-errors", true)

@push('styles')
    {{-- The countdown is the only thing set in Plex Mono. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
@endpush

@section("content")
@php
    $passwordError = $errors->first('password');

    $clock = sprintf('%d:%02d', intdiv($secondsLeft, 60), $secondsLeft % 60);

    $steps = [
        ['Your old password stops working', 'Straight away, on every device.'],
        ['Other sessions are signed out', 'Including shared lab computers you forgot to log out of.'],
        ['Your loans and requests are untouched', 'Nothing about your borrowing changes.'],
    ];
@endphp

{{-- The same frame as sign-in, registration and forgot-password: two columns
     from lg up, stacked below it. --}}
<div class="grid min-h-[100dvh] lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1fr)]">

    {{-- Left — what saving actually does. Each line is true of update():
         the hash is replaced, the remember token is rotated and the user's
         other session rows are deleted, and nothing else is touched. --}}
    <aside class="flex flex-col justify-between gap-8 px-6 py-8 text-white bg-[#183060] sm:px-10 lg:gap-10 lg:px-11 lg:py-11">
        <a href="{{ url('/') }}" class="flex items-center gap-3 rounded-lg w-fit" aria-label="CICT Equipment Borrower System home">
            <span class="grid w-10 h-10 overflow-hidden bg-white rounded-[10px] shrink-0 place-items-center">
                <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="" class="object-contain w-8 h-8">
            </span>
            <span class="min-w-0">
                <span class="block text-[13.5px] font-semibold leading-tight">CICT Equipment</span>
                <span class="block text-[11.5px] leading-tight text-white/60">University of Northwestern Mindanao</span>
            </span>
        </a>

        <div class="flex flex-col gap-5 max-w-[420px] lg:gap-[22px]">
            <h1 class="text-[25px] font-semibold leading-[1.2] tracking-[-0.02em] text-balance lg:text-[30px]">
                What happens when you save
            </h1>
            <ol class="flex flex-col gap-4" data-save-steps>
                @foreach($steps as [$title, $detail])
                    <li class="flex items-start gap-[13px]">
                        <span class="grid w-[22px] h-[22px] rounded-full shrink-0 place-items-center bg-white/[0.16] text-[11.5px] font-bold" aria-hidden="true">{{ $loop->iteration }}</span>
                        <span class="min-w-0">
                            <span class="block text-[14px] font-semibold">{{ $title }}</span>
                            <span class="block mt-px text-[13px] text-white/[0.66] text-pretty">{{ $detail }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>
        </div>

        <p class="text-[12px] text-white/50">College of Information &amp; Communications Technology</p>
    </aside>

    <main class="flex items-center justify-center px-6 py-10 sm:px-8 lg:px-8 lg:py-12">

        {{-- Expired. Rendered hidden next to a live form too, so the countdown
             can swap to it at 0:00 without a round trip. A class, not the
             `hidden` attribute: preflight's [hidden] loses to `.flex`. --}}
        <div class="{{ $expired ? 'flex' : 'hidden' }} w-full max-w-[396px] flex-col gap-5" data-reset-expired>
            <span class="grid w-11 h-11 rounded-full shrink-0 place-items-center bg-warning-50 text-warning-700" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6.2" stroke="currentColor" stroke-width="1.5"/><path d="M8 4.6V8l2.2 1.4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            </span>
            <div class="flex flex-col gap-2">
                <h2 class="text-[23px] font-semibold tracking-[-0.015em] text-neutral-900">This link has expired</h2>
                <p class="text-[14.5px] leading-[1.6] text-neutral-600 text-pretty">
                    Reset links work once and last {{ $expireMinutes }} minutes. Your password has not been changed.
                </p>
            </div>
            <a href="{{ route('password.request') }}"
               class="btn-primary flex h-[46px] min-h-0 items-center justify-center rounded-[10px] px-5 text-[14.5px] font-semibold">
                Send a new link
            </a>
            <a href="{{ route('login') }}" class="text-[13.5px] text-neutral-500 hover:text-neutral-800">&larr; Back to sign in</a>
        </div>

        @unless($expired)
        <div class="flex w-full max-w-[396px] flex-col gap-[22px]" data-reset-form-state>

            <div class="flex flex-col gap-1.5">
                <h2 class="text-[24px] font-semibold tracking-[-0.015em] text-neutral-900">Choose a new password</h2>
                <p class="text-[13.5px] leading-[1.6] text-neutral-600">
                    This link expires in
                    <span class="font-mono font-medium tabular-nums text-neutral-800"
                          data-countdown data-seconds-left="{{ $secondsLeft }}">{{ $clock }}</span>.
                </p>
            </div>

            {{-- The address is the link's, not the visitor's to change. --}}
            <div class="flex items-center gap-[11px] rounded-[10px] border border-neutral-200 bg-neutral-50 px-[13px] py-[11px]">
                <span class="grid w-[30px] h-[30px] rounded-full shrink-0 place-items-center bg-primary-100 text-[11px] font-semibold text-primary-700" aria-hidden="true">{{ $initials }}</span>
                <span class="flex-1 min-w-0">
                    <span class="block text-[11.5px] text-neutral-500">Resetting the password for</span>
                    <span class="block truncate text-[13.5px] font-medium text-neutral-900" data-reset-email>{{ $email }}</span>
                </span>
            </div>

            <form action="{{ route('password.update') }}" method="POST" novalidate id="reset-form"
                  class="flex flex-col gap-[15px]">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <input type="hidden" name="token" value="{{ $token }}">
                {{-- For password managers: saves the new password against the right account. --}}
                <input type="text" name="username" value="{{ $email }}" autocomplete="username" hidden readonly tabindex="-1" aria-hidden="true">

                <div class="flex flex-col gap-1.5">
                    <label for="password" class="text-[12.5px] font-medium text-neutral-900">New password</label>
                    <div class="flex h-[46px] items-center gap-2 rounded-[10px] border pl-[13px] pr-1.5 transition-colors focus-within:border-primary-500
                                {{ $passwordError ? 'border-danger-300' : 'border-neutral-200' }}"
                         data-password-wrap>
                        <input type="password" name="password" id="password" required autofocus
                               autocomplete="new-password" placeholder="At least 8 characters"
                               aria-describedby="password-rules password-error"
                               @if($passwordError) aria-invalid="true" @endif
                               class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[14px] text-neutral-900 outline-none placeholder:text-neutral-400">
                        <button type="button" id="password-toggle"
                                class="h-[34px] shrink-0 rounded-lg px-2.5 text-[12.5px] font-medium text-neutral-600 hover:bg-neutral-100 hover:text-neutral-800"
                                aria-controls="password" aria-pressed="false">Show</button>
                    </div>
                    <p id="password-error" data-password-error class="text-[12px] text-danger-700" role="alert" @unless($passwordError) hidden @endunless>{{ $passwordError }}</p>
                </div>

                {{-- The same three rules update() enforces, in the same terms. --}}
                <div class="flex flex-col gap-2">
                    <span class="flex gap-1" aria-hidden="true">
                        @for($i = 0; $i < 3; $i++)
                            <span class="h-1 flex-1 rounded-[3px] bg-neutral-200 transition-colors" data-strength-segment></span>
                        @endfor
                    </span>
                    <ul id="password-rules" class="flex flex-col gap-2" aria-live="polite">
                        @foreach(['length' => 'At least 8 characters', 'mix' => 'Letters and numbers', 'name' => 'Not the same as your email name'] as $key => $label)
                            <li class="flex items-center gap-2 text-[12.5px] text-neutral-500" data-rule="{{ $key }}">
                                <span class="grid w-[15px] h-[15px] rounded-full shrink-0 place-items-center bg-neutral-200 text-white transition-colors" data-rule-dot aria-hidden="true">
                                    <svg width="9" height="9" viewBox="0 0 16 16" fill="none"><path d="M3.4 8.4 6.4 11.4l6.2-7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span>{{ $label }}<span class="sr-only" data-rule-state> — not met yet</span></span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <button type="submit" id="reset-submit"
                        class="btn-primary mt-1 h-12 min-h-0 rounded-[10px] px-5 text-[14.5px] font-semibold disabled:cursor-progress disabled:opacity-70">
                    Save new password
                </button>
                <p class="text-[12.5px] text-center text-danger-700" data-rules-hint hidden>Meet all three requirements to continue.</p>
            </form>

            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-t border-neutral-200 pt-[18px]">
                <a href="{{ route('login') }}" class="text-[13.5px] font-medium text-primary-700 hover:text-primary-800">&larr; Back to sign in</a>
                <nav class="flex items-center gap-4 text-[12px]" aria-label="Legal">
                    <a href="{{ route('legal.privacy') }}" class="text-neutral-600 hover:text-neutral-900">Privacy policy</a>
                    <a href="{{ route('legal.terms') }}" class="text-neutral-600 hover:text-neutral-900">Terms of service</a>
                </nav>
            </div>
        </div>
        @endunless
    </main>
</div>

@unless($expired)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('reset-form');
    if (!form) return;

    const password = document.getElementById('password');
    const toggle = document.getElementById('password-toggle');
    const submit = document.getElementById('reset-submit');
    const hint = form.querySelector('[data-rules-hint]');
    const serverError = form.querySelector('[data-password-error]');
    const wrap = form.querySelector('[data-password-wrap]');
    const segments = form.querySelectorAll('[data-strength-segment]');
    const emailName = @json(mb_strtolower($emailName));
    let touched = false;

    /* ---- Show / Hide: a word, like the sign-in page. */
    toggle.addEventListener('click', function () {
        const revealed = password.type === 'text';
        password.type = revealed ? 'password' : 'text';
        toggle.textContent = revealed ? 'Show' : 'Hide';
        toggle.setAttribute('aria-pressed', revealed ? 'false' : 'true');
        password.focus();
    });

    /* ---- Requirements ------------------------------------------------
       Mirrors PasswordResetController::update exactly: Password::min(8)
       counts characters (mb_strlen), letters() is \pL, numbers() is \pN,
       and the email-name rule is a case-insensitive "contains". */
    function rules(value) {
        return {
            length: Array.from(value).length >= 8,
            mix: /\p{L}/u.test(value) && /\p{N}/u.test(value),
            name: value.length > 0 && (emailName === '' || !value.toLowerCase().includes(emailName)),
        };
    }

    const SEGMENT = ['bg-danger-500', 'bg-warning-500', 'bg-success-500'];

    function paint() {
        const result = rules(password.value);
        const passed = Object.values(result).filter(Boolean).length;
        const allOk = passed === 3;
        const tone = SEGMENT[Math.max(0, passed - 1)];

        segments.forEach(function (segment, i) {
            segment.classList.remove('bg-neutral-200', ...SEGMENT);
            segment.classList.add(password.value && i < passed ? tone : 'bg-neutral-200');
        });

        Object.keys(result).forEach(function (key) {
            const row = form.querySelector('[data-rule="' + key + '"]');
            const ok = result[key];
            row.classList.toggle('text-success-700', ok);
            row.classList.toggle('text-danger-700', !ok && touched);
            row.classList.toggle('text-neutral-500', !ok && !touched);
            const dot = row.querySelector('[data-rule-dot]');
            dot.classList.toggle('bg-success-500', ok);
            dot.classList.toggle('bg-neutral-200', !ok);
            row.querySelector('[data-rule-state]').textContent = ok ? ' — met' : ' — not met yet';
        });

        const flagged = touched && !allOk;
        hint.hidden = !flagged;
        wrap.classList.toggle('border-danger-300', flagged || !serverError.hidden);
        wrap.classList.toggle('border-neutral-200', !flagged && serverError.hidden);
        password.setAttribute('aria-invalid', flagged ? 'true' : 'false');

        return allOk;
    }

    password.addEventListener('input', function () {
        // The server's message described the last attempt, not this one.
        serverError.hidden = true;
        paint();
    });
    paint();

    form.addEventListener('submit', function (event) {
        touched = true;
        if (!paint()) {
            event.preventDefault();
            password.focus();
            return;
        }
        submit.disabled = true;
        submit.textContent = 'Saving…';
    });

    /* ---- Countdown ---------------------------------------------------
       Started from the token row the broker wrote plus its `expire`, so
       it reaches 0:00 when the broker would start refusing the link.
       Measured against the clock rather than counted in ticks, so a
       backgrounded tab does not drift. */
    const countdown = document.querySelector('[data-countdown]');
    const deadline = Date.now() + (parseInt(countdown.getAttribute('data-seconds-left'), 10) || 0) * 1000;

    function tick() {
        const left = Math.max(0, Math.round((deadline - Date.now()) / 1000));
        countdown.textContent = Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0');
        if (left === 0) {
            clearInterval(timer);
            document.querySelector('[data-reset-form-state]').remove();
            document.querySelector('[data-reset-expired]').classList.replace('hidden', 'flex');
        }
    }
    const timer = setInterval(tick, 1000);
});
</script>
@endunless
@endsection
