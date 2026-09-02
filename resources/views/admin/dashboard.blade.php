@extends("components.default")

@section("title", "Dashboard - CICT Equipment Borrower System")

@section("content")
@include('components.admin.navbar')

<style>
    /* Custom Neumorphic Styling Utility Classes */
    :root {
        --neu-bg: #e2e8f0;
        --neu-light: #ffffff;
        --neu-dark: #cbd5e1;
    }

    .neu-flat {
        background: #e2e8f0;
        box-shadow: 6px 6px 12px #cbd5e1, -6px -6px 12px #ffffff;
    }

    .neu-pressed {
        background: #e2e8f0;
        box-shadow: inset 4px 4px 8px #cbd5e1, inset -4px -4px 8px #ffffff;
    }

    .neu-btn {
        background: #e2e8f0;
        box-shadow: 5px 5px 10px #cbd5e1, -5px -5px 10px #ffffff;
        transition: all 0.2s ease-in-out;
    }

    .neu-btn:hover {
        box-shadow: 2px 2px 5px #cbd5e1, -2px -2px 5px #ffffff;
    }

    .neu-btn:active {
        box-shadow: inset 3px 3px 6px #cbd5e1, inset -3px -3px 6px #ffffff;
    }
</style>

<div class="dash-bg md:ml-80 min-h-screen bg-[#e2e8f0] text-slate-700 font-sans transition-colors duration-300">
    <header class="sticky top-0 z-30 bg-[#e2e8f0]/90 backdrop-blur-md border-b border-slate-300/40">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center gap-4">
                <button id="menu-toggle" class="neu-btn p-2.5 rounded-xl text-slate-600 hover:text-indigo-600 md:hidden focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <i class="text-base fas fa-bars"></i>
                </button>
                <div>
                    <h1 class="text-xs font-bold tracking-widest uppercase text-slate-500">Dashboard</h1>
                    <p class="text-xs text-slate-600 font-medium mt-0.5">Welcome back — Overview</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative hidden md:block">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                        <i class="text-slate-400 fas fa-search text-xs"></i>
                    </div>
                    <input type="text" class="w-60 py-2 pl-9 pr-4 rounded-xl text-xs neu-pressed border border-slate-300/30 text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-400/50 transition-all" placeholder="Search...">
                </div>

                <div class="flex items-center gap-3 px-3 py-1.5 rounded-xl neu-flat border border-white/50">
                    <img class="object-cover h-8 w-8 rounded-lg border border-slate-200" src="https://ui-avatars.com/api/?name=Admin+User&background=6366f1&color=fff&bold=true" alt="Admin">
                    <div class="hidden md:block leading-tight">
                        <p class="text-xs font-bold text-slate-800">Admin User</p>
                        <p class="text-[10px] text-slate-500 font-medium">Administrator</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="p-6 space-y-6 max-w-[1440px] mx-auto">

        <section>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-5">
                <div class="neu-flat p-5 rounded-2xl border border-white/60 flex items-center justify-between transition-transform duration-200 hover:-translate-y-0.5">
                    <div>
                        <p class="text-[11px] font-bold tracking-wider uppercase text-slate-400">Total Equipment</p>
                        <p class="text-2xl font-black tracking-tight text-slate-800 mt-1 tabular-nums">{{ $equipments->count() }}</p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Inventory count</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl neu-pressed grid place-items-center text-indigo-600">
                        <i class="fas fa-tools text-base"></i>
                    </div>
                </div>

                <div class="neu-flat p-5 rounded-2xl border border-white/60 flex items-center justify-between transition-transform duration-200 hover:-translate-y-0.5">
                    <div>
                        <p class="text-[11px] font-bold tracking-wider uppercase text-slate-400">Active Users</p>
                        <p class="text-2xl font-black tracking-tight text-slate-800 mt-1 tabular-nums">{{ $users->count() }}</p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Registered members</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl neu-pressed grid place-items-center text-indigo-600">
                        <i class="fas fa-users text-base"></i>
                    </div>
                </div>

                <div class="neu-flat p-5 rounded-2xl border border-white/60 flex items-center justify-between transition-transform duration-200 hover:-translate-y-0.5">
                    <div>
                        <p class="text-[11px] font-bold tracking-wider uppercase text-slate-400">Transactions</p>
                        <p class="text-2xl font-black tracking-tight text-slate-800 mt-1 tabular-nums">{{ $transactions->count() }}</p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Currently borrowed</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl neu-pressed grid place-items-center text-indigo-600">
                        <i class="fas fa-exchange-alt text-base"></i>
                    </div>
                </div>

                <div class="neu-flat p-5 rounded-2xl border border-white/60 flex items-center justify-between transition-transform duration-200 hover:-translate-y-0.5">
                    <div>
                        <p class="text-[11px] font-bold tracking-wider uppercase text-slate-400">Total Requests</p>
                        <p class="text-2xl font-black tracking-tight text-slate-800 mt-1 tabular-nums">{{ $requests->count() }}</p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">All recorded statuses</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl neu-pressed grid place-items-center text-indigo-600">
                        <i class="fas fa-clipboard-list text-base"></i>
                    </div>
                </div>
            </div>
        </section>

        <div class="neu-flat p-6 rounded-2xl border border-white/60">
            <div class="flex items-center justify-between mb-6 pb-2 border-b border-slate-300/50">
                <h3 class="text-base font-bold tracking-tight text-slate-800">Recent Return Logs</h3>
                <a href="{{ route('admin.logs') }}" class="neu-btn px-3 py-1.5 rounded-xl text-xs font-semibold text-indigo-600 hover:text-indigo-700">View All &rarr;</a>
            </div>

            <div class="space-y-3">
                @forelse ($returnLogs as $returnLog)
                    <div class="flex items-center justify-between p-3.5 rounded-xl neu-flat hover:border-slate-300/60 transition duration-150">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-10 h-10 rounded-xl neu-pressed grid place-items-center shrink-0 text-indigo-600">
                                <i class="fas fa-undo text-xs"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-800 truncate">{{ $returnLog->equipment->equipment_name ?? 'N/A' }}</p>
                                <p class="text-xs text-slate-500 leading-relaxed truncate">
                                    Borrowed by: <span class="text-slate-700 font-medium">{{ $returnLog->borrower->name ?? 'N/A' }}</span> &middot;
                                    Received by: <span class="text-slate-700 font-medium">{{ $returnLog->receiver->name ?? 'N/A' }}</span>
                                </p>
                            </div>
                        </div>
                        <span class="text-[11px] font-medium text-slate-400 bg-slate-200/50 px-2.5 py-1 rounded-lg shrink-0 border border-white/50">
                            {{ $returnLog->created_at->diffForHumans() }}
                        </span>
                    </div>
                @empty
                    <div class="neu-pressed p-8 rounded-xl text-center">
                        <p class="text-sm font-medium text-slate-500">No return logs recorded yet.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </main>
</div>
@endsection
