@extends("components.default")

@section("title", "Dashboard - CICT Equipment Borrower System")

@section("content")
@include('components.admin.navbar')
<div class="md:ml-72 min-h-screen bg-slate-50 flex flex-col font-sans transition-all duration-300">
    <!-- Top Header — Clean, high contrast, responsive -->
    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-slate-200/80 px-4 sm:px-6 py-3.5 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <!-- Left: Toggle & Page Title -->
            <div class="flex items-center gap-3">
                <button id="menu-toggle" class="p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition md:hidden focus:outline-none focus:ring-2 focus:ring-blue-500/20" aria-label="Toggle navigation menu">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div>
                    <h1 class="text-base sm:text-lg font-bold text-slate-800 tracking-tight leading-none">Dashboard Overview</h1>
                    <p class="text-xs text-slate-500 mt-0.5 hidden sm:block">Real-time status of equipment, transactions, and user requests</p>
                </div>
            </div>

            <!-- Right: Search Bar & Admin Profile Pill -->
            <div class="flex items-center gap-3">
                <div class="relative hidden lg:block">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <i class="fas fa-magnifying-glass text-xs"></i>
                    </div>
                    <input type="text" class="w-64 py-1.5 pl-9 pr-3 rounded-lg text-xs bg-slate-50 border border-slate-200 text-slate-700 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 transition" placeholder="Search dashboard...">
                </div>
                <div class="flex items-center gap-2.5 pl-2 sm:border-l sm:border-slate-200">
                    <div class="w-8 h-8 rounded-full bg-blue-100 border border-blue-200 flex items-center justify-center text-blue-700 font-bold text-xs shadow-sm">
                        {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                    </div>
                    <div class="hidden sm:block text-left leading-tight">
                        <p class="text-xs font-semibold text-slate-800">{{ Auth::user()->name ?? 'Admin User' }}</p>
                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-medium bg-blue-50 text-blue-600 border border-blue-100">Administrator</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Body -->
    <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl w-full mx-auto">

        <!-- Welcome Banner -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-700 p-6 sm:p-7 text-white shadow-md shadow-blue-900/10">
            <div class="relative z-10 max-w-2xl">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/15 text-white backdrop-blur-md mb-2.5 border border-white/10">
                    <i class="fas fa-sparkles text-[10px]"></i> CICT Equipment Hub
                </span>
                <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white mb-1 font-sans">
                    Welcome back, {{ Auth::user()->name ?? 'Administrator' }}!
                </h2>
                <p class="text-xs sm:text-sm text-blue-100 font-normal leading-relaxed">
                    Here is what is happening across inventory and borrow requests today.
                </p>
            </div>
            <div class="absolute -right-10 -top-10 w-52 h-52 rounded-full bg-white/10 blur-xl pointer-events-none"></div>
            <div class="absolute right-20 -bottom-10 w-40 h-40 rounded-full bg-indigo-500/20 blur-lg pointer-events-none"></div>
        </div>

        <!-- Metric Stat Cards Grid -->
        <section>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <!-- Total Equipment Card -->
                <div class="group relative bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition duration-200 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Equipment</p>
                        <p class="text-2xl font-bold text-slate-800 mt-1.5 tabular-nums tracking-tight font-sans">{{ $equipments->count() }}</p>
                        <div class="flex items-center gap-1.5 mt-2">
                            <span class="inline-flex items-center text-[11px] font-medium text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-md border border-emerald-100">
                                <i class="fas fa-check text-[9px] mr-1"></i> In System
                            </span>
                            <span class="text-[11px] text-slate-400">Inventory</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-lg group-hover:scale-105 transition-transform shrink-0 shadow-sm">
                        <i class="fas fa-boxes-stacked"></i>
                    </div>
                </div>

                <!-- Active Users Card -->
                <div class="group relative bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition duration-200 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Users</p>
                        <p class="text-2xl font-bold text-slate-800 mt-1.5 tabular-nums tracking-tight font-sans">{{ $users->count() }}</p>
                        <div class="flex items-center gap-1.5 mt-2">
                            <span class="inline-flex items-center text-[11px] font-medium text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded-md border border-blue-100">
                                <i class="fas fa-user-check text-[9px] mr-1"></i> Registered
                            </span>
                            <span class="text-[11px] text-slate-400">Accounts</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-lg group-hover:scale-105 transition-transform shrink-0 shadow-sm">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

                <!-- Active Transactions Card -->
                <div class="group relative bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition duration-200 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Transactions</p>
                        <p class="text-2xl font-bold text-slate-800 mt-1.5 tabular-nums tracking-tight font-sans">{{ $transactions->count() }}</p>
                        <div class="flex items-center gap-1.5 mt-2">
                            <span class="inline-flex items-center text-[11px] font-medium text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-md border border-amber-100">
                                <i class="fas fa-arrow-right-arrow-left text-[9px] mr-1"></i> Borrowed
                            </span>
                            <span class="text-[11px] text-slate-400">Total logs</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-lg group-hover:scale-105 transition-transform shrink-0 shadow-sm">
                        <i class="fas fa-handshake"></i>
                    </div>
                </div>

                <!-- Total Requests Card -->
                <div class="group relative bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition duration-200 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Requests</p>
                        <p class="text-2xl font-bold text-slate-800 mt-1.5 tabular-nums tracking-tight font-sans">{{ $requests->count() }}</p>
                        <div class="flex items-center gap-1.5 mt-2">
                            <span class="inline-flex items-center text-[11px] font-medium text-purple-600 bg-purple-50 px-1.5 py-0.5 rounded-md border border-purple-100">
                                <i class="fas fa-clock text-[9px] mr-1"></i> All Status
                            </span>
                            <span class="text-[11px] text-slate-400">Requests</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-lg group-hover:scale-105 transition-transform shrink-0 shadow-sm">
                        <i class="fas fa-clipboard-question"></i>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Access Navigation & Activity Feeds Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Recent Return Logs -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                            <i class="fas fa-clock-rotate-left"></i>
                        </div>
                        <div>
                            <h3 class="text-xs sm:text-sm font-bold text-slate-800">Recent Return Logs</h3>
                            <p class="text-[11px] text-slate-500">Latest returned items recorded by staff</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.logs') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline transition">
                        <span>View all</span>
                        <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>

                <div class="p-5 divide-y divide-slate-100 flex-1">
                    @forelse ($returnLogs->take(6) as $returnLog)
                        <div class="py-3.5 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-3 group">
                            <div class="flex items-start gap-3.5 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200/60 text-slate-600 flex items-center justify-center shrink-0 mt-0.5 group-hover:bg-blue-50 group-hover:text-blue-600 group-hover:border-blue-200 transition">
                                    <i class="fas fa-box-archive text-xs"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-800 truncate">{{ $returnLog->equipment->equipment_name ?? 'Equipment Item' }}</p>
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 mt-1 text-[11px] text-slate-500">
                                        <span><i class="fas fa-user text-[10px] mr-1 text-slate-400"></i><strong class="font-medium text-slate-700">{{ $returnLog->borrower->name ?? 'N/A' }}</strong></span>
                                        <span class="text-slate-300">•</span>
                                        <span>Receiver: <strong class="font-medium text-slate-700">{{ $returnLog->receiver->name ?? 'Staff' }}</strong></span>
                                    </div>
                                </div>
                            </div>
                            <span class="self-start sm:self-center text-[11px] font-medium text-slate-500 bg-slate-50 px-2 py-0.5 rounded-md border border-slate-150 shrink-0">
                                {{ $returnLog->created_at ? $returnLog->created_at->diffForHumans() : 'Recently' }}
                            </span>
                        </div>
                    @empty
                        <div class="text-center py-10">
                            <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2.5">
                                <i class="fas fa-clipboard text-sm"></i>
                            </div>
                            <p class="text-xs font-medium text-slate-600">No return logs recorded yet</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Return records will appear here as items get returned.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Right 1 Col: Quick Links & Summary -->
            <div class="space-y-5">
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">Quick Navigation</h3>
                    <div class="space-y-2">
                        <a href="{{ route('admin.equipment') }}" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-100 hover:border-blue-100 transition group">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xs group-hover:scale-105 transition-transform">
                                    <i class="fas fa-box-open"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-700 group-hover:text-blue-700">Inventory Items</p>
                                    <p class="text-[11px] text-slate-400">Add & modify assets</p>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-slate-300 group-hover:text-blue-500 text-xs"></i>
                        </a>

                        <a href="{{ route('admin.request') }}" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-purple-50 border border-slate-100 hover:border-purple-100 transition group">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-xs group-hover:scale-105 transition-transform">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-700 group-hover:text-purple-700">Borrow Requests</p>
                                    <p class="text-[11px] text-slate-400">Review pending requests</p>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-slate-300 group-hover:text-purple-500 text-xs"></i>
                        </a>

                        <a href="{{ route('admin.transaction') }}" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-amber-50 border border-slate-100 hover:border-amber-100 transition group">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xs group-hover:scale-105 transition-transform">
                                    <i class="fas fa-clock-rotate-left"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-700 group-hover:text-amber-700">Transactions</p>
                                    <p class="text-[11px] text-slate-400">View borrowed records</p>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-slate-300 group-hover:text-amber-500 text-xs"></i>
                        </a>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-5 text-white shadow-sm border border-slate-800">
                    <div class="flex items-center gap-3 mb-2.5">
                        <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT" class="w-7 h-7 object-contain">
                        <div>
                            <p class="text-xs font-bold text-white leading-tight">CICT Laboratory Hub</p>
                            <p class="text-[10px] text-slate-400">Equipment Borrower Portal</p>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-300 leading-relaxed">
                        Verify item serials and condition remarks prior to accepting returns.
                    </p>
                </div>
            </div>
        </div>

    </main>
</div>
@endsection
