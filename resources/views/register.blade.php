@extends('components.default')

@section("title", "Register - CICT Equipment Borrower System")

@section("content")
<div class="theme-shell" style="align-items:flex-start; padding-top:32px; padding-bottom:32px; overflow:auto">
    <div class="auth-card animate-fade-in" style="max-width:480px">

        {{-- Header --}}
        <div class="flex items-start gap-3.5 mb-2">
            <div class="auth-logo shrink-0">
                <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT">
            </div>
            <div class="pt-1">
                <h1 class="auth-title">Create your account</h1>
                <p class="auth-subtitle">Get started with CICT Equipment Borrower</p>
            </div>
        </div>
        <p class="text-[13px] text-[#8b93a8] mb-6 leading-relaxed">Please fill in the details below to create your account.</p>

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                @foreach ($errors->all() as $error)
                    <div class="flex items-center gap-2"><i class="fa-solid fa-circle-exclamation text-xs"></i> {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form class="space-y-4" action="{{ route('register') }}" method="POST">
            @csrf

            {{-- Full Name --}}
            <div>
                <div class="field-label-row">
                    <label for="name" class="field-label">Full Name</label>
                    <span class="field-hint">Your first and last name</span>
                </div>
                <div class="input-wrap">
                    <i class="fa-regular fa-user input-icon"></i>
                    <input type="text" name="name" id="name" placeholder="John Doe" value="{{ old('name') }}"
                           class="ds-input" required>
                </div>
            </div>

            {{-- Email --}}
            <div>
                <div class="field-label-row">
                    <label for="email" class="field-label">Email</label>
                </div>
                <div class="input-wrap">
                    <i class="fa-regular fa-envelope input-icon"></i>
                    <input type="email" name="email" id="email" placeholder="name@company.com" value="{{ old('email') }}"
                           class="ds-input" required>
                </div>
            </div>

            {{-- Contact Number --}}
            <div>
                <div class="field-label-row">
                    <label for="contact_number" class="field-label">Contact Number</label>
                    <span class="field-hint">Optional</span>
                </div>
                <div class="input-wrap">
                    <i class="fa-solid fa-phone input-icon" style="font-size:13px"></i>
                    <input type="text" name="contact_number" id="contact_number" placeholder="09XXXXXXXXX" value="{{ old('contact_number') }}"
                           class="ds-input">
                </div>
            </div>

            {{-- User Type --}}
            <div>
                <div class="field-label-row">
                    <label for="user_type" class="field-label">User Type</label>
                </div>
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
            <div>
                <div class="field-label-row">
                    <label for="password" class="field-label">Password</label>
                </div>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock input-icon" style="font-size:13px"></i>
                    <input type="password" name="password" id="password" placeholder="••••••••"
                           class="ds-input has-trailing" required>
                    <button type="button" class="eye-btn" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                </div>
            </div>

            {{-- Confirm Password --}}
            <div>
                <div class="field-label-row">
                    <label for="password_confirmation" class="field-label">Confirm Password</label>
                </div>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock input-icon" style="font-size:13px"></i>
                    <input type="password" name="password_confirmation" id="password_confirmation" placeholder="••••••••"
                           class="ds-input has-trailing" required>
                    <button type="button" class="eye-btn" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                </div>
            </div>

            {{-- Terms --}}
            <label class="flex items-start gap-2.5 cursor-pointer select-none pt-1">
                <input type="checkbox" required class="ds-checkbox mt-0.5">
                <span class="text-[13px] leading-[1.4] text-slate-200">I agree to the <a href="#" class="inline-link">Terms &amp; Privacy</a></span>
            </label>

            <button type="submit" class="btn-primary mt-1">
                Create account
            </button>

            <p class="auth-footer">
                Already have an account? <a href="{{ route('login') }}">Sign in</a>
            </p>
        </form>
    </div>
</div>
@endsection
