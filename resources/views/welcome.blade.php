@extends("components.default")

@section("title", "Welcome - CICT Equipment Borrower System")

@section("content")

<div class="theme-shell relative min-h-screen overflow-hidden bg-[#080c16]">
{{-- Background Effects --}}
<div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-40 -left-40 h-[500px] w-[500px] rounded-full bg-blue-600/10 blur-[120px]"></div>
    <div class="absolute -right-40 top-1/3 h-[500px] w-[500px] rounded-full bg-indigo-600/10 blur-[120px]"></div>
    <div class="absolute bottom-[-200px] left-1/3 h-[400px] w-[400px] rounded-full bg-cyan-500/5 blur-[100px]"></div>

    {{-- Subtle Grid --}}
    <div
        class="absolute inset-0 opacity-[0.025]"
        style="
            background-image:
                linear-gradient(rgba(255,255,255,.8) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.8) 1px, transparent 1px);
            background-size: 45px 45px;
        ">
    </div>
</div>

<div class="relative z-10 flex items-center w-full min-h-screen px-5 py-12 mx-auto max-w-7xl sm:px-8 lg:px-10">

    <div class="grid items-center w-full gap-14 lg:grid-cols-12 lg:gap-16">

        {{-- ========================================================= --}}
        {{-- LEFT / HERO --}}
        {{-- ========================================================= --}}
        <div class="lg:col-span-7 animate-fade-in">

            {{-- Badge --}}
            <div class="mb-6 inline-flex items-center gap-2.5 rounded-full border border-blue-400/10 bg-blue-500/[0.07] px-4 py-2 text-xs font-medium text-blue-200/80 shadow-[0_0_30px_rgba(59,130,246,.08)]">
                <span class="relative flex w-2 h-2">
                    <span class="absolute inline-flex w-full h-full rounded-full animate-ping bg-emerald-400 opacity-60"></span>
                    <span class="relative inline-flex w-2 h-2 rounded-full bg-emerald-400"></span>
                </span>

                CICT Equipment Borrower System

                <i class="fa-solid fa-arrow-up-right-from-square ml-1 text-[9px] text-blue-400/70"></i>
            </div>

            {{-- Heading --}}
            <h1 class="max-w-3xl text-4xl font-black leading-[1.02] tracking-[-0.04em] text-white sm:text-5xl md:text-6xl xl:text-[68px]">
                Equipment access,
                <br>

                <span class="text-transparent bg-gradient-to-r from-blue-300 via-blue-500 to-indigo-500 bg-clip-text">
                    made simple.
                </span>
            </h1>

            {{-- Description --}}
            <p class="max-w-2xl text-base leading-7 mt-7 text-slate-400 sm:text-lg">
                Request, track, and manage CICT equipment from one centralized workspace.
                Built to make borrowing
                <span class="font-medium text-slate-300">faster, easier, and more organized.</span>
            </p>

            {{-- Buttons --}}
            <div class="flex flex-col gap-3 mt-9 sm:flex-row">

                <a
                    href="{{ route('login') }}"
                    class="group inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-7 py-3.5 text-sm font-bold text-white shadow-[0_10px_35px_rgba(37,99,235,.25)] transition-all duration-300 hover:-translate-y-0.5 hover:from-blue-500 hover:to-indigo-500 hover:shadow-[0_15px_45px_rgba(37,99,235,.35)]">
                    Login to System

                    <i class="text-xs transition-transform duration-300 fa-solid fa-arrow-right group-hover:translate-x-1"></i>
                </a>

                <a
                    href="{{ route('register') }}"
                    class="group inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/[0.04] px-7 py-3.5 text-sm font-semibold text-slate-200 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-white/20 hover:bg-white/[0.08]">
                    <i class="text-xs text-blue-400 transition-transform fa-solid fa-user-plus group-hover:scale-110"></i>
                    Create an account
                </a>

            </div>

            {{-- Trust Indicators --}}
            <div class="flex flex-wrap items-center text-xs mt-9 gap-x-7 gap-y-3 text-slate-500">

                <div class="flex items-center gap-2">
                    <span class="flex items-center justify-center w-6 h-6 rounded-lg bg-emerald-400/10 text-emerald-400">
                        <i class="fa-solid fa-shield-halved text-[10px]"></i>
                    </span>
                    Secure access
                </div>

                <div class="hidden w-px h-4 bg-white/10 sm:block"></div>

                <div class="flex items-center gap-2">
                    <span class="flex items-center justify-center w-6 h-6 text-blue-400 rounded-lg bg-blue-400/10">
                        <i class="fa-solid fa-clock text-[10px]"></i>
                    </span>
                    Easy requests
                </div>

                <div class="hidden w-px h-4 bg-white/10 sm:block"></div>

                <div class="flex items-center gap-2">
                    <span class="flex items-center justify-center w-6 h-6 rounded-lg bg-violet-400/10 text-violet-400">
                        <i class="fa-solid fa-chart-line text-[10px]"></i>
                    </span>
                    Organized tracking
                </div>

            </div>
        </div>


        {{-- ========================================================= --}}
        {{-- RIGHT / SYSTEM CARD --}}
        {{-- ========================================================= --}}
        <div
            class="flex justify-center lg:col-span-5 lg:justify-end animate-fade-in"
            style="animation-delay:.15s">

            <div class="relative w-full max-w-[430px]">

                {{-- Decorative Glow --}}
                <div class="absolute -inset-3 rounded-[2rem] bg-blue-500/10 blur-2xl"></div>

                {{-- Main Card --}}
                <div class="relative overflow-hidden rounded-[1.75rem] border border-white/10 bg-[#0d1321]/90 p-6 shadow-[0_25px_80px_rgba(0,0,0,.45)] backdrop-blur-xl sm:p-7">

                    {{-- Top Gradient --}}
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-400/60 to-transparent"></div>

                    {{-- Card Header --}}
                    <div class="flex items-center justify-between">

                        <div class="flex items-center gap-3">

                            <div class="flex items-center justify-center border h-11 w-11 rounded-xl border-blue-400/10 bg-blue-500/10">
                                <i class="text-lg text-blue-400 fa-solid fa-laptop"></i>
                            </div>

                            <div>
                                <p class="text-sm font-bold text-white">
                                    CICT Equipment Center
                                </p>

                                <p class="mt-0.5 text-[11px] text-slate-500">
                                    Equipment Management Portal
                                </p>
                            </div>

                        </div>

                        <span class="flex items-center gap-1.5 rounded-full border border-emerald-400/10 bg-emerald-400/5 px-2.5 py-1 text-[10px] font-medium text-emerald-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            Online
                        </span>

                    </div>


                    {{-- Logo Area --}}
                    <div class="relative mt-7 overflow-hidden rounded-2xl border border-white/[0.06] bg-gradient-to-br from-[#111a2c] to-[#0a0f1c] px-6 py-8">

                        <div class="absolute w-32 h-32 rounded-full -right-10 -top-10 bg-blue-500/10 blur-3xl"></div>

                        <div class="relative flex flex-col items-center text-center">

                            <div class="mb-5 flex h-24 w-24 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04] p-3 shadow-[0_10px_40px_rgba(0,0,0,.25)]">
                                <img
                                    src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png"
                                    alt="CICT Logo"
                                    class="object-contain w-full h-full">
                            </div>

                            <h2 class="text-lg font-bold tracking-tight text-white">
                                College of Information &amp;
                                <br>
                                Communications Technology
                            </h2>

                            <p class="mt-2 text-xs text-slate-500">
                                University of Northern Mindanao
                            </p>

                        </div>

                    </div>


                    {{-- Stats --}}
                    <div class="mt-4 grid grid-cols-3 gap-2.5">

                        <div class="group rounded-xl border border-white/[0.06] bg-[#090e19] px-3 py-4 text-center transition-colors hover:border-blue-400/20 hover:bg-blue-500/[0.03]">
                            <div class="text-lg font-extrabold tracking-tight text-white">
                                500<span class="text-blue-400">+</span>
                            </div>

                            <div class="mt-1 text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-600">
                                Equipment
                            </div>
                        </div>

                        <div class="group rounded-xl border border-white/[0.06] bg-[#090e19] px-3 py-4 text-center transition-colors hover:border-blue-400/20 hover:bg-blue-500/[0.03]">
                            <div class="text-lg font-extrabold tracking-tight text-white">
                                24<span class="text-blue-400">/7</span>
                            </div>

                            <div class="mt-1 text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-600">
                                Access
                            </div>
                        </div>

                        <div class="group rounded-xl border border-white/[0.06] bg-[#090e19] px-3 py-4 text-center transition-colors hover:border-emerald-400/20 hover:bg-emerald-500/[0.03]">
                            <div class="flex items-center justify-center h-7">
                                <i class="text-lg fa-solid fa-circle-check text-emerald-400"></i>
                            </div>

                            <div class="mt-1 text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-600">
                                Trusted
                            </div>
                        </div>

                    </div>


                    {{-- Bottom Status --}}
                    <div class="mt-5 flex items-center justify-between border-t border-white/[0.06] pt-5">

                        <div class="flex items-center gap-2.5">

                            <div class="flex items-center justify-center w-8 h-8 text-blue-400 rounded-lg bg-blue-500/10">
                                <i class="text-xs fa-solid fa-bolt"></i>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold text-slate-300">
                                    Quick &amp; convenient
                                </p>

                                <p class="text-[10px] text-slate-600">
                                    Manage your requests online
                                </p>
                            </div>

                        </div>

                        <i class="text-xs fa-solid fa-arrow-up-right-from-square text-slate-600"></i>

                    </div>

                </div>

                {{-- Floating Decoration --}}
                <div class="absolute -bottom-5 -left-5 hidden rounded-2xl border border-white/10 bg-[#111827]/90 px-4 py-3 shadow-xl backdrop-blur-md sm:block">
                    <div class="flex items-center gap-3">

                        <div class="flex items-center justify-center h-9 w-9 rounded-xl bg-emerald-400/10 text-emerald-400">
                            <i class="text-xs fa-solid fa-check"></i>
                        </div>

                        <div>
                            <p class="text-[10px] font-bold text-white">
                                System ready
                            </p>

                            <p class="text-[9px] text-slate-500">
                                Start borrowing today
                            </p>
                        </div>

                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

</div> @endsection
