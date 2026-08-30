@extends("components.default")

@section("title", "Dashboard - CICT Equipment Borrower System")

@section("content")
@include('components.admin.navbar')
<div class="dash-bg md:ml-80 min-h-screen">
    <!-- Top Header — dense, functional -->
    <header class="sticky top-0 z-30 dash-header">
        <div class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <button id="menu-toggle" class="text-[#94a3b8] hover:text-white md:hidden">
                    <i class="text-lg fas fa-bars"></i>
                </button>
                <div>
                    <h1 class="text-[13px] font-semibold tracking-widest uppercase text-white">Dashboard</h1>
                    <p class="text-xs text-[#94a3b8]">Welcome back — overview</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative hidden md:block">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="text-[#7a8aaa] fas fa-search text-xs"></i>
                    </div>
                    <input type="text" class="w-56 py-1.5 pl-9 pr-3 rounded-lg text-sm bg-[#0d1220] border border-white/10 text-slate-200 placeholder:text-[#7a8aaa] focus:outline-none focus:border-[rgba(91,141,224,0.4)] focus:ring-2 focus:ring-[rgba(91,141,224,0.15)]" placeholder="Search">
                </div>
                <div class="flex items-center gap-2.5">
                    <img class="object-cover h-8 w-8 rounded-lg border border-white/10" src="https://ui-avatars.com/api/?name=Admin+User&background=1e293b&color=fff&bold=true" alt="Admin">
                    <div class="hidden md:block leading-none">
                        <p class="text-xs font-semibold text-white">Admin User</p>
                        <p class="text-[11px] text-[#94a3b8]">Administrator</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content — denser -->
    <main class="p-4 space-y-4 max-w-[1440px] mx-auto">

        <!-- Quick Stats — single accent, no rainbow -->
        <section>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="stat-card flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-medium tracking-widest uppercase text-[#94a3b8]">Total equipment</p>
                        <p class="text-xl font-semibold tracking-tight text-white mt-1 tabular-nums">{{ $equipments->count() }}</p>
                        <p class="text-[11px] text-[#94a3b8] mt-1">Inventory</p>
                    </div>
                    <div class="w-9 h-9 rounded-lg bg-[rgba(91,141,224,0.10)] border border-[rgba(91,141,224,0.14)] grid place-items-center">
                        <i class="text-[#8aa8e8] fas fa-tools text-sm"></i>
                    </div>
                </div>
                <div class="stat-card flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-medium tracking-widest uppercase text-[#94a3b8]">Active users</p>
                        <p class="text-xl font-semibold tracking-tight text-white mt-1 tabular-nums">{{ $users->count() }}</p>
                        <p class="text-[11px] text-[#94a3b8] mt-1">Registered</p>
                    </div>
                    <div class="w-9 h-9 rounded-lg bg-[rgba(91,141,224,0.08)] border border-[rgba(91,141,224,0.12)] grid place-items-center">
                        <i class="text-[#8aa8e8] fas fa-users text-sm"></i>
                    </div>
                </div>
                <div class="stat-card flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-medium tracking-widest uppercase text-[#94a3b8]">Transactions</p>
                        <p class="text-xl font-semibold tracking-tight text-white mt-1 tabular-nums">{{ $transactions->count() }}</p>
                        <p class="text-[11px] text-[#94a3b8] mt-1">Borrowed</p>
                    </div>
                    <div class="w-9 h-9 rounded-lg bg-[rgba(91,141,224,0.08)] border border-[rgba(91,141,224,0.12)] grid place-items-center">
                        <i class="text-[#8aa8e8] fas fa-exchange-alt text-sm"></i>
                    </div>
                </div>
                <div class="stat-card flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-medium tracking-widest uppercase text-[#94a3b8]">Total requests</p>
                        <p class="text-xl font-semibold tracking-tight text-white mt-1 tabular-nums">{{ $requests->count() }}</p>
                        <p class="text-[11px] text-[#94a3b8] mt-1">All status</p>
                    </div>
                    <div class="w-9 h-9 rounded-lg bg-[rgba(91,141,224,0.08)] border border-[rgba(91,141,224,0.12)] grid place-items-center">
                        <i class="text-[#8aa8e8] fas fa-clipboard-list text-sm"></i>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recent Activity -->
        <div class="dash-card p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-sm font-bold tracking-tight text-white">Recent Return Logs</h3>
                <a href="{{ route('admin.logs') }}" class="text-xs font-semibold text-[#60a5fa] hover:text-[#93c5fd]">View All →</a>
            </div>
            @forelse ($returnLogs as $returnLog)
                <div class="flex items-start gap-3 py-3 border-b border-white/5 last:border-0">
                    <div class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/15 grid place-items-center shrink-0 mt-0.5">
                        <i class="text-blue-400 fas fa-undo text-xs"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-100 truncate">{{ $returnLog->equipment->equipment_name ?? 'N/A' }}</p>
                        <p class="text-xs text-[#8b93a8] leading-relaxed">
                            Borrowed by: <span class="text-slate-300">{{ $returnLog->borrower->name ?? 'N/A' }}</span> ·
                            Received by: <span class="text-slate-300">{{ $returnLog->receiver->name ?? 'N/A' }}</span> ·
                            {{ $returnLog->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-[#8b93a8] py-4 text-center">No return logs yet.</p>
            @endforelse
        </div>

    </main>
</div>
@endsection
