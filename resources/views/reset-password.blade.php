@extends("components.default")

@section("title", "Reset Password - CICT Equipment Borrower System")

@section("content")
<div class="lp-root lp-root-register">
    <div class="lp-grid-overlay" aria-hidden="true"></div>
    <div class="lp-orb lp-orb-1" aria-hidden="true"></div>
    <div class="lp-orb lp-orb-2" aria-hidden="true"></div>

    <div class="lp-hero">
        <aside class="lp-left">
            <a href="{{ url('/') }}" class="lp-logo-ring" aria-label="Back to home">
                <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT logo">
            </a>
            <p class="lp-school">University of Northwestern Mindanao</p>
            <p class="lp-college">College of Information &amp; Communications Technology</p>
            <div class="lp-divider"></div>
            <p class="lp-tagline">Request, track and return laboratory equipment in one secure workspace.</p>
        </aside>

        <section class="lp-right">
            <div class="lp-eyebrow"><span class="lp-dot" aria-hidden="true"></span>Account Recovery</div>
            <h1 class="lp-title">Choose a new<br>password</h1>
            <p class="lp-sub">Pick something you'll remember — you'll use it to sign in from now on.</p>

            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    <div class="lp-alert lp-alert-error"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $error }}</span></div>
                @endforeach
            @endif
            @if (session('status'))
                <div class="lp-alert lp-alert-success"><i class="fa-solid fa-circle-check"></i><span>{{ session('status') }}</span></div>
            @endif

            <form class="lp-form" action="{{ route('password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                {{-- Email --}}
                <div class="lp-field">
                    <label for="email" class="field-label">Email</label>
                    <div class="input-wrap">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input type="email" name="email" id="email" placeholder="name@company.com" value="{{ old('email', $email) }}" class="ds-input" required>
                    </div>
                </div>

                {{-- New Password --}}
                <div class="lp-field">
                    <label for="password" class="field-label">New Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock input-icon" style="font-size:13px"></i>
                        <input type="password" name="password" id="password" placeholder="**********" class="ds-input has-trailing" required autofocus>
                        <button type="button" class="eye-btn" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>

                {{-- Confirm Password --}}
                <div class="lp-field">
                    <label for="password_confirmation" class="field-label">Confirm New Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock input-icon" style="font-size:13px"></i>
                        <input type="password" name="password_confirmation" id="password_confirmation" placeholder="**********" class="ds-input has-trailing" required>
                        <button type="button" class="eye-btn" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>

                <button type="submit" class="btn-primary lp-submit">
                    <i class="fa-solid fa-key"></i> Reset password
                </button>

                <p class="auth-footer">Back to <a href="{{ route('login') }}">Sign in</a></p>
            </form>
        </section>
    </div>
</div>
@push('styles')
    <link rel="stylesheet" href="{{ asset('resources/css/auth.css') }}">
@endpush
@endsection
