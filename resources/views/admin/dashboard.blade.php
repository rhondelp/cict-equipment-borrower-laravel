@extends("components.default")
@section("title", "Dashboard - CICT Equipment Borrower System")
@section("content")
@include('components.admin.navbar')

<div class="page-bg min-h-screen md:ml-64">
    <x-ui.page-header eyebrow="Overview" title="Dashboard">
        <x-slot:actions>
            <div class="hidden sm:flex items-center gap-2">
                <img class="w-8 h-8 rounded-lg object-cover border border-neutral-200"
                     src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=2563eb&color=fff&bold=true"
                     alt="Admin">
                <div class="leading-none">
                    <p class="text-xs font-semibold text-neutral-900">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-neutral-500">Administrator</p>
                </div>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <main class="p-4 sm:p-6 space-y-6 max-w-content mx-auto">
        {{-- Quick stats --}}
        <section>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white border border-neutral-200 rounded-lg p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Total Equipment</p>
                        <p class="text-2xl font-bold tracking-tight text-neutral-900 mt-1 tabular-nums">{{ $equipments->count() }}</p>
                        <p class="text-xs text-neutral-500 mt-1">Inventory items</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-primary-50 border border-primary-100 grid place-items-center shrink-0">
                        <i class="text-primary-600 fas fa-tools text-sm"></i>
                    </div>
                </div>
                <div class="bg-white border border-neutral-200 rounded-lg p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Active Users</p>
                        <p class="text-2xl font-bold tracking-tight text-neutral-900 mt-1 tabular-nums">{{ $users->count() }}</p>
                        <p class="text-xs text-neutral-500 mt-1">Registered</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-primary-50 border border-primary-100 grid place-items-center shrink-0">
                        <i class="text-primary-600 fas fa-users text-sm"></i>
                    </div>
                </div>
                <div class="bg-white border border-neutral-200 rounded-lg p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Transactions</p>
                        <p class="text-2xl font-bold tracking-tight text-neutral-900 mt-1 tabular-nums">{{ $transactions->count() }}</p>
                        <p class="text-xs text-neutral-500 mt-1">Borrowed</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-primary-50 border border-primary-100 grid place-items-center shrink-0">
                        <i class="text-primary-600 fas fa-exchange-alt text-sm"></i>
                    </div>
                </div>
                <div class="bg-white border border-neutral-200 rounded-lg p-5 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Total Requests</p>
                        <p class="text-2xl font-bold tracking-tight text-neutral-900 mt-1 tabular-nums">{{ $requests->count() }}</p>
                        <p class="text-xs text-neutral-500 mt-1">All status</p>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-primary-50 border border-primary-100 grid place-items-center shrink-0">
                        <i class="text-primary-600 fas fa-clipboard-list text-sm"></i>
                    </div>
                </div>
            </div>
        </section>

        {{-- Recent return logs --}}
        <section>
            <x-ui.table-card>
                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
                    <div>
                        <h3 class="text-base font-semibold tracking-tight text-neutral-900">Recent Return Logs</h3>
                        <p class="text-xs text-neutral-500 mt-0.5">Latest equipment returns</p>
                    </div>
                    <a href="{{ route('admin.logs') }}"
                       class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-700 hover:text-primary-800">
                        View All <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>

                @if($returnLogs->isEmpty())
                    <div class="py-12 text-center">
                        <i class="fas fa-inbox text-3xl text-neutral-300 mb-3 block"></i>
                        <p class="text-sm font-medium text-neutral-600">No return logs yet</p>
                        <p class="text-xs text-neutral-500 mt-1">When borrowers return equipment, the logs will appear here.</p>
                    </div>
                @else
                    <ul class="divide-y divide-neutral-200">
                        @foreach ($returnLogs as $returnLog)
                            <li class="flex items-start gap-4 px-6 py-4">
                                <div class="w-9 h-9 rounded-lg bg-primary-50 border border-primary-100 grid place-items-center shrink-0 mt-0.5">
                                    <i class="text-primary-600 fas fa-undo text-xs"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-neutral-900 truncate">{{ $returnLog->equipment->equipment_name ?? 'N/A' }}</p>
                                    <p class="text-xs text-neutral-500 mt-0.5 leading-relaxed">
                                        Borrowed by: <span class="text-neutral-700 font-medium">{{ $returnLog->borrower->name ?? 'N/A' }}</span> ·
                                        Received by: <span class="text-neutral-700 font-medium">{{ $returnLog->receiver->name ?? 'N/A' }}</span> ·
                                        <span>{{ $returnLog->created_at->diffForHumans() }}</span>
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.table-card>
        </section>
    </main>
</div>
@endsection
