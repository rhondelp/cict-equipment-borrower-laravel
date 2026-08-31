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
                        <input type="password" name="password" id="password" placeholder="••••••••" class="ds-input has-trailing" required>
                        <button type="button" class="eye-btn" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>

                {{-- Confirm Password --}}
                <div class="lp-field">
                    <label for="password_confirmation" class="field-label">Confirm Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock input-icon" style="font-size:13px"></i>
                        <input type="password" name="password_confirmation" id="password_confirmation" placeholder="••••••••" class="ds-input has-trailing" required>
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
@endsection

<style>
/* Root shell */
.lp-root {
    min-height: 100dvh; display: flex; align-items: center; justify-content: center;
    padding: 40px 24px; position: relative; overflow: hidden; background: #0a0e1a;
}
.lp-root-register { align-items: flex-start; overflow: auto; }
.lp-root-register .lp-hero { margin: auto 0; }
.lp-grid-overlay {
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.035) 1px, transparent 1px);
    background-size: 32px 32px; pointer-events: none; z-index: 0;
}
.lp-orb { position: absolute; border-radius: 999px; filter: blur(80px); pointer-events: none; z-index: 0; }
.lp-orb-1 { width: 560px; height: 420px; background: rgba(91,141,224,0.12); top: -10%; left: -12%; }
.lp-orb-2 { width: 480px; height: 380px; background: rgba(91,141,224,0.09); bottom: -8%; right: -10%; }
/* Split hero */
.lp-hero {
    position: relative; z-index: 1; display: grid; grid-template-columns: 1fr 1fr;
    width: 100%; max-width: 1080px; min-height: 520px; align-items: center;
}
/* LEFT — Branding */
.lp-left {
    display: flex; flex-direction: column; justify-content: center; align-items: flex-start;
    padding: 56px 48px 56px 0; border-right: 1px solid rgba(255,255,255,0.07);
    align-self: stretch;
}
.lp-logo-ring {
    width: 96px; height: 96px; border-radius: 999px; padding: 6px;
    background: #fff; border: 1px solid rgba(255,255,255,0.10);
    box-shadow: 0 8px 28px rgba(0,0,0,0.40), 0 0 0 6px rgba(91,141,224,0.10);
    display: grid; place-items: center; margin-bottom: 28px; flex-shrink: 0;
    transition: box-shadow 0.3s ease; text-decoration: none;
}
.lp-logo-ring:hover { box-shadow: 0 10px 36px rgba(0,0,0,0.45), 0 0 0 10px rgba(91,141,224,0.14); }
.lp-logo-ring img { width: 100%; height: 100%; object-fit: contain; border-radius: 999px; }
.lp-school { margin: 0 0 4px; font-size: 13px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #5b8de0; }
.lp-college { margin: 0; font-size: 18px; font-weight: 700; line-height: 1.35; color: #e2e8f0; max-width: 340px; }
.lp-divider { width: 40px; height: 2px; background: linear-gradient(90deg, #5b8de0, transparent); border-radius: 999px; margin: 22px 0; }
.lp-tagline { margin: 0; font-size: 14.5px; line-height: 1.65; color: #64748b; max-width: 320px; }
</style>

<style>
/* RIGHT — Auth form panel */
.lp-right {
    display: flex; flex-direction: column; justify-content: center; align-items: flex-start;
    padding: 40px 0 40px 64px;
}
.lp-eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; letter-spacing: 0.10em; text-transform: uppercase; color: #5b8de0; margin-bottom: 16px; }
.lp-dot { width: 7px; height: 7px; border-radius: 999px; background: #5b8de0; box-shadow: 0 0 8px rgba(91,141,224,0.70); animation: lp-pulse 2s ease-in-out infinite; }
@keyframes lp-pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.55;transform:scale(1.25)} }
.lp-title { margin: 0 0 12px; font-size: clamp(30px,3.2vw,40px); font-weight: 800; line-height: 1.12; letter-spacing: -0.035em; color: #fff; }
.lp-sub { margin: 0 0 22px; font-size: 14.5px; line-height: 1.6; color: #64748b; }
/* Alerts */
.lp-alert { display: flex; align-items: center; gap: 10px; width: 100%; max-width: 380px; padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 500; margin-bottom: 10px; }
.lp-alert-error { background: rgba(239,68,68,0.10); border: 1px solid rgba(239,68,68,0.25); color: #fca5a5; }
.lp-alert-success { background: rgba(34,197,94,0.10); border: 1px solid rgba(34,197,94,0.25); color: #86efac; }
/* Form */
.lp-form { display: flex; flex-direction: column; gap: 15px; width: 100%; max-width: 380px; }
.lp-field { display: flex; flex-direction: column; gap: 6px; }
.lp-remember { display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none; padding-top: 2px; }
.lp-remember span { font-size: 13px; font-weight: 500; color: #cbd5e1; line-height: 1.4; }
.lp-remember .ds-checkbox { margin-top: 1px; }
.lp-submit { width: 100%; display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 9px !important; padding: 13px 20px !important; border-radius: 10px !important; font-size: 14.5px !important; font-weight: 600 !important; margin-top: 4px; }
/* Responsive */
@media (max-width: 768px) {
    .lp-hero { grid-template-columns: 1fr; min-height: unset; gap: 32px; }
    .lp-left { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.07); padding: 0 0 32px; align-items: center; text-align: center; align-self: stretch; }
    .lp-college, .lp-tagline { text-align: center; }
    .lp-tagline { max-width: 360px; }
    .lp-divider { margin: 20px auto; }
    .lp-right { padding: 0; align-items: center; text-align: center; }
    .lp-title { text-align: center; }
    .lp-form { max-width: 100%; }
    .lp-alert { max-width: 100%; }
}
</style>

<style>
/* Light mode */
html:not(.dark) .lp-root { background: #f1f5f9; }
html:not(.dark) .lp-grid-overlay { background-image: radial-gradient(circle, rgba(15,23,42,0.05) 1px, transparent 1px); }
html:not(.dark) .lp-orb-1 { background: rgba(91,141,224,0.08); }
html:not(.dark) .lp-orb-2 { background: rgba(91,141,224,0.06); }
html:not(.dark) .lp-left { border-right-color: rgba(15,23,42,0.08); }
html:not(.dark) .lp-logo-ring { border-color: rgba(15,23,42,0.08); box-shadow: 0 8px 28px rgba(15,23,42,0.08), 0 0 0 6px rgba(91,141,224,0.08); }
html:not(.dark) .lp-college { color: #0f172a; }
html:not(.dark) .lp-tagline { color: #64748b; }
html:not(.dark) .lp-title { color: #0f172a; }
html:not(.dark) .lp-sub { color: #64748b; }
html:not(.dark) .lp-remember span { color: #334155; }
html:not(.dark) .lp-alert-error { background: rgba(239,68,68,0.06); border-color: rgba(239,68,68,0.18); color: #dc2626; }
html:not(.dark) .lp-alert-success { background: rgba(34,197,94,0.06); border-color: rgba(34,197,94,0.18); color: #16a34a; }
html:not(.dark) .lp-hero > .lp-left { border-bottom-color: rgba(15,23,42,0.08); }
</style>

