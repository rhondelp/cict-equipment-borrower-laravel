@extends("components.default")

@section("title", "CICT Equipment Borrower System — College of Information & Communications Technology, UNM")

@section("content")
<div class="landing-simple">
    <main class="landing-content" id="main-content">
        {{-- Logo — prominent centered emblem with ambient halo --}}
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
    padding: 48px 20px 32px;
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
    background: radial-gradient(ellipse 760px 420px at 50% 32%, rgba(91,141,224,0.06), transparent 68%);
    pointer-events: none;
}

/* Open Content Wrapper — clean & frameless (no card) */
.landing-content {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 680px;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: auto 0;
}

/* Logo Emblem */
.landing-logo {
    width: 110px;
    height: 110px;
    border-radius: 999px;
    padding: 7px;
    background: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow:
        0 14px 36px rgba(0, 0, 0, 0.45),
        0 0 0 1px rgba(255, 255, 255, 0.08) inset,
        0 0 36px rgba(91, 141, 224, 0.22);
    display: grid;
    place-items: center;
    margin-bottom: 24px;
    flex-shrink: 0;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.landing-logo:hover {
    transform: scale(1.03);
    box-shadow:
        0 16px 40px rgba(0, 0, 0, 0.50),
        0 0 44px rgba(91, 141, 224, 0.30);
}
.landing-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 999px;
}

/* Kicker Badge */
.landing-kicker-wrap {
    margin-bottom: 16px;
}
.landing-kicker {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: #94a3b8;
    background: rgba(91, 141, 224, 0.08);
    border: 1px solid rgba(91, 141, 224, 0.18);
    padding: 6px 16px;
    border-radius: 999px;
    display: inline-block;
}

/* Title */
.landing-title {
    margin: 0 0 16px;
    font-size: clamp(36px, 5.5vw, 54px);
    font-weight: 800;
    line-height: 1.12;
    letter-spacing: -0.038em;
    color: #ffffff;
    text-wrap: balance;
}

/* Description */
.landing-desc {
    margin: 0 0 34px;
    font-size: clamp(16px, 2vw, 18.5px);
    line-height: 1.6;
    color: #94a3b8;
    max-width: 520px;
    text-wrap: pretty;
}

/* Actions Row */
.landing-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    justify-content: center;
    align-items: center;
    width: 100%;
    max-width: 480px;
}
.landing-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 32px !important;
    border-radius: 999px !important;
    font-size: 15.5px !important;
    font-weight: 600 !important;
    min-width: 180px;
    width: auto !important;
}
.landing-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 14px 32px;
    border-radius: 999px;
    font-size: 15.5px;
    font-weight: 600;
    letter-spacing: -0.01em;
    text-decoration: none;
    min-width: 180px;
    color: #e2e8f0;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.10);
    transition: background 200ms, border-color 200ms, transform 200ms;
}
.landing-secondary:hover {
    background: rgba(255, 255, 255, 0.10);
    border-color: rgba(255, 255, 255, 0.16);
    transform: translateY(-1px);
}

/* Footer Access Note */
.landing-foot-wrap {
    position: relative;
    z-index: 1;
    margin-top: auto;
    padding-top: 36px;
}
.landing-foot {
    margin: 0;
    font-size: 12px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: rgba(148, 163, 184, 0.70);
    text-align: center;
}

/* Responsive */
@media (max-width: 640px) {
    .landing-simple { padding: 32px 16px 24px; }
    .landing-logo { width: 92px; height: 92px; margin-bottom: 20px; }
    .landing-kicker { font-size: 11.5px; padding: 5px 12px; }
    .landing-title { font-size: clamp(30px, 8vw, 40px); margin-bottom: 12px; }
    .landing-desc { font-size: 15.5px; margin-bottom: 28px; }
    .landing-actions { flex-direction: column; width: 100%; }
    .landing-actions a { width: 100% !important; justify-content: center; min-width: unset; }
    .landing-foot-wrap { padding-top: 24px; }
    .landing-foot { font-size: 11px; }
}

/* Light mode */
html:not(.dark) .landing-simple {
    background:
        radial-gradient(ellipse 900px 500px at 50% -8%, rgba(91,141,224,0.08) 0%, transparent 62%),
        linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}
html:not(.dark) .landing-simple::before {
    background: radial-gradient(ellipse 760px 420px at 50% 32%, rgba(91,141,224,0.04), transparent 68%);
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
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
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
        0 12px 32px rgba(15, 23, 42, 0.08),
        0 0 0 1px rgba(15, 23, 42, 0.06) inset;
}
</style>
@endsection
