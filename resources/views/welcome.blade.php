@extends("components.default")

@section("title", "CICT Equipment Borrower System — College of Information & Communications Technology, UNM")

@section("content")
<div class="lp-root">

    {{-- Decorative dot-grid texture --}}
    <div class="lp-grid-overlay" aria-hidden="true"></div>
    {{-- Ambient glow orbs --}}
    <div class="lp-orb lp-orb-1" aria-hidden="true"></div>
    <div class="lp-orb lp-orb-2" aria-hidden="true"></div>

    {{-- Two-column split hero --}}
    <div class="lp-hero">

        {{-- LEFT — Branding panel --}}
        <aside class="lp-left">
            <div class="lp-logo-ring">
                <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT logo">
            </div>
            <p class="lp-school">University of Northwestern Mindanao</p>
            <p class="lp-college">College of Information &amp; Communications Technology</p>
            <div class="lp-divider"></div>
            <p class="lp-tagline">Request, track and return laboratory equipment in one secure workspace.</p>
        </aside>

        {{-- RIGHT — Portal action panel --}}
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

<style>
/* ===== Root shell ===== */
.lp-root {
    min-height: 100dvh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 24px;
    position: relative;
    overflow: hidden;
    background: #0a0e1a;
}
.lp-grid-overlay {
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.035) 1px, transparent 1px);
    background-size: 32px 32px;
    pointer-events: none; z-index: 0;
}
.lp-orb { position: absolute; border-radius: 999px; filter: blur(80px); pointer-events: none; z-index: 0; }
.lp-orb-1 { width: 560px; height: 420px; background: rgba(91,141,224,0.12); top: -10%; left: -12%; }
.lp-orb-2 { width: 480px; height: 380px; background: rgba(91,141,224,0.09); bottom: -8%; right: -10%; }

/* ===== Split hero ===== */
.lp-hero {
    position: relative; z-index: 1;
    display: grid; grid-template-columns: 1fr 1fr;
    width: 100%; max-width: 1080px; min-height: 520px;
}

/* ===== LEFT — Branding ===== */
.lp-left {
    display: flex; flex-direction: column; justify-content: center; align-items: flex-start;
    padding: 56px 48px 56px 0;
    border-right: 1px solid rgba(255,255,255,0.07);
}
.lp-logo-ring {
    width: 96px; height: 96px; border-radius: 999px; padding: 6px;
    background: #fff; border: 1px solid rgba(255,255,255,0.10);
    box-shadow: 0 8px 28px rgba(0,0,0,0.40), 0 0 0 6px rgba(91,141,224,0.10);
    display: grid; place-items: center; margin-bottom: 28px; flex-shrink: 0;
    transition: box-shadow 0.3s ease;
}
.lp-logo-ring:hover {
    box-shadow: 0 10px 36px rgba(0,0,0,0.45), 0 0 0 10px rgba(91,141,224,0.14);
}
.lp-logo-ring img { width: 100%; height: 100%; object-fit: contain; border-radius: 999px; }
.lp-school {
    margin: 0 0 4px; font-size: 13px; font-weight: 700;
    letter-spacing: 0.07em; text-transform: uppercase; color: #5b8de0;
}
.lp-college {
    margin: 0; font-size: 18px; font-weight: 700; line-height: 1.35; color: #e2e8f0; max-width: 340px;
}
.lp-divider {
    width: 40px; height: 2px;
    background: linear-gradient(90deg, #5b8de0, transparent);
    border-radius: 999px; margin: 22px 0;
}
.lp-tagline { margin: 0; font-size: 14.5px; line-height: 1.65; color: #64748b; max-width: 320px; }
</style>

<style>
/* ===== RIGHT — Portal panel ===== */
.lp-right {
    display: flex; flex-direction: column; justify-content: center; align-items: flex-start;
    padding: 56px 0 56px 64px;
}
.lp-eyebrow {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 12px; font-weight: 700; letter-spacing: 0.10em;
    text-transform: uppercase; color: #5b8de0; margin-bottom: 18px;
}
.lp-dot {
    width: 7px; height: 7px; border-radius: 999px; background: #5b8de0;
    box-shadow: 0 0 8px rgba(91,141,224,0.70);
    animation: lp-pulse 2s ease-in-out infinite;
}
@keyframes lp-pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.55;transform:scale(1.25)} }
.lp-title {
    margin: 0 0 14px; font-size: clamp(32px,3.5vw,44px); font-weight: 800;
    line-height: 1.12; letter-spacing: -0.035em; color: #fff;
}
.lp-sub { margin: 0 0 32px; font-size: 14.5px; line-height: 1.6; color: #64748b; }
.lp-actions { display: flex; flex-direction: column; gap: 12px; width: 100%; max-width: 300px; margin-bottom: 24px; }
.lp-btn-login {
    width: 100% !important; display: inline-flex !important; align-items: center !important;
    justify-content: center !important; gap: 9px !important; padding: 13px 20px !important;
    border-radius: 10px !important; font-size: 14.5px !important; font-weight: 600 !important;
}
.lp-btn-register {
    width: 100%; display: inline-flex; align-items: center; justify-content: center;
    gap: 9px; padding: 13px 20px; border-radius: 10px; font-size: 14.5px; font-weight: 600;
    text-decoration: none; color: #cbd5e1; background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.09);
    transition: background 200ms, border-color 200ms, transform 200ms;
}
.lp-btn-register:hover { background: rgba(255,255,255,0.09); border-color: rgba(255,255,255,0.15); transform: translateY(-1px); }
.lp-note { margin: 0; font-size: 11.5px; letter-spacing: 0.04em; text-transform: uppercase; color: rgba(148,163,184,0.55); }

/* ===== Responsive ===== */
@media (max-width: 768px) {
    .lp-hero { grid-template-columns: 1fr; min-height: unset; gap: 36px; }
    .lp-left { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.07); padding: 0 0 36px; align-items: center; text-align: center; }
    .lp-college, .lp-tagline { text-align: center; }
    .lp-tagline { max-width: 360px; }
    .lp-divider { margin: 20px auto; }
    .lp-right { padding: 0; align-items: center; text-align: center; }
    .lp-title { text-align: center; }
    .lp-actions { max-width: 100%; }
    .lp-note { text-align: center; }
}
</style>

<style>
/* ===== Light mode ===== */
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
html:not(.dark) .lp-btn-register { background: #fff; border-color: rgba(15,23,42,0.12); color: #334155; box-shadow: 0 1px 3px rgba(15,23,42,0.06); }
html:not(.dark) .lp-btn-register:hover { background: #f8fafc; border-color: rgba(15,23,42,0.18); }
html:not(.dark) .lp-note { color: #94a3b8; }
</style>
@endsection
