@extends("components.default")

@section("title", "CICT Equipment Borrower System — College of Information & Communications Technology, UNM")

@section("content")
<div class="landing-simple">
    <main class="landing-simple-inner" id="main-content">
        {{-- Logo — isolated, no card --}}
        <div class="landing-logo" aria-hidden="true">
            <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT logo">
        </div>

        <p class="landing-kicker">College of Information &amp; Communications Technology &middot; UNM</p>

        <h1 class="landing-title">CICT Equipment Borrower System</h1>

        <p class="landing-desc">
            Request, track and return laboratory equipment in one secure workspace for CICT students and instructors.
        </p>

        <div class="landing-actions">
            <a href="{{ route('login') }}" class="btn-primary landing-primary">
                Login to System
                <i class="fa-solid fa-arrow-right text-[11px]" aria-hidden="true"></i>
            </a>
            <a href="{{ route('register') }}" class="landing-secondary">Create account</a>
        </div>
    </main>

    <p class="landing-foot">College of Information &amp; Communications Technology, UNM — CICT Equipment Borrower System</p>
</div>

<style>
.landing-simple{
    min-height:100dvh;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding:48px 20px 32px;
    text-align:center;
    position:relative;
    overflow:hidden;
    background:
        radial-gradient(ellipse 900px 500px at 50% -8%, rgba(91,141,224,0.10) 0%, transparent 62%),
        linear-gradient(180deg, #0a0e1a 0%, #0e1426 100%);
}
.landing-simple::before{
    content:'';
    position:absolute; inset:0;
    background: radial-gradient(ellipse 760px 420px at 50% 28%, rgba(91,141,224,0.06), transparent 68%);
    pointer-events:none;
}
.landing-simple-inner{
    position:relative; z-index:1;
    width:100%; max-width:620px;
    display:flex; flex-direction:column; align-items:center;
}
/* Logo — better: larger, clean ring, soft halo, no card */
.landing-logo{
    width:112px; height:112px;
    border-radius:999px;
    padding:8px;
    background:#fff;
    border:1px solid rgba(255,255,255,0.10);
    box-shadow:
        0 12px 32px rgba(0,0,0,0.45),
        0 0 0 1px rgba(255,255,255,0.06) inset,
        0 0 28px rgba(91,141,224,0.18);
    display:grid; place-items:center;
    margin-bottom:22px;
}
.landing-logo img{
    width:100%; height:100%;
    object-fit:contain;
    border-radius:999px;
}
.landing-kicker{
    margin:0;
    font-size:11px; font-weight:700; letter-spacing:0.12em; text-transform:uppercase;
    color:#94a3b8;
}
.landing-title{
    margin:14px 0 0;
    font-size: clamp(32px, 5vw, 48px);
    font-weight:800; line-height:0.98; letter-spacing:-0.04em;
    color:#fff; text-wrap:balance;
}
.landing-desc{
    margin:16px 0 0;
    font-size:15px; line-height:1.6; color:#94a3b8;
    max-width:48ch; text-wrap:pretty;
}
.landing-actions{
    display:flex; flex-wrap:wrap; gap:12px;
    justify-content:center;
    margin-top:28px;
    width:100%;
}
.landing-primary{
    width:auto !important;
    padding:13px 24px !important;
    border-radius:999px !important;
    font-size:14px !important;
}
.landing-secondary{
    display:inline-flex; align-items:center; justify-content:center;
    padding:13px 24px;
    border-radius:999px;
    font-size:14px; font-weight:600; letter-spacing:-0.01em; text-decoration:none;
    color:#e2e8f0;
    background: rgba(255,255,255,0.06);
    border:1px solid rgba(255,255,255,0.10);
    transition: background 200ms, border-color 200ms, transform 200ms;
}
.landing-secondary:hover{
    background: rgba(255,255,255,0.10);
    border-color: rgba(255,255,255,0.14);
    transform: translateY(-1px);
}
.landing-foot{
    position:relative; z-index:1;
    margin:40px 0 0;
    font-size:11px; letter-spacing:0.06em; text-transform:uppercase;
    color: rgba(148,163,184,0.70);
    text-align:center;
}

@media (max-width: 640px){
    .landing-simple{padding:32px 16px 24px;}
    .landing-logo{width:96px; height:96px; margin-bottom:18px;}
    .landing-title{font-size: clamp(28px, 8vw, 36px);}
    .landing-desc{font-size:14px;}
    .landing-actions{flex-direction:column;}
    .landing-actions a{width:100% !important; justify-content:center;}
}

/* Light mode */
html:not(.dark) .landing-simple{
    background:
        radial-gradient(ellipse 900px 500px at 50% -8%, rgba(91,141,224,0.08) 0%, transparent 62%),
        linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}
html:not(.dark) .landing-simple::before{background: radial-gradient(ellipse 760px 420px at 50% 28%, rgba(91,141,224,0.04), transparent 68%);}
html:not(.dark) .landing-kicker{color:#64748b;}
html:not(.dark) .landing-title{color:#0f172a;}
html:not(.dark) .landing-desc{color:#475569;}
html:not(.dark) .landing-secondary{background:#fff; border-color: rgba(15,23,42,0.10); color:#0f172a; box-shadow: 0 1px 2px rgba(15,23,42,0.06);}
html:not(.dark) .landing-secondary:hover{background:#f8fafc;}
html:not(.dark) .landing-foot{color:#94a3b8;}
html:not(.dark) .landing-logo{background:#fff; border-color: rgba(15,23,42,0.08); box-shadow: 0 10px 28px rgba(15,23,42,0.08), 0 0 0 1px rgba(15,23,42,0.06) inset;}
</style>
@endsection
