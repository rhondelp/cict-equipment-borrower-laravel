@extends("components.default")

@section("title", "CICT Equipment Borrower System — College of Information & Communications Technology, UNM")

@section("content")
<div class="landing-root">
    {{-- Top nav --}}
    <header class="landing-nav">
        <div class="landing-container">
            <a href="{{ url('/') }}" class="landing-brand" aria-label="CICT Equipment Borrower System home">
                <span class="landing-brand-mark">
                    <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT logo">
                </span>
                <span class="landing-brand-text">
                    <span class="landing-brand-title">CICT</span>
                    <span class="landing-brand-sub">College of Information &amp; Communications Technology — UNM</span>
                </span>
            </a>
            <nav class="landing-nav-actions" aria-label="Primary">
                <a href="{{ route('login') }}" class="landing-nav-link">Login</a>
                <a href="{{ route('register') }}" class="landing-nav-cta">Create account</a>
            </nav>
        </div>
    </header>

    {{-- Hero --}}
    <main class="landing-hero" id="main-content">
        <div class="landing-container landing-hero-grid">
            {{-- Left: identity --}}
            <div class="landing-copy">
                <p class="landing-eyebrow">
                    <span class="landing-eyebrow-dot" aria-hidden="true"></span>
                    College of Information &amp; Communications Technology &middot; UNM
                </p>

                <h1 class="landing-title">
                    CICT Equipment<br>
                    Borrower System
                </h1>

                <p class="landing-desc">
                    Request, track and return laboratory equipment in one secure workspace for CICT students and instructors.
                </p>

                <div class="landing-actions">
                    <a href="{{ route('login') }}" class="btn-primary landing-btn-primary">
                        Login to System
                        <i class="fa-solid fa-arrow-right text-[11px]" aria-hidden="true"></i>
                    </a>
                    <a href="{{ route('register') }}" class="landing-btn-secondary">
                        Create account
                    </a>
                </div>

                <p class="landing-meta">
                    For enrolled CICT students and faculty. Authorized access only.
                </p>
            </div>

            {{-- Right: system mark --}}
            <div class="landing-visual" aria-hidden="true">
                <div class="landing-card">
                    <div class="landing-card-glow"></div>
                    <div class="landing-card-inner">
                        <div class="auth-logo landing-card-logo">
                            <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="">
                        </div>
                        <h2 class="landing-card-title">CICT Equipment Borrower System</h2>
                        <p class="landing-card-college">College of Information &amp;<br>Communications Technology</p>
                        <p class="landing-card-unm">UNM</p>
                        <div class="landing-card-rule"></div>
                        <p class="landing-card-foot">Equipment borrowing &amp; returns</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="landing-footer">
        <div class="landing-container landing-footer-inner">
            <span>CICT &mdash; College of Information &amp; Communications Technology, UNM</span>
            <span class="landing-footer-dot">&middot;</span>
            <span>CICT Equipment Borrower System</span>
        </div>
    </footer>
</div>

<style>
.landing-root{
    min-height:100dvh;
    display:flex;
    flex-direction:column;
    background:
        radial-gradient(ellipse 900px 520px at 70% 14%, rgba(91,141,224,0.12) 0%, rgba(91,141,224,0.05) 28%, transparent 62%),
        radial-gradient(ellipse 700px 440px at -4% 88%, rgba(91,141,224,0.06) 0%, transparent 65%),
        linear-gradient(180deg, #0a0e1a 0%, #0e1426 100%);
    position:relative;
    overflow:hidden;
}
.landing-root::before{
    content:'';
    position:absolute; inset:0;
    background: radial-gradient(ellipse 1200px 420px at 50% -10%, rgba(96,165,250,0.07), transparent 70%);
    pointer-events:none;
}
.landing-container{
    width:100%;
    max-width:1120px;
    margin-inline:auto;
    padding-inline:24px;
    position:relative;
    z-index:1;
}
/* Nav */
.landing-nav{
    border-bottom:1px solid rgba(255,255,255,0.06);
    backdrop-filter: blur(10px);
    background: rgba(10,14,26,0.55);
    position:sticky; top:0; z-index:20;
}
.landing-nav .landing-container{
    display:flex; align-items:center; justify-content:space-between;
    gap:24px; min-height:64px; padding-block:12px;
}
.landing-brand{display:flex; align-items:center; gap:12px; text-decoration:none; min-width:0;}
.landing-brand-mark{
    width:40px; height:40px; border-radius:999px; overflow:hidden; flex-shrink:0;
    background: linear-gradient(135deg,#1e293b 0%,#0f172a 100%);
    border:1px solid rgba(255,255,255,0.08);
    display:grid; place-items:center;
    box-shadow: 0 4px 16px rgba(0,0,0,0.28);
}
.landing-brand-mark img{width:100%; height:100%; object-fit:cover;}
.landing-brand-text{display:flex; flex-direction:column; line-height:1; min-width:0;}
.landing-brand-title{font-size:15px; font-weight:800; letter-spacing:-0.03em; color:#fff; line-height:1;}
.landing-brand-sub{font-size:11px; font-weight:500; letter-spacing:0.02em; color:#94a3b8; margin-top:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;}
.landing-nav-actions{display:flex; align-items:center; gap:10px; flex-shrink:0;}
.landing-nav-link{
    font-size:13.5px; font-weight:600; color:#cbd5e1; text-decoration:none;
    padding:8px 14px; border-radius:8px; border:1px solid transparent;
}
.landing-nav-link:hover{color:#fff; background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.06);}
.landing-nav-cta{
    font-size:13.5px; font-weight:700; letter-spacing:-0.01em; color:#fff;
    background: var(--accent, #5b8de0);
    border:1px solid rgba(255,255,255,0.08);
    padding:10px 18px; border-radius:999px; text-decoration:none;
    box-shadow: 0 8px 20px rgba(10,14,26,0.45), 0 1px 0 rgba(255,255,255,0.06) inset;
    transition: background 200ms, transform 200ms;
}
.landing-nav-cta:hover{background:#4a6fa5; transform: translateY(-1px);}
/* Hero */
.landing-hero{
    flex:1; display:flex; align-items:center;
    padding:40px 0 32px;
}
.landing-hero-grid{
    display:grid; grid-template-columns:1.05fr 0.95fr; gap:40px; align-items:center;
}
.landing-copy{display:flex; flex-direction:column; gap:0; max-width:560px;}
.landing-eyebrow{
    display:inline-flex; align-items:center; gap:8px;
    font-size:11px; font-weight:600; letter-spacing:0.10em; text-transform:uppercase;
    color:#94a3b8; margin:0 0 18px;
}
.landing-eyebrow-dot{
    width:6px; height:6px; border-radius:999px; background:#22c55e;
    box-shadow: 0 0 10px rgba(34,197,94,0.55);
    flex-shrink:0;
}
.landing-title{
    font-size: clamp(36px, 5.2vw, 54px);
    font-weight:800; line-height:0.98; letter-spacing:-0.04em;
    color:#fff; margin:0; text-wrap:balance;
}
.landing-desc{
    margin:16px 0 0;
    font-size:15px; line-height:1.65; color:#94a3b8; font-weight:400;
    max-width:46ch; text-wrap:pretty;
}
.landing-actions{
    display:flex; flex-wrap:wrap; gap:12px; margin-top:28px;
}
.landing-btn-primary{width:auto !important; padding:13px 22px !important; border-radius:12px !important; font-size:14px !important;}
.landing-btn-secondary{
    display:inline-flex; align-items:center; justify-content:center;
    padding:13px 22px; border-radius:12px;
    background: rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.10);
    color:#e2e8f0; font-size:14px; font-weight:600; letter-spacing:-0.01em; text-decoration:none;
    transition: background 200ms, border-color 200ms, transform 200ms;
}
.landing-btn-secondary:hover{background: rgba(255,255,255,0.10); border-color: rgba(255,255,255,0.14); transform: translateY(-1px);}
.landing-meta{
    margin:14px 0 0; font-size:12px; color:#64748b; line-height:1.5;
}
/* Visual card */
.landing-visual{display:flex; justify-content:flex-end; align-items:center;}
.landing-card{
    width:100%; max-width:420px; position:relative;
    background: #131a2b;
    border:1px solid rgba(255,255,255,0.07);
    border-radius:20px;
    box-shadow: 0 24px 64px rgba(0,0,0,0.48), 0 0 0 1px rgba(255,255,255,0.03) inset;
    overflow:hidden;
    padding:32px 28px 24px;
    text-align:center;
}
.landing-card-glow{
    position:absolute; inset:-1px; border-radius:20px;
    background: radial-gradient(ellipse 420px 200px at 50% 0%, rgba(91,141,224,0.18), transparent 62%);
    pointer-events:none;
}
.landing-card-inner{position:relative; z-index:1;}
.landing-card-logo{margin:0 auto 16px !important; width:72px !important; height:72px !important;}
.landing-card-title{
    font-size:17px; font-weight:800; letter-spacing:-0.03em; color:#fff; line-height:1.2; margin:0;
    text-wrap:balance;
}
.landing-card-college{
    margin:8px 0 0; font-size:13px; line-height:1.5; color:#94a3b8; font-weight:500;
}
.landing-card-unm{
    margin:6px 0 0; font-size:12px; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; color:#cbd5e1;
}
.landing-card-rule{
    height:1px; background: rgba(255,255,255,0.07); margin:18px auto 14px; max-width:160px;
}
.landing-card-foot{
    margin:0; font-size:11px; letter-spacing:0.08em; text-transform:uppercase; color:#64748b; font-weight:600;
}
/* Footer */
.landing-footer{
    border-top:1px solid rgba(255,255,255,0.06);
    padding:14px 0 18px;
    background: rgba(10,14,26,0.35);
}
.landing-footer-inner{
    display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:8px;
    font-size:11.5px; color:#64748b; text-align:center; letter-spacing:0.02em;
}
.landing-footer-dot{opacity:0.6;}

@media (max-width: 900px){
    .landing-hero-grid{grid-template-columns:1fr; gap:32px;}
    .landing-visual{justify-content:center;}
    .landing-card{max-width:480px;}
    .landing-hero{padding-top:28px;}
}
@media (max-width: 640px){
    .landing-nav .landing-container{min-height:58px;}
    .landing-brand-sub{display:none;}
    .landing-nav-link{padding:8px 10px;}
    .landing-nav-cta{padding:9px 14px; font-size:13px;}
    .landing-container{padding-inline:16px;}
    .landing-title{font-size: clamp(32px, 9vw, 40px);}
    .landing-desc{font-size:14px;}
    .landing-actions{flex-direction:column;}
    .landing-actions a{width:100% !important; justify-content:center;}
}
/* Light mode */
html:not(.dark) .landing-root{
    background:
        radial-gradient(ellipse 900px 520px at 70% 14%, rgba(91,141,224,0.10) 0%, rgba(91,141,224,0.04) 28%, transparent 62%),
        linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}
html:not(.dark) .landing-root::before{background: radial-gradient(ellipse 1200px 420px at 50% -10%, rgba(91,141,224,0.06), transparent 70%);}
html:not(.dark) .landing-nav{background: rgba(255,255,255,0.86); border-color: rgba(15,23,42,0.06);}
html:not(.dark) .landing-brand-title{color:#0f172a;}
html:not(.dark) .landing-brand-sub{color:#64748b;}
html:not(.dark) .landing-brand-mark{border-color: rgba(15,23,42,0.08);}
html:not(.dark) .landing-nav-link{color:#475569;}
html:not(.dark) .landing-nav-link:hover{color:#0f172a; background: rgba(15,23,42,0.06);}
html:not(.dark) .landing-title{color:#0f172a;}
html:not(.dark) .landing-desc{color:#475569;}
html:not(.dark) .landing-eyebrow{color:#64748b;}
html:not(.dark) .landing-meta{color:#94a3b8;}
html:not(.dark) .landing-btn-secondary{background:#fff; border-color: rgba(15,23,42,0.10); color:#0f172a; box-shadow: 0 1px 2px rgba(15,23,42,0.06);}
html:not(.dark) .landing-btn-secondary:hover{background:#f8fafc;}
html:not(.dark) .landing-card{background:#fff; border-color: rgba(15,23,42,0.08); box-shadow: 0 8px 32px rgba(15,23,42,0.08), 0 1px 3px rgba(15,23,42,0.06);}
html:not(.dark) .landing-card-glow{background: radial-gradient(ellipse 420px 200px at 50% 0%, rgba(91,141,224,0.10), transparent 62%);}
html:not(.dark) .landing-card-title{color:#0f172a;}
html:not(.dark) .landing-card-college{color:#64748b;}
html:not(.dark) .landing-card-unm{color:#334155;}
html:not(.dark) .landing-card-rule{background: rgba(15,23,42,0.08);}
html:not(.dark) .landing-footer{background: rgba(255,255,255,0.7); border-color: rgba(15,23,42,0.06);}
</style>
@endsection
