@extends("components.default")

@section("title", "Sign in - CICT Equipment Borrower System")

@section("inline-errors", true)

@section("content")
@php
    $authError = $errors->first();
@endphp

<div class="grid min-h-[100dvh] lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1fr)]">

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
            <h1 class="text-[26px] font-semibold leading-[1.16] tracking-[-0.02em] text-balance lg:text-[34px]">
                Borrow lab equipment without the paper logbook.
            </h1>
            <p class="hidden text-[14.5px] leading-[1.6] text-white/70 text-pretty lg:block">
                Request an item, see what is still on the shelf, and get a reminder before it is due back.
            </p>

            <ul class="flex flex-col gap-2.5 pt-1 lg:gap-[10px]">
                @if($inventory)
                    <li class="flex items-center gap-2.5 text-[13.5px] text-white/80">
                        <span class="w-[5px] h-[5px] rounded-full shrink-0 bg-[#63d99b]" aria-hidden="true"></span>
                        <span>{{ $inventory['units'] }} {{ str('unit')->plural($inventory['units']) }} tracked across {{ $inventory['types'] }} item {{ str('type')->plural($inventory['types']) }}</span>
                    </li>
                @endif
                <li class="flex items-center gap-2.5 text-[13.5px] text-white/80">
                    <span class="w-[5px] h-[5px] rounded-full shrink-0 bg-[#63d99b]" aria-hidden="true"></span>
                    CICT Office open {{ \App\Support\OfficeHours::fromConfig()->label() }}
                </li>
                <li class="flex items-center gap-2.5 text-[13.5px] text-white/80">
                    <span class="w-[5px] h-[5px] rounded-full shrink-0 bg-[#63d99b]" aria-hidden="true"></span>
                    Requests are reviewed within one working day
                </li>
            </ul>
        </div>

        <p class="text-[12px] text-white/50">College of Information &amp; Communications Technology</p>
    </aside>

    <main class="flex items-center justify-center px-6 py-10 sm:px-8 lg:px-8 lg:py-12">
        <div class="flex w-full max-w-[392px] flex-col gap-[22px]">

            @if(session('password_reset'))
                @php($officeEmail = config('office.email'))
                <div role="status" data-password-reset-done
                     class="flex items-start gap-3 rounded-[11px] border border-success-200 bg-success-50 px-4 py-3.5">
                    <span class="grid mt-px rounded-full w-7 h-7 shrink-0 place-items-center bg-success-100 text-success-700" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none"><path d="M3.4 8.4 6.4 11.4l6.2-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <div class="flex flex-col min-w-0 gap-1">
                        <p class="text-[14px] font-semibold text-neutral-900">Password updated</p>
                        <p class="text-[13px] leading-[1.55] text-neutral-700 text-pretty">
                            Sign in with your new password. Anywhere else you were signed in has been logged out.
                        </p>
                        <p class="text-[12.5px] leading-[1.55] text-neutral-600 text-pretty">
                            Didn't make this change? Tell the equipment office
                            @if($officeEmail)
                                at <a href="mailto:{{ $officeEmail }}" class="font-medium text-primary-700 [overflow-wrap:anywhere] hover:text-primary-800">{{ $officeEmail }}</a>.
                            @else
                                at the CICT Office.
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            <div class="flex flex-col gap-[5px]">
                <h2 class="text-[24px] font-semibold tracking-[-0.015em] text-neutral-900">Sign in</h2>
                <p class="text-[13.5px] text-neutral-600 text-pretty">
                    Use your school account. Students and instructors both sign in here.
                </p>
            </div>

            @if($authError)
                <div role="alert" data-login-error
                     class="flex items-start gap-2.5 rounded-[10px] bg-danger-50 px-[13px] py-[11px] text-[13px] text-danger-700 text-pretty">
                    <span class="mt-[7px] h-1.5 w-1.5 shrink-0 rounded-full bg-danger-600" aria-hidden="true"></span>
                    <span>{{ $authError }}</span>
                </div>
            @endif

            <form action="{{ route('login.store') }}" method="POST" novalidate class="flex flex-col gap-[15px]" id="login-form">
                @csrf

                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-[12.5px] font-medium text-neutral-900">School email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                           autocomplete="username" required autofocus
                           placeholder="name@nmsc.edu.ph"
                           aria-describedby="email-error"
                           @if($errors->has('email')) aria-invalid="true" @endif
                           class="h-11 rounded-[10px] border px-[13px] text-[14px] text-neutral-900 outline-none transition-colors placeholder:text-neutral-400 focus:border-primary-500
                                  {{ $errors->has('email') ? 'border-danger-300' : 'border-neutral-200' }}">
                    <p id="email-error" data-error-for="email" class="text-[12px] text-danger-700" hidden></p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <div class="flex items-baseline justify-between gap-3">
                        <label for="password" class="text-[12.5px] font-medium text-neutral-900">Password</label>
                        <a href="{{ route('password.request') }}" class="text-[12.5px] font-medium text-primary-700 hover:text-primary-800">
                            Forgot password?
                        </a>
                    </div>

                    <div class="flex h-11 items-center gap-2 rounded-[10px] border pl-[13px] pr-1.5 transition-colors focus-within:border-primary-500
                                {{ $errors->has('password') ? 'border-danger-300' : 'border-neutral-200' }}"
                         data-password-wrap>
                        <input type="password" name="password" id="password" required
                               autocomplete="current-password" placeholder="••••••••"
                               aria-describedby="password-error"
                               @if($errors->has('password')) aria-invalid="true" @endif
                               class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[14px] text-neutral-900 outline-none placeholder:text-neutral-400">
                        <button type="button" id="password-toggle"
                                class="h-8 shrink-0 rounded-lg px-2.5 text-[12.5px] font-medium text-neutral-600 hover:bg-neutral-100 hover:text-neutral-800"
                                aria-controls="password" aria-pressed="false">Show</button>
                    </div>
                    <p id="password-error" data-error-for="password" class="text-[12px] text-danger-700" hidden></p>
                </div>

                <label class="flex cursor-pointer items-center gap-2.5 text-[13px] text-neutral-700">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                           class="w-[15px] h-[15px] cursor-pointer accent-primary-600">
                    Keep me signed in on this computer
                </label>

                <button type="submit" id="login-submit"
                        class="btn-primary h-[46px] min-h-0 rounded-[10px] px-5 text-[14.5px] font-semibold">
                    Sign in
                </button>
            </form>

            <div class="flex items-center gap-3" aria-hidden="true">
                <span class="flex-1 h-px bg-neutral-200"></span>
                <span class="text-[11.5px] text-neutral-500">OR</span>
                <span class="flex-1 h-px bg-neutral-200"></span>
            </div>

            <div class="flex flex-col gap-3">
                <p class="text-[13px] text-neutral-600 text-pretty">
                    No account yet? Students and instructors can create one with their school email —
                    the CICT office confirms instructor access.
                </p>
                <a href="{{ route('register') }}"
                   class="flex h-11 items-center justify-center rounded-[10px] border border-neutral-200 text-[14px] font-semibold text-neutral-700 transition-colors hover:bg-neutral-50 hover:text-neutral-900">
                    Request an account
                </a>
            </div>

            <nav class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[12px] text-neutral-500" aria-label="Legal">
                <a href="{{ route('legal.privacy') }}" class="text-neutral-600 hover:text-neutral-900">Privacy policy</a>
                <a href="{{ route('legal.terms') }}" class="text-neutral-600 hover:text-neutral-900">Terms of service</a>
                <span class="sm:ml-auto">Need help? Ask at the CICT Office.</span>
            </nav>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('login-form');
    if (!form) return;

    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const submit = document.getElementById('login-submit');
    const toggle = document.getElementById('password-toggle');

    toggle.addEventListener('click', function () {
        const revealed = password.type === 'text';
        password.type = revealed ? 'password' : 'text';
        toggle.textContent = revealed ? 'Show' : 'Hide';
        toggle.setAttribute('aria-pressed', revealed ? 'false' : 'true');
        password.focus();
    });

    const rules = {
        email: function (value) {
            if (value.trim() === '') return 'Enter your school email address.';
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())) return 'That does not look like an email address.';
            return '';
        },
        password: function (value) {
            return value === '' ? 'Enter your password.' : '';
        },
    };

    const fields = { email: email, password: password };
    let attempted = false;

    function borderTarget(name) {
        return name === 'password' ? password.closest('[data-password-wrap]') : email;
    }

    function check(name) {
        const field = fields[name];
        const message = rules[name](field.value);
        const note = document.querySelector('[data-error-for="' + name + '"]');
        const target = borderTarget(name);

        note.textContent = message;
        note.hidden = message === '';
        field.setAttribute('aria-invalid', message ? 'true' : 'false');
        target.classList.toggle('border-danger-300', message !== '');
        target.classList.toggle('border-neutral-200', message === '');

        return message === '';
    }

    Object.keys(fields).forEach(function (name) {
        fields[name].addEventListener('input', function () {
            if (attempted) check(name);
        });
        fields[name].addEventListener('blur', function () {
            if (fields[name].value !== '') check(name);
        });
    });

    form.addEventListener('submit', function (event) {
        attempted = true;
        const valid = Object.keys(fields).map(check).every(Boolean);

        if (!valid) {
            event.preventDefault();
            const firstBad = Object.keys(fields).find(function (name) {
                return fields[name].getAttribute('aria-invalid') === 'true';
            });
            if (firstBad) fields[firstBad].focus();
            return;
        }

        submit.disabled = true;
        submit.textContent = 'Signing in…';
    });
});
</script>
@endsection
