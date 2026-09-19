@extends("components.default")
@section("title", "Dashboard - CICT Equipment Borrower System")
@section("content")
@include('components.admin.navbar')

<div class="min-h-[100dvh] page-bg md:ml-64">

    {{-- Essential for keyboard users: the sidebar is six links deep, so without
         this every page begins with six tab stops before the content. --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>
    <x-ui.page-header eyebrow="Overview" title="Dashboard">
        <x-slot:actions>
            <div class="hidden sm:flex items-center gap-2">
                <img class="w-10 h-10 rounded-lg object-cover border border-neutral-200"
                     src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=2563eb&color=fff&bold=true"
                     alt="Admin">
                <div class="leading-none">
                    <p class="text-base font-semibold text-neutral-900">{{ Auth::user()->name }}</p>
                    <p class="text-sm text-neutral-600">Administrator</p>
                </div>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <main id="main-content" class="p-4 sm:p-6 space-y-6 max-w-content mx-auto">
        @php
            // Derived from the collections the controller already passes; no extra
            // queries. The headline figure on each tile is unchanged — the second
            // line now says what that number is made of instead of restating it.
            $pendingRequests = $requests->where('status', 'Pending')->count();
            $outNow          = $transactions->whereIn('status', ['Borrowed', 'Overdue'])->count();
            $overdue         = $transactions->where('status', 'Overdue')->count();
            $unavailable     = $equipments->where('available_quantity', '<=', 0)->count();
        @endphp

        {{-- Quick stats.
             Four identical cards in an even grid read as one card repeated, so
             each tile now carries the accent of what it measures — the treatment
             the borrower dashboard already uses — and the two that can demand
             action (pending requests, overdue loans) turn amber or red when they
             are non-zero rather than staying decorative at all times. --}}
        <section aria-label="Summary">
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">

                <article class="relative p-5 overflow-hidden transition-colors bg-white border rounded-xl border-neutral-200 hover:border-primary-200">
                    <span class="absolute inset-x-0 top-0 h-1 bg-primary-500" aria-hidden="true"></span>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold tracking-wider uppercase text-neutral-600">Equipment</p>
                            <p class="mt-2 text-3xl font-bold text-neutral-900 tabular-nums">{{ $equipments->count() }}</p>
                            <p class="mt-1 text-sm text-neutral-600">
                                @if($unavailable > 0)
                                    <span class="font-semibold text-danger-700 tabular-nums">{{ $unavailable }}</span> out of stock
                                @else
                                    All items in stock
                                @endif
                            </p>
                        </div>
                        <span class="grid w-12 h-12 border rounded-lg shrink-0 bg-primary-50 border-primary-100 place-items-center">
                            <i class="text-lg fas fa-toolbox text-primary-600" aria-hidden="true"></i>
                        </span>
                    </div>
                </article>

                <article class="relative p-5 overflow-hidden transition-colors bg-white border rounded-xl border-neutral-200 hover:border-neutral-300">
                    <span class="absolute inset-x-0 top-0 h-1 bg-neutral-300" aria-hidden="true"></span>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold tracking-wider uppercase text-neutral-600">Users</p>
                            <p class="mt-2 text-3xl font-bold text-neutral-900 tabular-nums">{{ $users->count() }}</p>
                            <p class="mt-1 text-sm text-neutral-600 tabular-nums">
                                {{ $users->where('user_type', 'Instructor')->count() }} instructors &middot;
                                {{ $users->where('user_type', 'Student')->count() }} students
                            </p>
                        </div>
                        <span class="grid w-12 h-12 border rounded-lg shrink-0 bg-neutral-50 border-neutral-200 place-items-center">
                            <i class="text-lg fas fa-users text-neutral-600" aria-hidden="true"></i>
                        </span>
                    </div>
                </article>

                <article class="relative overflow-hidden rounded-xl border p-5 transition-colors {{ $overdue > 0 ? 'border-danger-200 bg-danger-50/60' : 'border-neutral-200 bg-white hover:border-neutral-300' }}">
                    <span class="absolute inset-x-0 top-0 h-1 {{ $overdue > 0 ? 'bg-danger-500' : 'bg-success-500' }}" aria-hidden="true"></span>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold tracking-wider uppercase text-neutral-600">Out on loan</p>
                            <p class="mt-2 text-3xl font-bold text-neutral-900 tabular-nums">{{ $outNow }}</p>
                            <p class="mt-1 text-sm {{ $overdue > 0 ? 'font-semibold text-danger-700' : 'text-neutral-600' }}">
                                @if($overdue > 0)
                                    <span class="tabular-nums">{{ $overdue }}</span> overdue
                                @else
                                    None overdue
                                @endif
                            </p>
                        </div>
                        <span class="grid w-12 h-12 border rounded-lg shrink-0 place-items-center {{ $overdue > 0 ? 'bg-danger-100 border-danger-200' : 'bg-success-50 border-success-200' }}">
                            <i class="text-lg fas {{ $overdue > 0 ? 'fa-triangle-exclamation text-danger-700' : 'fa-right-left text-success-700' }}" aria-hidden="true"></i>
                        </span>
                    </div>
                </article>

                {{-- The one tile that is a to-do list rather than a readout, so it
                     is also a link: the count and the place to act on it become
                     the same control. --}}
                <a href="{{ route('admin.request') }}"
                   class="relative block overflow-hidden rounded-xl border p-5 transition-colors active:translate-y-px {{ $pendingRequests > 0 ? 'border-warning-300 bg-warning-50/60 hover:border-warning-400' : 'border-neutral-200 bg-white hover:border-neutral-300' }}">
                    <span class="absolute inset-x-0 top-0 h-1 {{ $pendingRequests > 0 ? 'bg-warning-400' : 'bg-neutral-300' }}" aria-hidden="true"></span>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold tracking-wider uppercase text-neutral-600">Requests</p>
                            <p class="mt-2 text-3xl font-bold text-neutral-900 tabular-nums">{{ $requests->count() }}</p>
                            <p class="mt-1 text-sm {{ $pendingRequests > 0 ? 'font-semibold text-warning-700' : 'text-neutral-600' }}">
                                @if($pendingRequests > 0)
                                    <span class="tabular-nums">{{ $pendingRequests }}</span> awaiting review
                                @else
                                    Nothing awaiting review
                                @endif
                            </p>
                        </div>
                        <span class="grid w-12 h-12 border rounded-lg shrink-0 place-items-center {{ $pendingRequests > 0 ? 'bg-warning-100 border-warning-200' : 'bg-neutral-50 border-neutral-200' }}">
                            <i class="text-lg fas fa-clipboard-list {{ $pendingRequests > 0 ? 'text-warning-700' : 'text-neutral-600' }}" aria-hidden="true"></i>
                        </span>
                    </div>
                </a>

            </div>
        </section>

        {{-- Recent return logs --}}
        <section>
            <x-ui.table-card>
                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
                    <div>
                        <h3 class="text-lg font-semibold text-neutral-900">Recent Return Logs</h3>
                        <p class="text-sm text-neutral-600 mt-0.5">Latest equipment returns</p>
                    </div>
                    <a href="{{ route('admin.logs') }}"
                       class="inline-flex items-center gap-2 min-h-[40px] px-3 -mr-3 rounded-md text-base font-semibold text-primary-700 hover:text-primary-800 hover:bg-primary-50">
                        View All <i class="fas fa-arrow-right text-sm"></i>
                    </a>
                </div>

                @if($returnLogs->isEmpty())
                    <x-ui.empty-state icon="fa-inbox" title="No return logs yet"
                                      message="When borrowers return equipment, the logs appear here." />
                @else
                    <ul class="divide-y divide-neutral-200">
                        @foreach ($returnLogs as $returnLog)
                            <li class="flex items-start gap-4 px-6 py-4">
                                <div class="w-11 h-11 rounded-lg bg-primary-50 border border-primary-100 grid place-items-center shrink-0 mt-0.5">
                                    <i class="text-primary-600 fas fa-undo text-base"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-base font-semibold text-neutral-900 truncate">{{ $returnLog->equipment->equipment_name ?? 'N/A' }}</p>
                                    <p class="text-base text-neutral-600 mt-1 leading-relaxed">
                                        Borrowed by: <span class="text-neutral-900 font-semibold">{{ $returnLog->borrower->name ?? 'N/A' }}</span> ·
                                        Received by: <span class="text-neutral-900 font-semibold">{{ $returnLog->receiver->name ?? 'N/A' }}</span> ·
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
