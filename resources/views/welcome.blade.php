@extends("components.default")

@section("title", "Welcome - CICT Equipment Borrower System")

@section("content")

<div class="relative min-h-screen overflow-hidden theme-shell">
<!-- Background Decorations -->
<div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="absolute rounded-full -left-32 -top-32 h-96 w-96 bg-blue-600/10 blur-3xl"></div>
    <div class="absolute -right-32 top-1/4 h-[32rem] w-[32rem] rounded-full bg-indigo-600/10 blur-3xl"></div>
    <div class="absolute bottom-0 rounded-full left-1/3 h-72 w-72 bg-cyan-500/5 blur-3xl"></div>

    <!-- Grid -->
    <div
        class="absolute inset-0 opacity-[0.025]"
        style="background-image: linear-gradient(rgba(255,255,255,.8) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.8) 1px, transparent 1px); background-size: 48px 48px;">
    </div>
</div>

<div class="relative flex items-center w-full min-h-screen px-5 py-16 mx-auto max-w-7xl sm:px-8 lg:px-10">

    <div class="grid items-center w-full gap-14 lg:grid-cols-12 lg:gap-16">

        <!-- =========================
             LEFT / HERO CONTENT
        ========================== -->
        <div class="lg:col-span-7 animate-fade-in">

            <!-- Badge -->
            <div class="mb-7 inline-flex items-center gap-2.5 rounded-full border border-blue-400/10 bg-blue-500/[0.07] px-4 py-2 text-xs font-semibold tracking-wide text-blue-300 shadow-[0_0_30px_rgba(59,130,246,.06)]">
                <span class="relative flex w-2 h-2">
                    <span class="absolute inline-flex w-full h-full rounded-full animate-ping bg-emerald-400 opacity-60"></span>
                    <span class="relative inline-flex w-2 h-2 rounded-full bg-emerald-400"></span>
                </span>

                CICT EQUIPMENT BORROWER SYSTEM
            </div>

            <!-- Heading -->
            <h1 class="max-w-4xl text-5xl font-black leading-[0.98] tracking-[-0.045em] text-white sm:text-6xl lg:text-[64px] xl:text-[72px]">
                Your equipment.
                <br>

                <span class="text-transparent bg-gradient-to-r from-blue-400 via-sky-400 to-cyan-300 bg-clip-text">
                    Your workspace.
                </span>
            </h1>

            <!-- Description -->
            <p class="max-w-2xl text-base leading-7 mt-7 text-slate-400 sm:text-lg">
                Request, track, and manage CICT equipment from one simple
                digital workspace. Built to make borrowing faster,
                more organized, and easier for students and instructors.
            </p>

            <!-- CTA -->
            <div class="flex flex-col gap-3 mt-9 sm:flex-row">

                <a
                    href="{{ route('login') }}"
                    class="group inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-7 py-4 text-sm font-bold text-white shadow-[0_10px_35px_rgba(37,99,235,.25)] transition-all duration-300 hover:-translate-y-0.5 hover:from-blue-500 hover:to-sky-500 hover:shadow-[0_15px_40px_rgba(37,99,235,.35)]"
                >
                    Login to System
                    <i class="text-xs transition-transform duration-300 fa-solid fa-arrow-right group-hover:translate-x-1"></i>
                </a>

                <a
                    href="{{ route('register') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/[0.04] px-7 py-4 text-sm font-semibold text-slate-200 backdrop-blur-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-white/20 hover:bg-white/[0.08]"
                >
                    <i class="text-xs text-blue-400 fa-solid fa-user-plus"></i>
                    Create account
                </a>

            </div>

            <!-- Trust / Features -->
            <div class="mt-10 flex flex-wrap gap-x-7 gap-y-3 border-t border-white/[0.06] pt-6 text-xs text-slate-500">

                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-emerald-400"></i>
                    Secure access
                </div>

                <div class="flex items-center gap-2">
                    <i class="text-blue-400 fa-solid fa-bolt"></i>
                    Fast requests
                </div>

                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-violet-400"></i>
                    Real-time tracking
                </div>

            </div>

        </div>


        <!-- =========================
             RIGHT / SYSTEM PREVIEW
        ========================== -->
        <div
            class="relative flex justify-center lg:col-span-5 lg:justify-end animate-fade-in"
            style="animation-delay:.15s"
        >

            <!-- Glow -->
            <div class="absolute rounded-full inset-10 bg-blue-500/10 blur-3xl"></div>

            <div class="relative w-full max-w-[430px]">

                <!-- Main Card -->
                <div class="overflow-hidden rounded-3xl border border-white/[0.09] bg-[#0d1424]/90 shadow-[0_30px_100px_rgba(0,0,0,.45)] backdrop-blur-xl">

                    <!-- Card Header -->
                    <div class="flex items-center justify-between border-b border-white/[0.06] px-6 py-5">

                        <div class="flex items-center gap-3">

                            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-500/10 ring-1 ring-blue-400/10">
                                <i class="text-blue-400 fa-solid fa-box-open"></i>
                            </div>

                            <div>
                                <p class="text-sm font-bold text-white">
                                    Equipment Center
                                </p>
                                <p class="text-[11px] text-slate-500">
                                    CICT • UNM
                                </p>
                            </div>

                        </div>

                        <div class="flex items-center gap-1.5 rounded-full border border-emerald-400/10 bg-emerald-400/5 px-2.5 py-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            <span class="text-[10px] font-semibold text-emerald-400">
                                ONLINE
                            </span>
                        </div>

                    </div>


                    <!-- Logo Area -->
                    <div class="px-6 pt-7">

                        <div class="relative flex h-44 items-center justify-center overflow-hidden rounded-2xl border border-white/[0.06] bg-gradient-to-br from-[#111a2d] to-[#0a0f1b]">

                            <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(59,130,246,.12),transparent_65%)]"></div>

                            <div class="relative flex h-24 w-24 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.04] p-4 shadow-2xl">
                                <img
                                    src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png"
                                    alt="CICT Logo"
                                    class="object-contain max-w-full max-h-full"
                                >
                            </div>

                        </div>

                    </div>


                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-3 px-6 pt-5">

                        <div class="rounded-2xl border border-white/[0.05] bg-white/[0.025] p-4 transition hover:bg-white/[0.045]">
                            <div class="flex items-center justify-center w-8 h-8 mb-2 rounded-lg bg-blue-500/10">
                                <i class="text-xs text-blue-400 fa-solid fa-cubes"></i>
                            </div>
                            <div class="text-xl font-extrabold tracking-tight text-white">
                                500+
                            </div>
                            <div class="mt-0.5 text-[10px] font-medium uppercase tracking-wider text-slate-500">
                                Items
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/[0.05] bg-white/[0.025] p-4 transition hover:bg-white/[0.045]">
                            <div class="flex items-center justify-center w-8 h-8 mb-2 rounded-lg bg-violet-500/10">
                                <i class="text-xs fa-solid fa-clock text-violet-400"></i>
                            </div>
                            <div class="text-xl font-extrabold tracking-tight text-white">
                                24/7
                            </div>
                            <div class="mt-0.5 text-[10px] font-medium uppercase tracking-wider text-slate-500">
                                Access
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/[0.05] bg-white/[0.025] p-4 transition hover:bg-white/[0.045]">
                            <div class="flex items-center justify-center w-8 h-8 mb-2 rounded-lg bg-emerald-500/10">
                                <i class="text-xs fa-solid fa-check text-emerald-400"></i>
                            </div>
                            <div class="text-xl font-extrabold tracking-tight text-white">
                                100%
                            </div>
                            <div class="mt-0.5 text-[10px] font-medium uppercase tracking-wider text-slate-500">
                                Trusted
                            </div>
                        </div>

                    </div>


                    <!-- Bottom Status -->
                    <div class="mx-6 mb-6 mt-5 flex items-center justify-between rounded-2xl border border-blue-400/10 bg-blue-500/[0.045] px-4 py-3.5">

                        <div class="flex items-center gap-3">

                            <div class="flex items-center justify-center rounded-lg h-9 w-9 bg-blue-500/10">
                                <i class="text-xs text-blue-400 fa-solid fa-layer-group"></i>
                            </div>

                            <div>
                                <p class="text-xs font-semibold text-slate-200">
                                    Equipment management
                                </p>
                                <p class="text-[10px] text-slate-500">
                                    Request • Borrow • Return
                                </p>
                            </div>

                        </div>

                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-600"></i>

                    </div>

                </div>


                <!-- Floating Notification -->
                <div class="absolute -left-5 top-16 hidden rounded-2xl border border-white/[0.08] bg-[#101827]/95 px-4 py-3 shadow-2xl backdrop-blur-xl sm:block">

                    <div class="flex items-center gap-3">

                        <div class="flex items-center justify-center h-9 w-9 rounded-xl bg-emerald-500/10">
                            <i class="text-sm fa-solid fa-circle-check text-emerald-400"></i>
                        </div>

                        <div>
                            <p class="text-[11px] font-bold text-white">
                                System Ready
                            </p>
                            <p class="text-[10px] text-slate-500">
                                Accepting requests
                            </p>
                        </div>

                    </div>

                </div>


                <!-- Floating Shield -->
                <div class="absolute -bottom-5 -right-4 hidden rounded-2xl border border-white/[0.08] bg-[#101827]/95 px-4 py-3 shadow-2xl backdrop-blur-xl sm:block">

                    <div class="flex items-center gap-3">

                        <div class="flex items-center justify-center h-9 w-9 rounded-xl bg-blue-500/10">
                            <i class="text-sm text-blue-400 fa-solid fa-shield-halved"></i>
                        </div>

                        <div>
                            <p class="text-[11px] font-bold text-white">
                                Secure
                            </p>
                            <p class="text-[10px] text-slate-500">
                                Protected access
                            </p>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>
</div>

</div> @endsection
