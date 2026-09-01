@extends("components.default")

@section("title", "Login - CICT Equipment Borrower System")

@section("content")
<div class="lp-root">
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
            <div class="lp-eyebrow"><span class="lp-dot" aria-hidden="true"></span>Welcome Back</div>
            <h1 class="lp-title">Sign in to<br>your account</h1>
            <p class="lp-sub">Enter your credentials to continue.</p>
            @if ($errors->any())
                <div class="lp-alert lp-alert-error"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $errors->first() }}</span></div>
            @endif
            @if (session('status'))
                <div class="lp-alert lp-alert-success"><i class="fa-solid fa-circle-check"></i><span>{{ session('status') }}</span></div>
            @endif
            <form class="lp-form" action="{{ route('login.store') }}" method="POST">
                @csrf
                <div class="lp-field">
                    <label for="email" class="field-label">Email Address</label>
                    <div class="input-wrap">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input type="email" name="email" id="email" placeholder="name@company.com" value="{{ old('email') }}" class="ds-input" required>
                    </div>
                </div>
                <div class="lp-field">
                    <div class="field-label-row">
                        <label for="password" class="field-label">Password</label>
                        <a href="#" class="field-hint">Forgot password?</a>
                    </div>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock input-icon" style="font-size:13px"></i>
                        <input type="password" name="password" id="password" placeholder="**********" class="ds-input has-trailing" required>
                        <button type="button" class="eye-btn" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>
                <label class="lp-remember">
                    <input type="checkbox" name="remember" class="ds-checkbox">
                    <span>Remember me</span>
                </label>
                <button type="submit" class="btn-primary lp-submit">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign in
                </button>
                <p class="auth-footer">Don't have an account? <a href="{{ route('register') }}">Sign up</a></p>
            </form>
        </section>
    </div>
</div>
@push('styles')
    <link rel="stylesheet" href="{{ asset('resources/css/auth.css') }}">
@endpush
@endsection
