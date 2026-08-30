@extends("components.default")

@section("title", "Login - CICT Equipment Borrower System")

@section("content")
<div class="theme-shell">
    <div class="auth-card animate-fade-in">

        {{-- Header --}}
        <div class="flex items-start gap-3.5 mb-7">
            <div class="auth-logo shrink-0">
                <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT">
            </div>
            <div class="pt-1">
                <h1 class="auth-title">Welcome back</h1>
                <p class="auth-subtitle">Sign in to your account to continue</p>
            </div>
        </div>

        <p class="text-[13px] text-[#8b93a8] mb-5 leading-relaxed">Please enter your credentials to access the CICT Equipment Borrower System.</p>
        {{-- Validation / flash alerts handled by global components.alerts --}}

        <form class="space-y-5" action="{{ route('login.store') }}" method="POST">
            @csrf

            {{-- Email --}}
            <div>
                <div class="field-label-row">
                    <label for="email" class="field-label">Email Address</label>
                </div>
                <div class="input-wrap">
                    <i class="fa-regular fa-envelope input-icon"></i>
                    <input type="email" name="email" id="email" placeholder="name@company.com" value="{{ old('email') }}"
                           class="ds-input" required>
                </div>
            </div>

            {{-- Password --}}
            <div>
                <div class="field-label-row">
                    <label for="password" class="field-label">Password</label>
                    <a href="#" class="field-hint hover:text-[#aab4cc] transition">Forgot password?</a>
                </div>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock input-icon" style="font-size:13px"></i>
                    <input type="password" name="password" id="password" placeholder="••••••••"
                           class="ds-input has-trailing" required>
                    <button type="button" class="eye-btn" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                </div>
            </div>

            {{-- Remember --}}
            <label class="flex items-center gap-2.5 cursor-pointer select-none pt-1">
                <input type="checkbox" name="remember" class="ds-checkbox">
                <span class="text-[13px] font-medium text-slate-200">Remember me</span>
            </label>

            <button type="submit" class="btn-primary mt-2">
                Sign in
            </button>

            <p class="auth-footer">
                Don't have an account? <a href="{{ route('register') }}">Sign up</a>
            </p>
        </form>
    </div>
</div>
@endsection
