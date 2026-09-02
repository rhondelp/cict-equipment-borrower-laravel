@extends("components.default")

@section("title", "Dashboard - CICT Equipment Borrower System")

@section("content")
@include('components.admin.navbar')
<div class="dash-bg md:ml-80 min-h-screen bg-neutral-950 text-neutral-100 font-sans antialiased">
    <header class="sticky top-0 z-30 bg-neutral-950/80 backdrop-blur-md border-b border-white/10 dash-header">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center gap-4">
                <button id="menu-toggle" class="p-2 rounded-lg text-neutral-400 hover:text-white hover:bg-white/5 transition-colors md:hidden">
                    <i class="text-xl fas fa-bars"></i>
                </button>
                <div>
                    <h1 class="text-base font-bold tracking-wider uppercase text-white">Dashboard</h1>
                    <p class="text-sm text-neutral-400">Welcome back — system overview</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative hidden md:block">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                        <i class="text-neutral-500 fas fa-search text-sm"></i>
                    </div>
                    <input type="text" class="w-64 py-2 pl-10 pr-4 rounded-xl text-sm bg-neutral-900/90 border border-white/10 text-neutral-200 placeholder:text-neutral-500 focus:outline-none focus:border-primary-500/50 focus:ring-2 focus:ring-primary-500/20 transition" placeholder="Search...">
                </div>
                <div class="flex items-center gap-3 pl-2 border-l border-white/10">
                    <img class="object-cover h-9 w-9 rounded-xl border border-white/15 shadow-sm" src="https://ui-avatars.com/api/?name=Admin+User&background=1e293b&color=fff&bold=true" alt="Admin">
                    <div class="hidden md:block leading-tight">
                        <p class="text-sm font-semibold text-white">Admin User</p>
                        <p class="text-xs text-neutral-400">Administrator</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="p-6 space-y-6 max-w-[1440px] mx-auto">

        <section>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div class="stat-card p-5 rounded-2xl bg-neutral-900/60 border border-white/10 hover:border-primary-500/30 transition-all duration-200 flex items-center justify-between shadow-lg">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold tracking-wider uppercase text-neutral-400">Total equipment</p>
                        <p class="text-2xl font-bold tracking-tight text-white tabular-nums">{{ $equipments->count() }}</p>
                        <p class="text-xs text-neutral-500">Inventory items</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-primary-500/10 border border-primary-500/20 grid place-items-center shrink-0">
                        <i class="text-primary-400 fas fa-tools text-base"></i>
                    </div>
                </div>

                <div class="stat-card p-5 rounded-2xl bg-neutral-900/60 border border-white/10 hover:border-primary-500/30 transition-all duration-200 flex items-center justify-between shadow-lg">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold tracking-wider uppercase text-neutral-400">Active users</p>
                        <p class="text-2xl font-bold tracking-tight text-white tabular-nums">{{ $users->count() }}</p>
                        <p class="text-xs text-neutral-500">Registered accounts</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-primary-500/10 border border-primary-500/20 grid place-items-center shrink-0">
                        <i class="text-primary-400 fas fa-users text-base"></i>
                    </div>
                </div>

                <div class="stat-card p-5 rounded-2xl bg-neutral-900/60 border border-white/10 hover:border-primary-500/30 transition-all duration-200 flex items-center justify-between shadow-lg">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold tracking-wider uppercase text-neutral-400">Transactions</p>
                        <p class="text-2xl font-bold tracking-tight text-white tabular-nums">{{ $transactions->count() }}</p>
                        <p class="text-xs text-neutral-500">Currently borrowed</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-primary-500/10 border border-primary-500/20 grid place-items-center shrink-0">
                        <i class="text-primary-400 fas fa-exchange-alt text-base"></i>
                    </div>
                </div>

                <div class="stat-card p-5 rounded-2xl bg-neutral-900/60 border border-white/10 hover:border-primary-500/30 transition-all duration-200 flex items-center justify-between shadow-lg">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold tracking-wider uppercase text-neutral-400">Total requests</p>
                        <p class="text-2xl font-bold tracking-tight text-white tabular-nums">{{ $requests->count() }}</p>
                        <p class="text-xs text-neutral-500">All request statuses</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-primary-500/10 border border-primary-500/20 grid place-items-center shrink-0">
                        <i class="text-primary-400 fas fa-clipboard-list text-base"></i>
                    </div>
                </div>
            </div>
        </section>

        <div class="dash-card p-6 rounded-2xl bg-neutral-900/60 border border-white/10 shadow-lg">
            <div class="flex items-center justify-between mb-6 pb-3 border-b border-white/5">
                <div>
                    <h3 class="text-base font-bold tracking-tight text-white">Recent Return Logs</h3>
                    <p class="text-xs text-neutral-400 mt-0.5">Latest equipment returned by borrowers</p>
                </div>
                <a href="{{ route('admin.logs') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-400 hover:text-primary-300 transition-colors py-1 px-3 rounded-lg hover:bg-primary-500/10">
                    View All <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <div class="divide-y divide-white/5">
                @forelse ($returnLogs as $returnLog)
                    <div class="flex items-start gap-4 py-3.5 first:pt-0 last:pb-0 hover:bg-white/[0.02] rounded-xl px-2 transition-colors">
                        <div class="w-10 h-10 rounded-xl bg-primary-500/10 border border-primary-500/20 grid place-items-center shrink-0 mt-0.5">
                            <i class="text-primary-400 fas fa-undo text-sm"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-neutral-100 truncate">{{ $returnLog->equipment->equipment_name ?? 'N/A' }}</p>
                            <p class="text-xs text-neutral-400 leading-relaxed mt-0.5">
                                Borrowed by: <span class="text-neutral-200 font-medium">{{ $returnLog->borrower->name ?? 'N/A' }}</span> &bull;
                                Received by: <span class="text-neutral-200 font-medium">{{ $returnLog->receiver->name ?? 'N/A' }}</span> &bull;
                                <span class="text-neutral-500">{{ $returnLog->created_at->diffForHumans() }}</span>
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center space-y-2">
                        <i class="fas fa-inbox text-neutral-600 text-2xl"></i>
                        <p class="text-sm text-neutral-400">No return logs available yet.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </main>
</div>
@endsection
