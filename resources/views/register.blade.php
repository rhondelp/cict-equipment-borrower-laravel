@extends("components.default")

@section("title", "Request an account - CICT Equipment Borrower System")

{{-- This page renders validation errors next to the field they belong to.
     components/alerts.blade.php checks for this section and skips its
     SweetAlert modal when it is present, so a failed submit no longer
     reports the same message twice. --}}
@section("inline-errors", true)

@section("content")
@php
    // Set by AuthenticateUser::registerPublic after the row is written. Its
    // presence is what switches this page from the form to the success state.
    $registered = session('registered');

    $studentDomain = \App\Models\User::STUDENT_DOMAIN;
    $staffDomain = \App\Models\User::STAFF_DOMAIN;

    // Re-derived for the server-rendered role note, so the fact shown after a
    // rejected submit comes from the same function that would have assigned
    // the role — not from a second copy of the rule in the template.
    $oldRole = \App\Models\User::roleForEmail(old('email'));
@endphp

{{-- Two columns from lg up, stacked below it — the same frame as the sign-in
     page, so the two read as one product. --}}
<div class="grid min-h-[100dvh] lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1fr)]">

    {{-- Left — what happens after this form. Someone filling in a sign-up has
         exactly one question the form itself cannot answer: then what? --}}
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

        <div class="flex flex-col gap-4 max-w-[420px] lg:gap-[22px]">
            <h1 class="text-[26px] font-semibold leading-[1.16] tracking-[-0.02em] text-balance lg:text-[32px]">
                Three steps between you and your first borrow.
            </h1>

            <ol class="flex flex-col gap-4 pt-0.5">
                <li class="flex items-start gap-[13px]">
                    <span class="grid w-[22px] h-[22px] shrink-0 place-items-center rounded-full bg-white text-[11.5px] font-bold text-[#183060]" aria-hidden="true">1</span>
                    <span class="min-w-0">
                        <span class="block text-[14px] font-semibold">Fill this in</span>
                        <span class="block text-[13px] leading-[1.5] text-white/65 text-pretty mt-px">
                            Enter your school information. Takes about a minute.
                        </span>
                    </span>
                </li>
                <li class="flex items-start gap-[13px]">
                    <span class="grid w-[22px] h-[22px] shrink-0 place-items-center rounded-full bg-white/[0.18] text-[11.5px] font-bold text-white" aria-hidden="true">2</span>
                    <span class="min-w-0">
                        <span class="block text-[14px] font-semibold">Office verifies you</span>
                        <span class="block text-[13px] leading-[1.5] text-white/65 text-pretty mt-px">
                            The equipment office checks new accounts against the school register.
                        </span>
                    </span>
                </li>
                <li class="flex items-start gap-[13px]">
                    <span class="grid w-[22px] h-[22px] shrink-0 place-items-center rounded-full bg-white/[0.18] text-[11.5px] font-bold text-white" aria-hidden="true">3</span>
                    <span class="min-w-0">
                        <span class="block text-[14px] font-semibold">Start requesting</span>
                        <span class="block text-[13px] leading-[1.5] text-white/65 text-pretty mt-px">
                            Ask for equipment from your dashboard. The office approves each request before anything leaves the shelf.
                        </span>
                    </span>
                </li>
            </ol>
        </div>

        <p class="text-[12px] text-white/50">College of Information &amp; Communications Technology</p>
    </aside>

    {{-- Right — the form, or what happened to it. --}}
    <main class="flex items-center justify-center px-6 py-10 sm:px-8 lg:px-8 lg:py-12">

    @if ($registered)
        {{-- Success state. Reached only by the redirect that follows a written
             row, so everything it reports is a fact about that row. --}}
        <div class="flex w-full max-w-[400px] flex-col gap-[18px]" data-register-success>
            <span class="grid w-11 h-11 rounded-full shrink-0 place-items-center bg-success-100 text-success-700" aria-hidden="true">
                <i class="fa-solid fa-check text-[17px]"></i>
            </span>

            <div class="flex flex-col gap-[7px]">
                <h2 class="text-[22px] font-semibold tracking-[-0.015em] text-neutral-900">Account created</h2>
                <p class="text-[14px] leading-[1.6] text-neutral-600 text-pretty">
                    Your registration went through. Here is what the equipment office now has on file.
                </p>
            </div>

            <dl class="flex flex-col gap-[9px] rounded-[11px] border border-neutral-200 px-[15px] py-[13px]">
                <div class="flex items-center justify-between gap-3 text-[13px]">
                    <dt class="text-neutral-500 shrink-0">Email</dt>
                    <dd class="font-semibold text-neutral-900 truncate">{{ $registered['email'] }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 text-[13px]">
                    <dt class="text-neutral-500 shrink-0">Registered as</dt>
                    <dd class="font-semibold text-neutral-900">{{ $registered['role'] }}</dd>
                </div>
            </dl>

            {{-- What happens next, as this system actually behaves. It does not
                 hold a new account behind an approval queue and it sets no
                 turnaround time, so neither is claimed here. --}}
            <div class="flex flex-col gap-2.5">
                <h3 class="text-[13px] font-semibold text-neutral-900">What happens next</h3>
                <ul class="flex flex-col gap-2">
                    <li class="flex items-start gap-2.5 text-[13px] leading-[1.55] text-neutral-600 text-pretty">
                        <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-success-500" aria-hidden="true"></span>
                        <span>You can sign in straight away with this email and the password you just set.</span>
                    </li>
                    <li class="flex items-start gap-2.5 text-[13px] leading-[1.55] text-neutral-600 text-pretty">
                        <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-neutral-300" aria-hidden="true"></span>
                        <span>The equipment office checks your details against the school register and confirms
                              you as {{ strtolower($registered['role']) === 'instructor' ? 'an' : 'a' }} {{ strtolower($registered['role']) }}.
                              Nothing in the system puts a clock on that check — ask at the equipment room if you
                              need it looked at today (open 8:00&nbsp;AM&nbsp;–&nbsp;5:00&nbsp;PM, Monday to Saturday).</span>
                    </li>
                    <li class="flex items-start gap-2.5 text-[13px] leading-[1.55] text-neutral-600 text-pretty">
                        <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-neutral-300" aria-hidden="true"></span>
                        <span>Each equipment request you make is approved by the office before stock is held for you.</span>
                    </li>
                </ul>
            </div>

            <a href="{{ route('login') }}"
               class="btn-primary h-[46px] min-h-0 rounded-[10px] px-5 text-[14.5px] font-semibold">
                Back to sign in
            </a>

            <nav class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[12px] text-neutral-500" aria-label="Legal">
                <a href="{{ route('legal.privacy') }}" class="text-neutral-600 hover:text-neutral-900">Privacy policy</a>
                <a href="{{ route('legal.terms') }}" class="text-neutral-600 hover:text-neutral-900">Terms of service</a>
            </nav>
        </div>
    @else
        <div class="flex w-full max-w-[400px] flex-col gap-5">

            <div class="flex flex-col gap-[5px]">
                <h2 class="text-[24px] font-semibold tracking-[-0.015em] text-neutral-900">Request an account</h2>
                <p class="text-[13.5px] text-neutral-600 text-pretty">
                    Students and instructors register here. Your role is read from your school email —
                    there is nothing to pick.
                </p>
            </div>

            <form action="{{ route('register.store') }}" method="POST" novalidate id="register-form"
                  class="flex flex-col gap-[15px]">
                @csrf

                {{-- Full name --}}
                <div class="flex flex-col gap-1.5">
                    <label for="name" class="text-[12.5px] font-medium text-neutral-900">
                        Full name <span class="text-danger-600" aria-hidden="true">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                           autocomplete="name" required autofocus maxlength="255"
                           placeholder="Maria Angeles Bautista"
                           aria-describedby="name-error"
                           @if($errors->has('name')) aria-invalid="true" @endif
                           class="h-11 rounded-[10px] border px-[13px] text-[14px] text-neutral-900 outline-none transition-colors placeholder:text-neutral-400 focus:border-primary-500
                                  {{ $errors->has('name') ? 'border-danger-300' : 'border-neutral-200' }}">
                    <p id="name-error" data-error-for="name" class="text-[12px] text-danger-700" @unless($errors->has('name')) hidden @endunless>{{ $errors->first('name') }}</p>
                </div>

                {{-- School email. The role note under it is a read-only fact,
                     not a field: there is no control here that sets a role. --}}
                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-[12.5px] font-medium text-neutral-900">
                        School email <span class="text-danger-600" aria-hidden="true">*</span>
                    </label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                           autocomplete="username" required maxlength="255"
                           placeholder="name{{ '@'.$studentDomain }}"
                           aria-describedby="email-error email-role"
                           @if($errors->has('email')) aria-invalid="true" @endif
                           class="h-11 rounded-[10px] border px-[13px] text-[14px] text-neutral-900 outline-none transition-colors placeholder:text-neutral-400 focus:border-primary-500
                                  {{ $errors->has('email') ? 'border-danger-300' : 'border-neutral-200' }}">
                    <p id="email-error" data-error-for="email" class="text-[12px] text-danger-700" @unless($errors->has('email')) hidden @endunless>{{ $errors->first('email') }}</p>
                    <p id="email-role" data-role-note aria-live="polite"
                       class="text-[12px] leading-[1.5] text-pretty {{ $oldRole ? 'text-success-700' : 'text-neutral-500' }}">
                        @if($oldRole)
                            Detected role: <span class="font-semibold">{{ $oldRole }}</span>. Based on your school email.
                            The equipment office confirms your role during approval.
                        @else
                            Your role comes from your address — <span class="font-medium text-neutral-700">{{ '@'.$studentDomain }}</span> for students,
                            <span class="font-medium text-neutral-700">{{ '@'.$staffDomain }}</span> for instructors.
                        @endif
                    </p>
                </div>

                {{-- Contact number. Optional in the database and optional here;
                     the note says what it is actually for, which is the admin
                     users screen and nothing automated. --}}
                <div class="flex flex-col gap-1.5">
                    <label for="contact_number" class="flex items-baseline gap-1.5 text-[12.5px] font-medium text-neutral-900">
                        Contact number <span class="font-normal text-neutral-500">optional</span>
                    </label>
                    <input type="tel" name="contact_number" id="contact_number" value="{{ old('contact_number') }}"
                           autocomplete="tel" maxlength="15" inputmode="tel"
                           placeholder="0919 123 4567"
                           aria-describedby="contact-error contact-why"
                           @if($errors->has('contact_number')) aria-invalid="true" @endif
                           class="h-11 rounded-[10px] border px-[13px] text-[14px] text-neutral-900 outline-none transition-colors placeholder:text-neutral-400 focus:border-primary-500
                                  {{ $errors->has('contact_number') ? 'border-danger-300' : 'border-neutral-200' }}">
                    <p id="contact-error" data-error-for="contact_number" class="text-[12px] text-danger-700" @unless($errors->has('contact_number')) hidden @endunless>{{ $errors->first('contact_number') }}</p>
                    <p id="contact-why" class="text-[12px] leading-[1.5] text-neutral-500 text-pretty">
                        Shown to the equipment office on your account, so they can reach you about a request or
                        an overdue item. Nothing is sent to it automatically.
                    </p>
                </div>

                {{-- Password. One field: the confirm box caught a typo that the
                     Show control prevents outright, and the requirements are
                     stated here rather than reported back after a rejection. --}}
                <div class="flex flex-col gap-1.5">
                    <label for="password" class="text-[12.5px] font-medium text-neutral-900">
                        Password <span class="text-danger-600" aria-hidden="true">*</span>
                    </label>

                    {{-- A word, not a glyph, matching the sign-in page: "Show"
                         says what the control does, where an eye leaves you
                         guessing which state it reports. --}}
                    <div class="flex h-11 items-center gap-2 rounded-[10px] border pl-[13px] pr-1.5 transition-colors focus-within:border-primary-500
                                {{ $errors->has('password') ? 'border-danger-300' : 'border-neutral-200' }}"
                         data-password-wrap>
                        <input type="password" name="password" id="password" required
                               autocomplete="new-password" placeholder="At least 8 characters"
                               aria-describedby="password-error password-strength password-rules"
                               @if($errors->has('password')) aria-invalid="true" @endif
                               class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[14px] text-neutral-900 outline-none placeholder:text-neutral-400">
                        <button type="button" id="password-toggle"
                                class="h-8 shrink-0 rounded-lg px-2.5 text-[12.5px] font-medium text-neutral-600 hover:bg-neutral-100 hover:text-neutral-800"
                                aria-controls="password" aria-pressed="false">Show</button>
                    </div>
                    <p id="password-error" data-error-for="password" class="text-[12px] text-danger-700" @unless($errors->has('password')) hidden @endunless>{{ $errors->first('password') }}</p>

                    {{-- Strength, live. --}}
                    <div id="password-strength" class="flex items-center gap-[7px]" aria-live="polite">
                        <span class="h-1 flex-1 overflow-hidden rounded-[3px] bg-neutral-200">
                            <span data-strength-bar class="block h-full w-0 rounded-[3px] bg-neutral-300 transition-all duration-200"></span>
                        </span>
                        <span data-strength-label class="text-[12px] font-medium text-neutral-500 whitespace-nowrap">—</span>
                    </div>

                    {{-- The three rules the server applies, said before submit
                         and ticked off while typing. --}}
                    <ul id="password-rules" class="flex flex-col gap-1 pt-0.5">
                        <li data-rule="length" class="flex items-center gap-2 text-[12px] text-neutral-500">
                            <span data-rule-mark class="grid w-[13px] h-[13px] shrink-0 place-items-center rounded-full border border-neutral-300 text-[8px] text-transparent" aria-hidden="true">&#10003;</span>
                            At least 8 characters
                        </li>
                        <li data-rule="letters" class="flex items-center gap-2 text-[12px] text-neutral-500">
                            <span data-rule-mark class="grid w-[13px] h-[13px] shrink-0 place-items-center rounded-full border border-neutral-300 text-[8px] text-transparent" aria-hidden="true">&#10003;</span>
                            Contains letters
                        </li>
                        <li data-rule="numbers" class="flex items-center gap-2 text-[12px] text-neutral-500">
                            <span data-rule-mark class="grid w-[13px] h-[13px] shrink-0 place-items-center rounded-full border border-neutral-300 text-[8px] text-transparent" aria-hidden="true">&#10003;</span>
                            Contains numbers
                        </li>
                    </ul>
                </div>

                {{-- One consent, with the whole framed block as its target. --}}
                <div class="flex flex-col gap-1.5">
                    <label data-agree-box
                           class="flex cursor-pointer items-start gap-2.5 rounded-[10px] border px-3 py-[11px] transition-colors hover:bg-neutral-50
                                  {{ $errors->has('agree') ? 'border-danger-300' : 'border-neutral-200' }}">
                        <input type="checkbox" name="agree" id="agree" value="1" @checked(old('agree'))
                               aria-describedby="agree-error"
                               class="mt-0.5 h-[15px] w-[15px] shrink-0 cursor-pointer accent-primary-600">
                        <span class="text-[13px] leading-[1.5] text-neutral-700 text-pretty">
                            I will return borrowed equipment on time and in working condition, and I accept the
                            <a href="{{ route('legal.terms') }}" class="font-medium text-primary-700 hover:text-primary-800">Terms of service</a>
                            and
                            <a href="{{ route('legal.privacy') }}" class="font-medium text-primary-700 hover:text-primary-800">Privacy policy</a>.
                        </span>
                    </label>
                    <p id="agree-error" data-error-for="agree" class="text-[12px] text-danger-700" @unless($errors->has('agree')) hidden @endunless>{{ $errors->first('agree') }}</p>
                </div>

                <button type="submit" id="register-submit"
                        class="btn-primary h-[46px] min-h-0 rounded-[10px] px-5 text-[14.5px] font-semibold">
                    Create account
                </button>

                {{-- Why the button above is not available yet. Never blank: when
                     everything is satisfied it says what pressing it does. --}}
                <p id="register-hint" data-submit-hint aria-live="polite"
                   class="text-center text-[12.5px] text-neutral-500 text-pretty">
                    Fill in the required fields to continue.
                </p>
            </form>

            <p class="text-center text-[13.5px] text-neutral-600">
                Already have an account?
                <a href="{{ route('login') }}" class="font-semibold text-primary-700 hover:text-primary-800">Sign in</a>
            </p>

            <nav class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[12px] text-neutral-500" aria-label="Legal">
                <a href="{{ route('legal.privacy') }}" class="text-neutral-600 hover:text-neutral-900">Privacy policy</a>
                <a href="{{ route('legal.terms') }}" class="text-neutral-600 hover:text-neutral-900">Terms of service</a>
                <span class="sm:ml-auto">Need help? Ask at the equipment room.</span>
            </nav>
        </div>
    @endif
    </main>
</div>

@unless ($registered)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('register-form');
    if (!form) return;

    /* The two domains, rendered from the model constants rather than written
       here a second time — this half only mirrors the server, and a mirror
       that disagrees with what it reflects is worse than no mirror. */
    const STUDENT_DOMAIN = @json($studentDomain);
    const STAFF_DOMAIN = @json($staffDomain);

    const name = document.getElementById('name');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const agree = document.getElementById('agree');
    const submit = document.getElementById('register-submit');
    const toggle = document.getElementById('password-toggle');
    const roleNote = form.querySelector('[data-role-note]');
    const hint = form.querySelector('[data-submit-hint]');
    const agreeBox = form.querySelector('[data-agree-box]');
    const bar = form.querySelector('[data-strength-bar]');
    const strengthLabel = form.querySelector('[data-strength-label]');

    /* ---- Show / Hide -------------------------------------------------
       Not the shared `.eye-btn` handler in the layout: that one swaps a
       glyph, and this control is a word. */
    toggle.addEventListener('click', function () {
        const revealed = password.type === 'text';
        password.type = revealed ? 'password' : 'text';
        toggle.textContent = revealed ? 'Show' : 'Hide';
        toggle.setAttribute('aria-pressed', revealed ? 'false' : 'true');
        password.focus();
    });

    /* ---- Role, read from the address ---------------------------------
       Mirrors User::roleForEmail exactly, including matching the whole
       domain rather than a suffix. Nothing here assigns a role — the
       server derives it again from the address it receives. */
    function domainOf(value) {
        const parts = String(value).trim().toLowerCase().split('@');
        if (parts.length !== 2 || !parts[0] || !parts[1]) return null;
        return parts[1];
    }

    function roleFor(value) {
        const domain = domainOf(value);
        if (domain === STUDENT_DOMAIN) return 'Student';
        if (domain === STAFF_DOMAIN) return 'Instructor';
        return null;
    }

    const looksLikeEmail = function (value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value).trim());
    };

    /* ---- Password ----------------------------------------------------
       The same three conditions the server validates, plus a fourth that
       only moves the bar: length is the one thing a passing password can
       still have more of. */
    function passwordChecks(value) {
        return {
            length: value.length >= 8,
            letters: /[A-Za-z]/.test(value),
            numbers: /[0-9]/.test(value),
            long: value.length >= 12,
        };
    }

    function passwordOk(value) {
        const checks = passwordChecks(value);
        return checks.length && checks.letters && checks.numbers;
    }

    /* ---- Field rules, mirroring AuthenticateUser::registerPublic ------ */
    const rules = {
        name: function (value) {
            return value.trim() === '' ? 'Enter your full name.' : '';
        },
        email: function (value) {
            if (value.trim() === '') return 'Enter your school email address.';
            if (!looksLikeEmail(value)) return 'That does not look like an email address.';
            if (!roleFor(value)) return 'Use your school address — @' + STUDENT_DOMAIN + ' for students, @' + STAFF_DOMAIN + ' for instructors.';
            return '';
        },
        password: function (value) {
            if (value === '') return 'Choose a password.';
            return passwordOk(value) ? '' : 'Your password does not meet the three requirements below yet.';
        },
    };

    const fields = { name: name, email: email, password: password };
    let attempted = false;

    function borderTarget(field) {
        // The password input sits inside the bordered row that holds the
        // Show button, so the ring goes on the wrapper, not the input.
        return field === password ? password.closest('[data-password-wrap]') : field;
    }

    function mark(key, message) {
        const field = fields[key];
        const note = form.querySelector('[data-error-for="' + key + '"]');
        const target = borderTarget(field);

        note.textContent = message;
        note.hidden = message === '';
        field.setAttribute('aria-invalid', message ? 'true' : 'false');
        target.classList.toggle('border-danger-300', message !== '');
        target.classList.toggle('border-neutral-200', message === '');
    }

    function check(key) {
        const message = rules[key](fields[key].value);
        mark(key, message);
        return message === '';
    }

    /* ---- Live surfaces ------------------------------------------------ */
    function paintRole() {
        const value = email.value;
        const role = roleFor(value);

        roleNote.classList.remove('text-neutral-500', 'text-success-700', 'text-danger-700');

        if (role) {
            roleNote.innerHTML = 'Detected role: <span class="font-semibold">' + role + '</span>. Based on your school email. '
                + 'The equipment office confirms your role during approval.';
            roleNote.classList.add('text-success-700');
        } else if (value.trim() === '') {
            roleNote.innerHTML = 'Your role comes from your address — <span class="font-medium text-neutral-700">@' + STUDENT_DOMAIN + '</span> for students, '
                + '<span class="font-medium text-neutral-700">@' + STAFF_DOMAIN + '</span> for instructors.';
            roleNote.classList.add('text-neutral-500');
        } else if (!looksLikeEmail(value)) {
            roleNote.textContent = 'Keep typing your full address.';
            roleNote.classList.add('text-neutral-500');
        } else {
            roleNote.textContent = 'Personal addresses are not accepted. Use your @' + STAFF_DOMAIN + ' account.';
            roleNote.classList.add('text-danger-700');
        }
    }

    const STRENGTH = {
        0: { label: '—',      width: '0%',   bar: 'bg-neutral-300', text: 'text-neutral-500' },
        1: { label: 'Weak',   width: '33%',  bar: 'bg-danger-500',  text: 'text-danger-700' },
        2: { label: 'Fair',   width: '66%',  bar: 'bg-warning-500', text: 'text-warning-700' },
        3: { label: 'Strong', width: '100%', bar: 'bg-success-500', text: 'text-success-700' },
    };

    function paintPassword() {
        const value = password.value;
        const checks = passwordChecks(value);

        form.querySelectorAll('[data-rule]').forEach(function (row) {
            const met = checks[row.getAttribute('data-rule')] === true;
            const box = row.querySelector('[data-rule-mark]');

            row.classList.toggle('text-success-700', met);
            row.classList.toggle('text-neutral-500', !met);
            box.classList.toggle('bg-success-500', met);
            box.classList.toggle('border-success-500', met);
            box.classList.toggle('border-neutral-300', !met);
            box.classList.toggle('text-white', met);
            box.classList.toggle('text-transparent', !met);
        });

        const score = value.length === 0 ? 0
            : Math.max(1, (checks.length ? 1 : 0) + (checks.letters && checks.numbers ? 1 : 0) + (checks.long ? 1 : 0));
        const step = STRENGTH[Math.min(score, 3)];

        bar.style.width = step.width;
        bar.className = 'block h-full rounded-[3px] transition-all duration-200 ' + step.bar;
        strengthLabel.textContent = step.label;
        strengthLabel.className = 'text-[12px] font-medium whitespace-nowrap ' + step.text;
    }

    /* ---- Submit gate --------------------------------------------------
       Disabled until the form would pass, with the first unmet condition
       named underneath — a disabled button that does not say why is just
       a dead control. The server validates all of this again regardless. */
    function firstProblem() {
        if (rules.name(name.value)) return 'Enter your full name to continue.';
        if (email.value.trim() === '') return 'Enter your school email address to continue.';
        if (!looksLikeEmail(email.value)) return 'Finish typing your school email address.';
        if (!roleFor(email.value)) return 'Use an @' + STUDENT_DOMAIN + ' or @' + STAFF_DOMAIN + ' address to continue.';
        if (password.value === '') return 'Choose a password to continue.';
        if (!passwordOk(password.value)) return 'Your password still needs 8+ characters with letters and numbers.';
        if (!agree.checked) return 'Tick the agreement to continue.';
        return '';
    }

    function refresh() {
        const problem = firstProblem();

        submit.disabled = problem !== '';
        hint.textContent = problem || 'Creates your account and takes you back to sign in.';
        hint.classList.toggle('text-neutral-500', problem === '');
        hint.classList.toggle('text-neutral-600', problem !== '');

        agreeBox.classList.toggle('border-danger-300', attempted && !agree.checked);
        agreeBox.classList.toggle('border-neutral-200', !(attempted && !agree.checked));

        paintRole();
        paintPassword();
    }

    Object.keys(fields).forEach(function (key) {
        // Nothing is flagged red while someone is still typing their first
        // attempt; after a rejected submit it corrects as they fix it.
        fields[key].addEventListener('input', function () {
            if (attempted) check(key);
            refresh();
        });
        fields[key].addEventListener('blur', function () {
            if (fields[key].value !== '') check(key);
        });
    });

    agree.addEventListener('change', function () {
        if (attempted) {
            const note = form.querySelector('[data-error-for="agree"]');
            note.textContent = agree.checked ? '' : 'Tick the agreement to continue.';
            note.hidden = agree.checked;
        }
        refresh();
    });

    form.addEventListener('submit', function (event) {
        attempted = true;
        const valid = Object.keys(fields).map(check).every(Boolean) && agree.checked;

        if (!valid) {
            event.preventDefault();
            refresh();
            const firstBad = Object.keys(fields).find(function (key) {
                return fields[key].getAttribute('aria-invalid') === 'true';
            });
            (firstBad ? fields[firstBad] : agree).focus();
            return;
        }

        // Submission is already under way; this only stops a second one.
        submit.disabled = true;
        submit.textContent = 'Creating account…';
    });

    // The server may have rejected a field already; those messages are
    // rendered server-side, so a first pass here only has to bring the live
    // surfaces and the submit gate in line with what is in the inputs.
    attempted = {{ $errors->any() ? 'true' : 'false' }};
    refresh();
});
</script>
@endunless
@endsection
