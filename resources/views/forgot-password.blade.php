@extends("components.default")

@section("title", "Reset your password - CICT Equipment Borrower System")

{{-- This page renders validation errors next to the form.
     components/alerts.blade.php checks for this section and skips its
     SweetAlert modal when it is present, so a failed submit no longer
     reports the same message twice. --}}
@section("inline-errors", true)

@section("content")
@php
    // Both figures come from the configured broker, never from this template:
    // the link really does last `expire` minutes and the broker really does
    // refuse a resend inside `throttle` seconds, so a page quoting anything
    // else is describing a system the server will contradict.
    $expiryLabel = $expireMinutes >= 120
        ? round($expireMinutes / 60).' hours'
        : ($expireMinutes == 60 ? '1 hour' : $expireMinutes.' minutes');

    $cooldown = $tokenState['cooldown'] ?? 0;
    $expiresAt = $tokenState['expires_at'] ?? null;
    $expired = $expiresAt !== null && $expiresAt->isPast();

    $officeEmail = config('office.email');
    $officeHours = \App\Support\OfficeHours::fromConfig()->label();

    $formError = $errors->first('email');

    // A non-school address is worth warning about, not refusing: the admin
    // users form can create an account on any address, and refusing here would
    // lock out the very people who cannot get in.
    $typedEmail = old('email', $sentTo ?? '');
    $offDomain = $typedEmail !== '' && ! \App\Models\User::isSchoolEmail($typedEmail);
@endphp

{{-- The same frame as sign-in and registration, so all three read as one
     product: two columns from lg up, stacked below it. --}}
<div class="grid min-h-[100dvh] lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1fr)]">

    {{-- Left — the actual emergency. Someone locked out of this system is quite
         often holding equipment that is due back today, and the thing they most
         need to know is that the lockout does not block the return. --}}
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

        <div class="flex flex-col gap-5 max-w-[420px]">
            <h1 class="text-[25px] font-semibold leading-[1.2] tracking-[-0.02em] text-balance lg:text-[30px]">
                Locked out with equipment still due?
            </h1>
            <p class="text-[14.5px] leading-[1.6] text-white/70 text-pretty">
                You can still return items at the counter without signing in. A custodian logs the
                return against your name — the reset can wait until after.
            </p>

            <div class="flex flex-col gap-2 rounded-xl bg-white/[0.08] px-4 py-3.5">
                <p class="text-[12px] font-semibold uppercase tracking-[0.06em] text-white/60">Equipment office</p>
                <p class="text-[14px] font-medium">Open {{ $officeHours }}</p>
                @if($officeEmail)
                    <a href="mailto:{{ $officeEmail }}" class="text-[13px] text-white/70 underline underline-offset-2 [overflow-wrap:anywhere] hover:text-white">
                        {{ $officeEmail }}
                    </a>
                @endif
            </div>
        </div>

        <p class="text-[12px] text-white/50">College of Information &amp; Communications Technology</p>
    </aside>

    {{-- Right — the form, or the confirmation that it worked. --}}
    <main class="flex items-center justify-center px-6 py-10 sm:px-8 lg:px-8 lg:py-12">

    @if ($sentTo)
        {{-- Confirmation. The flow used to stop at the click: press Send, watch
             nothing happen, press again. Everything below is what the send
             actually did. --}}
        <div class="flex w-full max-w-[396px] flex-col gap-5" data-reset-sent>
            <span class="grid w-11 h-11 rounded-full shrink-0 place-items-center bg-success-100 text-success-700" aria-hidden="true">
                <i class="fa-regular fa-envelope text-[17px]"></i>
            </span>

            <div class="flex flex-col gap-2">
                <h2 class="text-[23px] font-semibold tracking-[-0.015em] text-neutral-900">Check your inbox</h2>
                @if($expired)
                    <p class="text-[14.5px] leading-[1.6] text-neutral-600 text-pretty">
                        The link sent to <strong class="font-semibold text-neutral-900">{{ $sentTo }}</strong>
                        has expired — it was only good for {{ $expiryLabel }}. Send yourself a new one below.
                    </p>
                @else
                    <p class="text-[14.5px] leading-[1.6] text-neutral-600 text-pretty">
                        A reset link is on its way to <strong class="font-semibold text-neutral-900">{{ $sentTo }}</strong>.
                        It works once, and
                        @if($expiresAt)
                            expires at <strong class="font-semibold text-neutral-900">{{ $expiresAt->format('g:i A') }}</strong> —
                            {{ $expiryLabel }} after it was sent.
                        @else
                            expires {{ $expiryLabel }} after it was sent.
                        @endif
                    </p>
                @endif
            </div>

            {{-- The broker refused a second send. Said here rather than as a
                 red field error on a form that is no longer on screen. --}}
            @if($formError)
                <div role="alert" data-reset-error
                     class="flex items-start gap-2.5 rounded-[10px] bg-warning-50 px-[13px] py-[11px] text-[13px] text-warning-700 text-pretty">
                    <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-warning-600" aria-hidden="true"></span>
                    <span>{{ $formError }}</span>
                </div>
            @endif

            {{-- The two things that actually go wrong, in the order they go
                 wrong. Neither is guesswork: school mail filters really do eat
                 these, and a deactivated account really cannot be reset. --}}
            <div class="flex flex-col gap-[9px] rounded-[11px] border border-neutral-200 px-4 py-3.5">
                <p class="text-[12px] font-semibold uppercase tracking-[0.06em] text-neutral-500">Not seeing it?</p>
                <p class="flex items-start gap-2.5 text-[13.5px] leading-[1.55] text-neutral-600 text-pretty">
                    <span class="mt-[7px] h-[5px] w-[5px] shrink-0 rounded-full bg-neutral-300" aria-hidden="true"></span>
                    <span>Check your spam or junk folder — school mail filters catch reset links fairly often.</span>
                </p>
                <p class="flex items-start gap-2.5 text-[13.5px] leading-[1.55] text-neutral-600 text-pretty">
                    <span class="mt-[7px] h-[5px] w-[5px] shrink-0 rounded-full bg-neutral-300" aria-hidden="true"></span>
                    <span>An account the equipment office has deactivated cannot be reset — a new password
                          will not let you back in until they restore it. Ask them
                          @if($officeEmail)
                              at <a href="mailto:{{ $officeEmail }}" class="font-medium text-primary-700 [overflow-wrap:anywhere] hover:text-primary-800">{{ $officeEmail }}</a>.
                          @else
                              at the equipment room.
                          @endif
                    </span>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                {{-- A real re-send, not a client-side redraw. The countdown
                     below is only a courtesy: the broker itself refuses a
                     second token inside its throttle window, and the button
                     re-enables exactly when the broker will accept one. --}}
                <form action="{{ route('password.email') }}" method="POST" id="resend-form" class="contents">
                    @csrf
                    <input type="hidden" name="email" value="{{ $sentTo }}">
                    <button type="submit" id="resend-button"
                            data-cooldown="{{ $cooldown }}"
                            @disabled($cooldown > 0)
                            class="h-[42px] shrink-0 rounded-[10px] border border-neutral-200 px-[15px] text-[13.5px] font-medium text-neutral-800 transition-colors hover:bg-neutral-50 disabled:cursor-not-allowed disabled:text-neutral-400 disabled:hover:bg-transparent">
                        <span data-resend-label>
                            {{ $cooldown > 0 ? 'Resend in '.$cooldown.'s' : 'Resend the email' }}
                        </span>
                    </button>
                </form>

                <a href="{{ route('password.request', ['new' => 1]) }}"
                   class="flex h-[42px] items-center rounded-[10px] px-[15px] text-[13.5px] font-medium text-primary-700 hover:bg-primary-50 hover:text-primary-800">
                    Use a different address
                </a>
            </div>

            <a href="{{ route('login') }}" class="text-[13.5px] text-neutral-500 hover:text-neutral-800">&larr; Back to sign in</a>

            <nav class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[12px] text-neutral-500" aria-label="Legal">
                <a href="{{ route('legal.privacy') }}" class="text-neutral-600 hover:text-neutral-900">Privacy policy</a>
                <a href="{{ route('legal.terms') }}" class="text-neutral-600 hover:text-neutral-900">Terms of service</a>
            </nav>
        </div>
    @else
        <div class="flex w-full max-w-[396px] flex-col gap-[22px]">

            <div class="flex flex-col gap-1.5">
                <h2 class="text-[24px] font-semibold tracking-[-0.015em] text-neutral-900">Reset your password</h2>
                <p class="text-[13.5px] leading-[1.6] text-neutral-600 text-pretty">
                    Enter the school email on your account. We will send a link that lets you set a new password.
                </p>
            </div>

            @if($formError)
                <div role="alert" data-reset-error
                     class="flex items-start gap-2.5 rounded-[10px] bg-danger-50 px-[13px] py-[11px] text-[13px] text-danger-700 text-pretty">
                    <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-danger-600" aria-hidden="true"></span>
                    <span>{{ $formError }}</span>
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" novalidate id="forgot-form"
                  class="flex flex-col gap-[15px]">
                @csrf

                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-[12.5px] font-medium text-neutral-900">School email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                           autocomplete="username" required autofocus maxlength="255"
                           placeholder="name{{ '@'.\App\Models\User::SCHOOL_DOMAIN }}"
                           aria-describedby="email-note"
                           @if($errors->has('email')) aria-invalid="true" @endif
                           class="h-11 rounded-[10px] border px-[13px] text-[14px] text-neutral-900 outline-none transition-colors placeholder:text-neutral-400 focus:border-primary-500
                                  {{ $errors->has('email') ? 'border-danger-300' : 'border-neutral-200' }}">

                    {{-- The expiry is stated here, before the link is sent —
                         finding out how long you had only once it has run out
                         is the failure this line exists to prevent. The same
                         slot carries the off-domain warning. --}}
                    <p id="email-note" data-email-note aria-live="polite"
                       class="text-[12px] leading-[1.5] text-pretty {{ $offDomain ? 'text-warning-700' : 'text-neutral-500' }}">
                        @if($offDomain)
                            That is not a school address — reset links normally only go to
                            {{ \App\Models\User::SCHOOL_DOMAIN }} accounts.
                        @else
                            The link expires {{ $expiryLabel }} after it is sent, and works once.
                        @endif
                    </p>
                </div>

                <button type="submit" id="forgot-submit"
                        class="btn-primary h-[46px] min-h-0 rounded-[10px] px-5 text-[14.5px] font-semibold">
                    Send reset link
                </button>
            </form>

            <span class="h-px bg-neutral-200" aria-hidden="true"></span>

            {{-- The failure a reset form cannot solve. Without this, someone who
                 cannot remember which address they registered with has nowhere
                 to go but the field they already cannot fill in. --}}
            <p class="text-[13px] leading-[1.6] text-neutral-600 text-pretty">
                Forgot which address you registered with? The equipment office can look it up
                @if($officeEmail)
                    — <a href="mailto:{{ $officeEmail }}" class="font-medium text-primary-700 [overflow-wrap:anywhere] hover:text-primary-800">{{ $officeEmail }}</a>,
                    or ask at the counter, open {{ $officeHours }}.
                @else
                    — ask at the equipment room counter, open {{ $officeHours }}.
                @endif
            </p>

            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                <a href="{{ route('login') }}" class="text-[13.5px] font-medium text-primary-700 hover:text-primary-800">&larr; Back to sign in</a>
                <nav class="flex items-center gap-4 text-[12px]" aria-label="Legal">
                    <a href="{{ route('legal.privacy') }}" class="text-neutral-600 hover:text-neutral-900">Privacy policy</a>
                    <a href="{{ route('legal.terms') }}" class="text-neutral-600 hover:text-neutral-900">Terms of service</a>
                </nav>
            </div>
        </div>
    @endif
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const SCHOOL_DOMAIN = @json(\App\Models\User::SCHOOL_DOMAIN);

    /* ---- Off-domain warning ------------------------------------------
       A warning, never a block. The admin users form can create an account
       on any address, so refusing here would shut out exactly the people
       who cannot get in. The server does not check the domain either. */
    const form = document.getElementById('forgot-form');
    if (form) {
        const email = document.getElementById('email');
        const note = form.querySelector('[data-email-note]');
        const expiryLine = @json('The link expires '.$expiryLabel.' after it is sent, and works once.');

        function paintNote() {
            const value = email.value.trim().toLowerCase();
            const parts = value.split('@');
            const domain = parts.length === 2 && parts[0] && parts[1] ? parts[1] : null;
            const complete = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            const offDomain = complete && domain !== SCHOOL_DOMAIN;

            note.textContent = offDomain
                ? 'That is not a school address — reset links normally only go to ' + SCHOOL_DOMAIN + ' accounts.'
                : expiryLine;
            note.classList.toggle('text-warning-700', offDomain);
            note.classList.toggle('text-neutral-500', !offDomain);
        }

        email.addEventListener('input', paintNote);
        paintNote();

        form.addEventListener('submit', function () {
            const submit = document.getElementById('forgot-submit');
            submit.disabled = true;
            submit.textContent = 'Sending…';
        });
    }

    /* ---- Resend cooldown ---------------------------------------------
       Cosmetic by design. The authority is the broker's own throttle, and
       the seconds counted here came from the token row it wrote, so the
       button re-enables exactly when a second send would be accepted
       rather than at some number picked to look reassuring. */
    const resend = document.getElementById('resend-button');
    if (resend) {
        let left = parseInt(resend.getAttribute('data-cooldown'), 10) || 0;
        const label = resend.querySelector('[data-resend-label]');

        if (left > 0) {
            const timer = setInterval(function () {
                left -= 1;
                if (left <= 0) {
                    clearInterval(timer);
                    resend.disabled = false;
                    label.textContent = 'Resend the email';
                    return;
                }
                label.textContent = 'Resend in ' + left + 's';
            }, 1000);
        }

        document.getElementById('resend-form').addEventListener('submit', function () {
            resend.disabled = true;
            label.textContent = 'Sending…';
        });
    }
});
</script>
@endsection
