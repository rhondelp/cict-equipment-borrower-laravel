@extends("components.default")

@section("title", "CICT Equipment Borrower System â€” College of Information & Communications Technology, UNM")

@section("content")
<div class="lp-root">

    {{-- Decorative dot-grid texture --}}
    <div class="lp-grid-overlay" aria-hidden="true"></div>
    {{-- Ambient glow orbs --}}
    <div class="lp-orb lp-orb-1" aria-hidden="true"></div>
    <div class="lp-orb lp-orb-2" aria-hidden="true"></div>

    {{-- Two-column split hero --}}
    <div class="lp-hero">

        {{-- LEFT â€” Branding panel --}}
        <aside class="lp-left">
            <div class="lp-logo-ring">
                <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT logo">
            </div>
            <p class="lp-school">University of Northwestern Mindanao</p>
            <p class="lp-college">College of Information &amp; Communications Technology</p>
            <div class="lp-divider"></div>
            <p class="lp-tagline">Request, track and return laboratory equipment in one secure workspace.</p>
        </aside>

        {{-- RIGHT â€” Portal action panel --}}
        <section class="lp-right">
            <div class="lp-eyebrow">
                <span class="lp-dot" aria-hidden="true"></span>
                System Portal
            </div>
            <h1 class="lp-title">CICT Equipment<br>Borrower System</h1>
            <p class="lp-sub">Sign in or create an account to get started.</p>
            <div class="lp-actions">
                <a href="{{ route('login') }}" class="btn-primary lp-btn-login">
                    <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                    Login to System
                </a>
                <a href="{{ route('register') }}" class="lp-btn-register">
                    <i class="fa-regular fa-circle-user" aria-hidden="true"></i>
                    Create account
                </a>
            </div>
            <p class="lp-note">Authorized access for students and instructors</p>
        </section>

    </div>
</div>
@push('styles')
    <link rel="stylesheet" href="{{ asset('resources/css/auth.css') }}">
@endpush
@endsection
