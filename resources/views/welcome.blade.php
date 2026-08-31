@extends("components.default")

@section("title", "CICT Equipment Borrower System — College of Information & Communications Technology, UNM")

@section("content")
<div class="landing-simple">
    <main class="landing-card" id="main-content">
        {{-- Logo — centered elevated emblem with halo --}}
        <div class="landing-logo" aria-hidden="true">
            <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT logo">
        </div>

        {{-- Institution kicker badge --}}
        <div class="landing-kicker-wrap">
            <p class="landing-kicker">College of Information &amp; Communications Technology &middot; UNM</p>
        </div>

        {{-- System Title --}}
        <h1 class="landing-title">CICT Equipment Borrower System</h1>

        {{-- Subtitle / Description --}}
        <p class="landing-desc">
            Request, track and return laboratory equipment in one secure workspace.
        </p>

        {{-- Action Buttons --}}
        <div class="landing-actions">
            <a href="{{ route('login') }}" class="btn-primary landing-primary">
                Login to System
                <i class="fa-solid fa-arrow-right text-[11px]" aria-hidden="true"></i>
            </a>
            <a href="{{ route('register') }}" class="landing-secondary">Create account</a>
        </div>
    </main>

    {{-- Footer Access Note --}}
    <footer class="landing-foot-wrap">
        <p class="landing-foot">Authorized access for students and instructors</p>
    </footer>
</div>

<style>
.landing-simple {
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px 24px;
    text-align: center;
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(ellipse 900px 500px at 50% -8%, rgba(91,141,224,0.10) 0%, transparent 62%),
        linear-gradient(180deg, #0a0e1a 0%, #0e1426 100%);
}
.landing-simple::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 760px 420px at 50% 28%, rgba(91,141,224,0.06), transparent 68%);
    pointer-events: none;
}

/* Card Container */
.landing-card {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 600px;
    background: rgba(19, 26, 43, 0.72);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 20px;
    box-shadow:
        0 24px 64px rgba(0, 0, 0, 0.50),
        0 0 0 1px rgba(255, 255, 255, 0.03) inset;
    padding: 44px 36px 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* Logo */
.landing-logo {
    width: 100px;
    height: 100px;
    border-radius: 999px;
    padding: 6px;
    background: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow:
        0 12px 32px rgba(0, 0, 0, 0.40),
        0 0 0 1px rgba(255, 255, 255, 0.06) inset,
        0 0 32px rgba(91, 141, 224, 0.20);
    display: grid;
    place-items: center;
    margin-bottom: 20px;
    flex-shrink: 0;
    transition: transform 0.25s ease;
}
.landing-logo:hover {
    transform: scale(1.03);
}
.landing-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 999px;
}

/* Kicker Badge */
.landing-kicker-wrap {
    margin-bottom: 14px;
}
.landing-kicker {
    margin: 0;
    font-size: 12.5px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #94a3b8;
    background: rgba(91, 141, 224, 0.08);
    border: 1px solid rgba(91, 141, 224, 0.16);
    padding: 5px 14px;
    border-radius: 999px;
    display: inline-block;
}

/* Title */
.landing-title {
    margin: 0 0 12px;
    font-size: clamp(32px, 5vw, 44px);
    font-weight: 800;
    line-height: 1.15;
    letter-spacing: -0.035em;
    color: #ffffff;
    text-wrap: balance;
}

/* Description */
.landing-desc {
    margin: 0 0 28px;
    font-size: 16.5px;
    line-height: 1.55;
    color: #94a3b8;
    max-width: 480px;
    text-wrap: pretty;
}

/* Actions */
.landing-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
    width: 100%;
    max-width: 440px;
}
.landing-primary {
    flex: 1 1 180px;
    min-width: 160px;
    width: auto !important;
    padding: 13px 24px !important;
    border-radius: 10px !important;
    font-size: 15px !important;
    font-weight: 600 !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.landing-secondary {
    flex: 1 1 180px;
    min-width: 160px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 13px 24px;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    letter-spacing: -0.01em;
    text-decoration: none;
    color: #e2e8f0;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.10);
    transition: background 200ms, border-color 200ms, transform 200ms;
}
.landing-secondary:hover {
    background: rgba(255, 255, 255, 0.10);
    border-color: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
}
/* Footer note */
.landing-foot-wrap {
    position: relative;
    z-index: 1;
    margin-top: 28px;
}
.landing-foot {
    margin: 0;
    font-size: 12px;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: rgba(148, 163, 184, 0.70);
    text-align: center;
}

/* Responsive */
@media (max-width: 640px) {
    .landing-simple { padding: 24px 16px 20px; }
    .landing-card { padding: 32px 20px 28px; border-radius: 16px; }
    .landing-logo { width: 84px; height: 84px; margin-bottom: 16px; }
    .landing-kicker { font-size: 11px; padding: 4px 12px; }
    .landing-title { font-size: clamp(26px, 7.5vw, 34px); margin-bottom: 10px; }
    .landing-desc { font-size: 14.5px; margin-bottom: 24px; }
    .landing-actions { flex-direction: column; width: 100%; }
    .landing-actions a { width: 100% !important; flex: none; justify-content: center; }
    .landing-foot-wrap { margin-top: 20px; }
    .landing-foot { font-size: 11px; }
}

/* Light mode */
html:not(.dark) .landing-simple {
    background:
        radial-gradient(ellipse 900px 500px at 50% -8%, rgba(91,141,224,0.08) 0%, transparent 62%),
        linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}
html:not(.dark) .landing-simple::before {
    background: radial-gradient(ellipse 760px 420px at 50% 28%, rgba(91,141,224,0.04), transparent 68%);
}
html:not(.dark) .landing-card {
    background: rgba(255, 255, 255, 0.88);
    border-color: rgba(15, 23, 42, 0.08);
    box-shadow:
        0 20px 48px rgba(15, 23, 42, 0.08),
        0 1px 2px rgba(15, 23, 42, 0.04);
}
html:not(.dark) .landing-kicker {
    color: #475569;
    background: rgba(91, 141, 224, 0.08);
    border-color: rgba(91, 141, 224, 0.18);
}
html:not(.dark) .landing-title {
    color: #0f172a;
}
html:not(.dark) .landing-desc {
    color: #475569;
}
html:not(.dark) .landing-secondary {
    background: #ffffff;
    border-color: rgba(15, 23, 42, 0.12);
    color: #0f172a;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
}
html:not(.dark) .landing-secondary:hover {
    background: #f8fafc;
    border-color: rgba(15, 23, 42, 0.18);
}
html:not(.dark) .landing-foot {
    color: #64748b;
}
html:not(.dark) .landing-logo {
    background: #ffffff;
    border-color: rgba(15, 23, 42, 0.08);
    box-shadow:
        0 10px 28px rgba(15, 23, 42, 0.08),
        0 0 0 1px rgba(15, 23, 42, 0.06) inset;
}
</style>
@endsection
