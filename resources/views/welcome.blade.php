@extends("components.default")

@section("title", "Welcome - CICT Equipment Borrower System")

@section("content")
<div class="theme-shell">
    <div class="relative w-full max-w-6xl">
        <div class="grid items-center gap-10 px-6 lg:px-8 lg:grid-cols-12">

            <!-- Left -->
            <div class="space-y-6 lg:col-span-7 animate-fade-in">
                <div class="inline-flex items-center gap-3 px-3 py-1.5 rounded-full bg-[#131a2b] border border-white/5 text-xs font-medium text-[#8b93a8]">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(16,185,129,.6)]"></span>
                    CICT Equipment Borrower System
                </div>
                <h1 class="text-4xl md:text-5xl xl:text-[54px] font-extrabold leading-[1.05] tracking-[-0.03em] text-white">
                    Borrow equipment,<br>
                    <span class="bg-gradient-to-r from-[#60a5fa] to-[#3b82f6] bg-clip-text text-transparent">faster</span>
                    &amp; smarter.
                </h1>
                <p class="max-w-xl text-[15px] leading-6 text-[#8b93a8]">
                    Manage, monitor and request CICT equipment from one unified dark workspace. Secure, fast and built for students &amp; instructors.
                </p>
                <div class="flex flex-col gap-3 pt-2 sm:flex-row">
                    <a href="{{ route('login') }}" class="btn-primary !w-auto px-7 py-3.5 rounded-xl">
                        Login to System <i class="ml-1 text-xs fa-solid fa-arrow-right"></i>
                    </a>
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-7 py-3.5 rounded-xl border border-white/10 bg-white/[0.06] text-sm font-semibold text-slate-200 hover:bg-white/[0.10] transition">
                        Create account
                    </a>
                </div>
                {{-- <div class="flex items-center gap-6 pt-4 text-xs text-[#6b7a99]">
                    <span class="flex items-center gap-2"><i class="fa-solid fa-shield-halved"></i> Secure access</span>
                    <span class="flex items-center gap-2"><i class="fa-solid fa-bolt"></i> Real-time tracking</span>
                </div> --}}
            </div>

            <!-- Right: auth-card style preview / logo card -->
            <div class="flex justify-center lg:col-span-5 lg:justify-end animate-fade-in" style="animation-delay:.15s">
                <div class="auth-card !max-w-[420px] !p-8 text-center">
                    <div class="auth-logo mx-auto !w-20 !h-20 mb-5">
                        <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT">
                    </div>
                    <h3 class="text-lg font-bold tracking-tight text-white">CICT Equipment Center</h3>
                    <p class="text-sm text-[#8b93a8] mt-1.5 leading-relaxed">College of Information &amp; Communications Technology<br>UNM</p>
                    <div class="grid grid-cols-3 gap-3 mt-6 text-center">
                        <div class="rounded-xl bg-[#0d1220] border border-white/5 py-3">
                            <div class="text-lg font-bold text-white">500+</div>
                            <div class="text-[11px] tracking-wide uppercase text-[#8b93a8]">Items</div>
                        </div>
                        <div class="rounded-xl bg-[#0d1220] border border-white/5 py-3">
                            <div class="text-lg font-bold text-white">24/7</div>
                            <div class="text-[11px] tracking-wide uppercase text-[#8b93a8]">Access</div>
                        </div>
                        <div class="rounded-xl bg-[#0d1220] border border-white/5 py-3">
                            <div class="text-lg font-bold text-white"><i class="fa-solid fa-check text-emerald-400"></i></div>
                            <div class="text-[11px] tracking-wide uppercase text-[#8b93a8]">Trusted</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
