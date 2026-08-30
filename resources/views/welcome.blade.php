@extends("components.default")

@section("title", "Welcome - CICT Equipment Borrower System")

@section("content")

<div class="relative min-h-screen overflow-hidden theme-shell">
{{-- Ambient Background --}}
<div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -left-40 -top-40 h-[420px] w-[420px] rounded-full bg-blue-600/[0.08] blur-3xl"></div>
    <div class="absolute -bottom-40 -right-40 h-[500px] w-[500px] rounded-full bg-indigo-600/[0.07] blur-3xl"></div>

    <div class="absolute left-1/2 top-0 h-px w-[70%] -translate-x-1/2 bg-gradient-to-r from-transparent via-blue-500/30 to-transparent"></div>

    {{-- Subtle Grid --}}
    <div
        class="absolute inset-0 opacity-[0.025]"
        style="
            background-image:
                linear-gradient(rgba(255,255,255,.5) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.5) 1px, transparent 1px);
            background-size: 44px 44px;
        "
    ></div>
</div>

<div class="relative flex items-center w-full min-h-screen px-5 py-12 mx-auto max-w-7xl sm:px-8 lg:px-10">

    <div class="grid items-center w-full gap-14 lg:grid-cols-12 lg:gap-10">

        {{-- =====================================================
            LEFT CONTENT
        ====================================================== --}}
        <div class="lg:col-span-7 animate-fade-in">

            {{-- Status Badge --}}
            <div class="mb-7 inline-flex items-center gap-3 rounded-full border border-white/[0.08] bg-white/[0.035] px-4 py-2 shadow-[0_8px_30px_rgba(0,0,0,.15)] backdrop-blur-md">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="absolute inline-flex w-full h-full rounded-full opacity-50 animate-ping bg-emerald-400"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-[0_0_12px_rgba(16,185,129,.8)]"></span>
                </span>

                <span class="text-xs font-semibold tracking-wide text-[#a5aec3]">
                    CICT Equipment Borrower System
                </span>

                <span class="w-px h-3 bg-white/10"></span>

                <span class="text-[10px] font-medium uppercase tracking-wider text-emerald-400/80">
                    Online
                </span>
            </div>

            {{-- Heading --}}
            <h1 class="max-w-3xl text-4xl font-extrabold leading-[1.02] tracking-[-0.035em] text-white sm:text-5xl md:text-[52px] xl:text-[62px]">
                Borrow equipment,
                <br>
                <span class="relative inline-block bg-gradient-to-r from-[#93c5fd] via-[#60a5fa] to-[#3b82f6] bg-clip-text text-transparent">
                    faster
                </span>
                <span class="text-white">&amp; smarter.</span>
            </h1>

            {{-- Description --}}
            <p class="mt-7 max-w-xl text-[15px] leading-7 text-[#8b93a8] sm:text-base">
                Manage, monitor, and request CICT equipment from one unified workspace.
                Designed to make borrowing simpler, faster, and more organized for
                students and instructors.
            </p>

            {{-- Actions --}}
            <div class="flex flex-col gap-3 mt-8 sm:flex-row">

                <a
                    href="{{ route('login') }}"
                    class="group btn-primary !w-auto rounded-xl px-7 py-3.5 shadow-[0_10px_30px_rgba(59,130,246,.18)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_35px_rgba(59,130,246,.28)]"
                >
                    <span>Login to System</span>
                    <i class="ml-2 text-xs transition-transform duration-300 group-hover:translate-x-1 fa-solid fa-arrow-right"></i>
                </a>

                <a
                    href="{{ route('register') }}"
                    class="group inline-flex items-center justify-center rounded-xl border border-white/[0.09] bg-white/[0.035] px-7 py-3.5 text-sm font-semibold text-slate-200 shadow-sm backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-white/[0.16] hover:bg-white/[0.07]"
                >
                    <i class="mr-2 text-xs transition-colors text-slate-400 group-hover:text-blue-400 fa-solid fa-user-plus"></i>
                    Create account
                </a>
            </div>

            {{-- Feature Highlights --}}
            <div class="mt-10 flex flex-wrap gap-x-7 gap-y-3 border-t border-white/[0.06] pt-6">

                <div class="flex items-center gap-2 text-xs text-[#737e96]">
                    <span class="flex items-center justify-center w-6 h-6 rounded-lg bg-emerald-400/10 text-emerald-400">
                        <i class="text-[10px] fa-solid fa-shield-halved"></i>
                    </span>
                    Secure access
                </div>

                <div class="flex items-center gap-2 text-xs text-[#737e96]">
                    <span class="flex items-center justify-center w-6 h-6 text-blue-400 rounded-lg bg-blue-400/10">
                        <i class="text-[10px] fa-solid fa-bolt"></i>
                    </span>
                    Fast requests
                </div>

                <div class="flex items-center gap-2 text-xs text-[#737e96]">
                    <span class="flex items-center justify-center w-6 h-6 rounded-lg bg-violet-400/10 text-violet-400">
                        <i class="text-[10px] fa-solid fa-box"></i>
                    </span>
                    Equipment tracking
                </div>

            </div>
        </div>


        {{-- =====================================================
            RIGHT CARD
        ====================================================== --}}
        <div
            class="relative flex justify-center lg:col-span-5 lg:justify-end animate-fade-in"
            style="animation-delay:.15s"
        >

            {{-- Decorative Glow --}}
            <div class="absolute -inset-8 rounded-[40px] bg-blue-500/[0.06] blur-3xl"></div>

            <div class="relative w-full max-w-[430px]">

                {{-- Floating Decorative Element --}}
                <div class="absolute -right-3 -top-3 z-10 flex h-11 w-11 items-center justify-center rounded-xl border border-white/10 bg-[#111827]/90 text-blue-400 shadow-xl backdrop-blur-xl">
                    <i class="text-sm fa-solid fa-cube"></i>
                </div>

                {{-- Main Card --}}
                <div class="auth-card !max-w-none !rounded-3xl !p-7 sm:!p-8">

                    {{-- Card Header --}}
                    <div class="flex items-center justify-between border-b border-white/[0.06] pb-5">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center text-blue-400 rounded-lg h-9 w-9 bg-blue-500/10">
                                <i class="text-sm fa-solid fa-layer-group"></i>
                            </div>

                            <div class="text-left">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#647089]">
                                    Equipment Center
                                </div>
                                <div class="mt-0.5 text-xs text-[#8b93a8]">
                                    CICT • UNM
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 rounded-full border border-emerald-400/10 bg-emerald-400/[0.06] px-2.5 py-1">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            <span class="text-[9px] font-semibold uppercase tracking-wider text-emerald-400">
                                Active
                            </span>
                        </div>
                    </div>


                    {{-- Logo --}}
                    <div class="flex flex-col items-center text-center pt-7">

                        <div class="relative">
                            <div class="absolute inset-0 rounded-[26px] bg-blue-500/10 blur-xl"></div>

                            <div class="auth-logo relative mx-auto !mb-0 !h-24 !w-24 rounded-[26px] border border-white/[0.08] bg-[#0c1220] p-3 shadow-[0_15px_40px_rgba(0,0,0,.25)]">
                                <img
                                    src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png"
                                    alt="CICT"
                                    class="object-contain w-full h-full"
                                >
                            </div>
                        </div>

                        <h3 class="mt-6 text-xl font-bold tracking-tight text-white">
                            CICT Equipment Center
                        </h3>

                        <p class="mt-2 max-w-xs text-xs leading-5 text-[#7f899f]">
                            College of Information &amp; Communications Technology
                            <br>
                            University of Northern Mindanao
                        </p>
                    </div>


                    {{-- Stats --}}
                    <div class="mt-7 grid grid-cols-3 gap-2.5">

                        <div class="group rounded-2xl border border-white/[0.06] bg-[#0b101c]/80 px-2 py-4 text-center transition-colors hover:border-blue-400/20 hover:bg-blue-400/[0.035]">
                            <div class="text-lg font-bold tracking-tight text-white">
                                500<span class="text-blue-400">+</span>
                            </div>
                            <div class="mt-1 text-[9px] font-semibold uppercase tracking-[0.12em] text-[#68738a]">
                                Items
                            </div>
                        </div>

                        <div class="group rounded-2xl border border-white/[0.06] bg-[#0b101c]/80 px-2 py-4 text-center transition-colors hover:border-blue-400/20 hover:bg-blue-400/[0.035]">
                            <div class="text-lg font-bold tracking-tight text-white">
                                24<span class="text-blue-400">/7</span>
                            </div>
                            <div class="mt-1 text-[9px] font-semibold uppercase tracking-[0.12em] text-[#68738a]">
                                Access
                            </div>
                        </div>

                        <div class="group rounded-2xl border border-white/[0.06] bg-[#0b101c]/80 px-2 py-4 text-center transition-colors hover:border-emerald-400/20 hover:bg-emerald-400/[0.035]">
                            <div class="flex h-[27px] items-center justify-center text-lg font-bold text-emerald-400">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div class="mt-1 text-[9px] font-semibold uppercase tracking-[0.12em] text-[#68738a]">
                                Trusted
                            </div>
                        </div>

                    </div>


                    {{-- Bottom Info --}}
                    <div class="mt-5 flex items-center gap-3 rounded-xl border border-white/[0.05] bg-white/[0.02] px-4 py-3">
                        <div class="flex items-center justify-center w-8 h-8 text-blue-400 rounded-lg shrink-0 bg-blue-500/10">
                            <i class="text-xs fa-solid fa-arrow-up-right-dots"></i>
                        </div>

                        <div class="text-left">
                            <div class="text-[11px] font-semibold text-[#b2bacb]">
                                Simplified borrowing
                            </div>
                            <div class="mt-0.5 text-[10px] text-[#68738a]">
                                Request • Track • Return
                            </div>
                        </div>

                        <i class="ml-auto text-[10px] text-[#46516a] fa-solid fa-chevron-right"></i>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

</div> @endsection
