@extends('components.default')

@section("title", "Register - CICT Equipment Borrower System")

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
            <div class="lp-eyebrow"><span class="lp-dot" aria-hidden="true"></span>Get Started</div>
            <h1 class="lp-title">Create your<br>account</h1>
            <p class="lp-sub">Fill in the details below to join.</p>

            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    <div class="lp-alert lp-alert-error"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $error }}</span></div>
                @endforeach
            @endif
            @if (session('status'))
                <div class="lp-alert lp-alert-success"><i class="fa-solid fa-circle-check"></i><span>{{ session('status') }}</span></div>
            @endif

            <form class="lp-form" action="{{ route('register.store') }}" method="POST">
                @csrf

                {{-- Full Name --}}
                <div class="lp-field">
                    <div class="field-label-row">
                        <label for="name" class="field-label">Full Name</label>
                        <span class="field-hint">First and last name</span>
                    </div>
                    <div class="input-wrap">
                        <i class="fa-regular fa-user input-icon"></i>
                        <input type="text" name="name" id="name" placeholder="John Doe" value="{{ old('name') }}" class="ds-input" required>
                    </div>
                </div>

                {{-- Email --}}
                <div class="lp-field">
                    <label for="email" class="field-label">Email</label>
                    <div class="input-wrap">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input type="email" name="email" id="email" placeholder="name@company.com" value="{{ old('email') }}" class="ds-input" required>
                    </div>
                </div>

                {{-- Contact Number --}}
                <div class="lp-field">
                    <div class="field-label-row">
                        <label for="contact_number" class="field-label">Contact Number</label>
                        <span class="field-hint">Optional</span>
                    </div>
                    <div class="input-wrap">
                        <i class="fa-solid fa-phone input-icon" style="font-size:13px"></i>
                        <input type="text" name="contact_number" id="contact_number" placeholder="09XXXXXXXXX" value="{{ old('contact_number') }}" class="ds-input">
                    </div>
                </div>

                {{-- User Type --}}
                <div class="lp-field">
                    <label for="user_type" class="field-label">User Type</label>
                    <div class="input-wrap">
                        <i class="fa-regular fa-user input-icon"></i>
                        <select name="user_type" id="user_type" class="ds-input" required>
                            <option value="" disabled selected>Select user type</option>
                            <option value="Instructor" {{ old('user_type')=='Instructor' ? 'selected' : '' }}>Instructor</option>
                            <option value="Student" {{ old('user_type')=='Student' ? 'selected' : '' }}>Student</option>
                        </select>
                        <i class="fa-solid fa-chevron-down input-icon" style="left:auto; right:14px; font-size:11px; color:#6b7a99"></i>
                    </div>
                </div>

                {{-- Password --}}
                <div class="lp-field">
                    <label for="password" class="field-label">Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock input-icon" style="font-size:13px"></i>
                        <input type="password" name="password" id="password" placeholder="**********" class="ds-input has-trailing" required>
                        <button type="button" class="eye-btn" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>

                {{-- Confirm Password --}}
                <div class="lp-field">
                    <label for="password_confirmation" class="field-label">Confirm Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock input-icon" style="font-size:13px"></i>
                        <input type="password" name="password_confirmation" id="password_confirmation" placeholder="**********" class="ds-input has-trailing" required>
                        <button type="button" class="eye-btn" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>

                {{-- Terms --}}
                <label class="lp-remember">
                    <input type="checkbox" required class="ds-checkbox">
                    <span>I agree to the <a href="#" class="inline-link">Terms &amp; Privacy</a></span>
                </label>

                <button type="submit" class="btn-primary lp-submit">
                    <i class="fa-regular fa-circle-user"></i> Create account
                </button>

                <p class="auth-footer">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
            </form>
        </section>
    </div>
</div>
@push('styles')
    <link rel="stylesheet" href="{{ asset('resources/css/auth.css') }}">
@endpush
@endsection
