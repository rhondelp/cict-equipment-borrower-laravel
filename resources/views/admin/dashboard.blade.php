@extends("components.default")

@section("title", "Dashboard - CICT Equipment Borrower System")

@section("content")
@include('components.admin.navbar')
<div class="dash-bg md:ml-80 min-h-screen">
    <!-- Top Header -->
    <header class="sticky top-0 z-30 dash-header">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
                <button id="menu-toggle" class="text-neutral-400 hover:text-white md:hidden">
                    <i class="text-lg fas fa-bars"></i>
                </button>
                <div>
                    <p class="text-xs font-medium tracking-widest uppercase" style="color:var(--text-muted)">Overview</p>
                    <h1 class="text-sm font-semibold tracking-tight text-white -mt-0.5">Dashboard</h1>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <img class="object-cover h-8 w-8 rounded-lg border border-white/10" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=1e293b&color=fff&bold=true" alt="Admin">
                <div class="hidden md:block leading-none">
                    <p class="text-xs font-semibold text-white">{{ Auth::user()->name }}</p>
                    <p class="text-xs" style="color:var(--text-muted)">Administrator</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="p-6 space-y-6 max-w-[1440px] mx-auto">

        <!-- Quick Stats -->
        <section>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div class="stat-card flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold tracking-widest uppercase" style="color:var(--text-muted)">Total Equipment</p>
                        <p class="text-2xl font-bold tracking-tight text-white mt-1.5 tabular-nums">{{ $equipments->count() }}</p>
                        <p class="text-xs mt-1" style="color:var(--text-muted)">Inventory items</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-primary-500/10 border border-primary-500/20 grid place-items-center shrink-0">
                        <i class="text-primary-300 fas fa-tools text-sm"></i>
                    </div>
                </div>
                <div class="stat-card flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold tracking-widest uppercase" style="color:var(--text-muted)">Active Users</p>
                        <p class="text-2xl font-bold tracking-tight text-white mt-1.5 tabular-nums">{{ $users->count() }}</p>
                        <p class="text-xs mt-1" style="color:var(--text-muted)">Registered</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-primary-500/10 border border-primary-500/20 grid place-items-center shrink-0">
                        <i class="text-primary-300 fas fa-users text-sm"></i>
                    </div>
                </div>
                <div class="stat-card flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold tracking-widest uppercase" style="color:var(--text-muted)">Transactions</p>
                        <p class="text-2xl font-bold tracking-tight text-white mt-1.5 tabular-nums">{{ $transactions->count() }}</p>
                        <p class="text-xs mt-1" style="color:var(--text-muted)">Borrowed</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-primary-500/10 border border-primary-500/20 grid place-items-center shrink-0">
                        <i class="text-primary-300 fas fa-exchange-alt text-sm"></i>
                    </div>
                </div>
                <div class="stat-card flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold tracking-widets uppercase" style="color:var(--text-muted)">Total Requests</p>
                        <p class="text-2xl font-bold tracking-tight text-white mt-1.5 tabular-nums">{{ $requests->count() }}</p>
                        <p class="text-xs mt-1" style="color:var(--text-muted)">All status</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-primary-500/10 border border-primary-500/20 grid place-items-center shrink-0">
                        <i class="text-primary-300 fas fa-clipboard-list text-sm"></i>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recent Return Logs -->
        <div class="dash-card p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-base font-bold tracking-tight text-white">Recent Return Logs</h3>
                    <p class="text-xs mt-0.5" style="color:var(--text-muted)">Latest equipment returns</p>
                </div>
                <a href="{{ route('admin.logs') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-300 hover:text-primary-200 transition">
                    View All <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>
            @forelse ($returnLogs as $returnLog)
                <div class="flex items-start gap-4 py-4 border-b last:border-0" style="border-color:var(--border-subtle)">
                    <div class="w-10 h-10 rounded-xl bg-primary-500/10 border border-primary-500/20 grid place-items-center shrink-0 mt-0.5">
                        <i class="text-primary-300 fas fa-undo text-xs"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-white truncate">{{ $returnLog->equipment->equipment_name ?? 'N/A' }}</p>
                        <p class="text-xs mt-0.5 leading-relaxed" style="color:var(--text-muted)">
                            Borrowed by: <span class="text-neutral-300 font-medium">{{ $returnLog->borrower->name ?? 'N/A' }}</span> ·
                            Received by: <span class="text-neutral-300 font-medium">{{ $returnLog->receiver->name ?? 'N/A' }}</span> ·
                            <span>{{ $returnLog->created_at->diffForHumans() }}</span>
                        </p>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="fas fa-inbox block"></i>
                    <p>No return logs yet.</p>
                </div>
            @endforelse
        </div>

    </main>
</div>
@endsection
