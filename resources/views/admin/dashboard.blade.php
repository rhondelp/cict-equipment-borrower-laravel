@extends("components.default")

@section("title", "Dashboard - CICT Equipment Borrower System")

@section("content")
@include('components.admin.navbar')
<div class="dash-bg md:ml-80 min-h-screen">
    <!-- Top Header -->
    <header class="sticky top-0 z-30 dash-header">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center gap-4">
                <button id="menu-toggle" class="text-[#8b93a8] hover:text-white md:hidden">
                    <i class="text-xl fas fa-bars"></i>
                </button>
                <div>
                    <h1 class="text-[18px] font-bold tracking-tight text-white">DASHBOARD</h1>
                    <p class="text-xs text-[#8b93a8]">Welcome back, Admin! Here's your overview.</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative hidden md:block">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="text-[#6b7a99] fas fa-search text-sm"></i>
                    </div>
                    <input type="text" class="w-64 py-2 pl-10 pr-4 rounded-xl text-sm bg-[#0d1220] border border-white/10 text-slate-200 placeholder:text-[#5a6584] focus:outline-none focus:border-blue-500/40 focus:ring-2 focus:ring-blue-500/15" placeholder="Search...">
                </div>
                <button class="relative p-2 text-[#8b93a8] hover:text-white transition">
                    <i class="text-lg fas fa-bell"></i>
                    <span class="absolute flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-red-500 rounded-full -top-1 -right-1">3</span>
                </button>
                <div class="flex items-center gap-3">
                    <img class="object-cover h-9 w-9 rounded-xl border border-white/10" src="https://ui-avatars.com/api/?name=Admin+User&background=1e293b&color=fff&bold=true" alt="Admin">
                    <div class="hidden md:block">
                        <p class="text-sm font-semibold text-white leading-none">Admin User</p>
                        <p class="text-xs text-[#8b93a8]">Administrator</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="p-6 space-y-6">

        <!-- Quick Stats -->
        <section>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div class="stat-card flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-semibold tracking-widest uppercase text-[#8b93a8]">Total Equipment</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ $equipments->count() }}</p>
                        <p class="text-xs text-[#6b7a99] mt-1">Inventory</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-blue-500/15 border border-blue-500/20 grid place-items-center">
                        <i class="text-blue-400 fas fa-tools"></i>
                    </div>
                </div>
                <div class="stat-card flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-semibold tracking-widest uppercase text-[#8b93a8]">Active Users</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ $users->count() }}</p>
                        <p class="text-xs text-[#6b7a99] mt-1">Registered</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-500/15 border border-emerald-500/20 grid place-items-center">
                        <i class="text-emerald-400 fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-card flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-semibold tracking-widest uppercase text-[#8b93a8]">Transactions</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ $transactions->count() }}</p>
                        <p class="text-xs text-[#6b7a99] mt-1">Borrowed</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-violet-500/15 border border-violet-500/20 grid place-items-center">
                        <i class="text-violet-400 fas fa-exchange-alt"></i>
                    </div>
                </div>
                <div class="stat-card flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-semibold tracking-widest uppercase text-[#8b93a8]">Total Requests</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ $requests->count() }}</p>
                        <p class="text-xs text-[#6b7a99] mt-1">All status</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-500/15 border border-amber-500/20 grid place-items-center">
                        <i class="text-amber-400 fas fa-clipboard-list"></i>
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
