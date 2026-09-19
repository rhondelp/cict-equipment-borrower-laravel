@extends("components.default")

@section("title", "Forgot Password - CICT Equipment Borrower System")

{{-- These pages render validation errors inline, next to the form.
     components/alerts.blade.php checks for this section and skips its
     SweetAlert modal when it is present, so a failed submit no longer
     reports the same message twice. --}}
@section("inline-errors", true)

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
            <div class="lp-eyebrow"><span class="lp-dot" aria-hidden="true"></span>Account Recovery</div>
            <h1 class="lp-title">Forgot your<br>password?</h1>
            <p class="lp-sub">Enter your email address and we'll send you a link to choose a new one.</p>

            @if ($errors->any())
                <div class="lp-alert lp-alert-error"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $errors->first() }}</span></div>
            @endif
            @if (session('status'))
                <div class="lp-alert lp-alert-success"><i class="fa-solid fa-circle-check"></i><span>{{ session('status') }}</span></div>
            @endif

            <form class="lp-form" action="{{ route('password.email') }}" method="POST">
                @csrf
                <div class="lp-field">
                    <label for="email" class="field-label">Email Address</label>
                    <div class="input-wrap">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input type="email" name="email" id="email" placeholder="name@company.com" value="{{ old('email') }}" class="ds-input" required autofocus>
                    </div>
                </div>

                <button type="submit" class="btn-primary lp-submit">
                    <i class="fa-solid fa-paper-plane"></i> Send reset link
                </button>

                <p class="auth-footer">Remembered your password? <a href="{{ route('login') }}">Sign in</a></p>
            </form>
                <nav class="lp-legal" aria-label="Legal">
                    <a href="{{ route('legal.privacy') }}">Privacy policy</a>
                    <a href="{{ route('legal.terms') }}">Terms of service</a>
                </nav>
        </section>
    </div>
</div>
@push('styles')
    <link rel="stylesheet" href="{{ asset('resources/css/auth.css') }}">
@endpush
@endsection
