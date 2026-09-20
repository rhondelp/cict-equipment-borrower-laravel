@extends('components.default')
@section('title', 'Dashboard - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $firstName = str(Auth::user()->name)->before(' ');

    $unitsOut = $openLoans->sum('quantity');
    $totalUnits = $equipments->sum('quantity');
    $overdueLoans = $openLoans->filter(fn ($loan) => $loan->isOverdue());
    $pendingRequests = $requests->where('status', 'Pending');

    $typesOut = $openLoans->pluck('equipment_id')->unique()->count();

    // The activity feed: loans out, returns in and requests raised, folded into
    // one list so the dashboard can answer "what has been happening" without
    // three separate tables.
    $activity = collect()
        ->concat($transactions->map(fn ($loan) => [
            'tag' => 'Borrowed', 'tone' => 'primary',
            'item' => ($loan->equipment->equipment_name ?? 'Equipment').($loan->quantity > 1 ? ' ×'.$loan->quantity : ''),
            'verb' => 'checked out to', 'person' => $loan->user->name ?? 'a deleted user',
            'at' => $loan->created_at,
        ]))
        ->concat($returnLogs->map(fn ($log) => [
            'tag' => 'Returned', 'tone' => 'success',
            'item' => $log->equipment->equipment_name ?? 'Equipment',
            'verb' => 'returned by', 'person' => $log->borrower->name ?? 'a deleted user',
            'at' => $log->created_at,
        ]))
        ->concat($requests->map(fn ($request) => [
            'tag' => 'Request', 'tone' => 'neutral',
            'item' => ($request->equipment->equipment_name ?? 'Equipment').($request->quantity > 1 ? ' ×'.$request->quantity : ''),
            'verb' => $request->status === 'Pending' ? 'requested by' : strtolower($request->status).' for',
            'person' => $request->user->name ?? 'a deleted user',
            'at' => $request->decided_at ?? $request->created_at,
        ]))
        ->sortByDesc(fn ($event) => $event['at']?->timestamp ?? 0)
        ->take(6)
        ->values();

    $tones = [
        'danger' => ['dot' => 'bg-danger-600', 'chip' => 'bg-danger-50 text-danger-700'],
        'warning' => ['dot' => 'bg-warning-500', 'chip' => 'bg-warning-50 text-warning-700'],
        'primary' => ['dot' => 'bg-primary-600', 'chip' => 'bg-primary-50 text-primary-700'],
        'success' => ['dot' => 'bg-success-600', 'chip' => 'bg-success-50 text-success-700'],
        'neutral' => ['dot' => 'bg-neutral-400', 'chip' => 'bg-neutral-100 text-neutral-700'],
    ];
@endphp

<div class="min-h-[100dvh] page-bg md:ml-64">

    {{-- Essential for keyboard users: the sidebar is six links deep, so without
         this every page begins with six tab stops before the content. --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>

    <x-ui.page-header eyebrow="Overview" :title="$greeting.', '.$firstName">
        {{ now()->format('l, j F') }} ·
        @if(count($attention) === 0)
            nothing needs you today
        @else
            {{ count($attention) }} {{ str('thing')->plural(count($attention)) }} {{ count($attention) === 1 ? 'needs' : 'need' }} you today
        @endif
        <x-slot:actions>
            <a href="{{ route('admin.transaction') }}"
               class="inline-flex items-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold text-white rounded-md bg-primary-600 hover:bg-primary-700">
                <i class="text-base fas fa-plus" aria-hidden="true"></i> New loan
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <main id="main-content" class="p-4 mx-auto space-y-5 sm:p-6 max-w-content">

        {{-- What needs doing leads. The counters below it are context for these
             decisions, not the point of the page: a dashboard that opens with
             four tiles reading zero has told its reader nothing. --}}
        <section aria-labelledby="attention-heading" class="anim-rise">
            <x-ui.panel>
                <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-neutral-200">
                    <h2 id="attention-heading" class="text-lg font-semibold text-neutral-900">Needs your attention</h2>
                    @if(count($attention) > 0)
                        <span class="rounded-full bg-danger-50 px-2.5 py-1 text-sm font-semibold text-danger-700 tabular-nums">
                            {{ count($attention) }}
                        </span>
                    @endif
                </div>

                @forelse($attention as $item)
                    <div class="flex flex-wrap items-center gap-4 px-5 py-4 border-b border-neutral-100 last:border-b-0">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $tones[$item['tone']]['dot'] }}" aria-hidden="true"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-base font-semibold text-neutral-900">{{ $item['title'] }}</p>
                            <p class="mt-0.5 text-sm text-neutral-600 text-pretty">{{ $item['detail'] }}</p>
                        </div>
                        <a href="{{ $item['url'] }}"
                           class="inline-flex min-h-[40px] shrink-0 items-center gap-2 rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-semibold text-neutral-700 hover:border-primary-300 hover:text-primary-700">
                            {{ $item['action'] }} <i class="text-xs fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                @empty
                    <x-ui.empty-state icon="fa-check" title="Nothing outstanding"
                                      message="No overdue loans, nothing due back today, and every request has been decided." />
                @endforelse
            </x-ui.panel>
        </section>

        <x-ui.stat-strip class="anim-rise [animation-delay:70ms]" :stats="[
            ['label' => 'Units out on loan', 'value' => $unitsOut, 'unit' => 'of '.$totalUnits, 'sub' => 'Across '.$typesOut.' item '.str('type')->plural($typesOut), 'url' => route('admin.transaction')],
            ['label' => 'Overdue', 'value' => $overdueLoans->count(), 'unit' => str('loan')->plural($overdueLoans->count()), 'sub' => $overdueLoans->isEmpty() ? 'Nothing past its due date' : 'Longest: '.$overdueLoans->max(fn ($loan) => $loan->daysLate()).' days late', 'tone' => $overdueLoans->isEmpty() ? 'neutral' : 'danger', 'url' => route('admin.transaction')],
            ['label' => 'Pending requests', 'value' => $pendingRequests->count(), 'unit' => '', 'sub' => $pendingRequests->isEmpty() ? 'Queue is clear' : 'Waiting on a decision', 'tone' => $pendingRequests->isEmpty() ? 'neutral' : 'warning', 'url' => route('admin.request')],
            ['label' => 'Returned this week', 'value' => $returnedThisWeek, 'unit' => str('loan')->plural($returnedThisWeek), 'sub' => 'Checked back in and logged', 'tone' => 'success', 'url' => route('admin.logs')],
        ]" />

        <div class="grid gap-5 lg:grid-cols-2 anim-rise [animation-delay:140ms]">

            {{-- Currently out, soonest due first: the one list that answers
                 "who has what" without a search. --}}
            <section aria-labelledby="out-heading">
                <x-ui.panel>
                    <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-neutral-200">
                        <h2 id="out-heading" class="text-lg font-semibold text-neutral-900">Currently out</h2>
                        <a href="{{ route('admin.transaction') }}" class="text-sm font-semibold text-primary-700 hover:text-primary-800">
                            All loans
                        </a>
                    </div>

                    @forelse($openLoans->take(6) as $loan)
                        <div class="flex items-center gap-3 px-5 py-3 border-b border-neutral-100 last:border-b-0">
                            <div class="min-w-0 flex-1">
                                <p class="text-base font-semibold truncate text-neutral-900">
                                    {{ $loan->equipment->equipment_name ?? 'Deleted equipment' }}@if($loan->quantity > 1) <span class="text-neutral-600">×{{ $loan->quantity }}</span>@endif
                                </p>
                                <p class="text-sm truncate text-neutral-600">{{ $loan->user->name ?? 'Deleted user' }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-semibold {{ $loan->isOverdue() ? 'text-danger-700' : 'text-neutral-700' }}">
                                    {{ $loan->timingLabel() }}
                                </p>
                                <p class="text-sm text-neutral-600 tabular-nums">{{ $loan->dateRangeLabel() }}</p>
                            </div>
                        </div>
                    @empty
                        <x-ui.empty-state icon="fa-boxes-stacked" title="Everything is on the shelf"
                                          message="No equipment is out with a borrower right now." />
                    @endforelse
                </x-ui.panel>
            </section>

            <section aria-labelledby="activity-heading">
                <x-ui.panel>
                    <div class="px-5 py-4 border-b border-neutral-200">
                        <h2 id="activity-heading" class="text-lg font-semibold text-neutral-900">Activity</h2>
                        <p class="mt-0.5 text-sm text-neutral-600">Loans, returns and requests across the department</p>
                    </div>

                    @forelse($activity as $event)
                        <div class="flex items-center gap-3 px-5 py-3 border-b border-neutral-100 last:border-b-0">
                            <span class="w-20 shrink-0 rounded px-2 py-1 text-center text-xs font-semibold uppercase tracking-wider {{ $tones[$event['tone']]['chip'] }}">
                                {{ $event['tag'] }}
                            </span>
                            <p class="flex-1 min-w-0 text-sm text-neutral-700 text-pretty">
                                <span class="font-semibold text-neutral-900">{{ $event['item'] }}</span>
                                {{ $event['verb'] }}
                                <span class="font-medium text-neutral-900">{{ $event['person'] }}</span>
                            </p>
                            <span class="text-sm shrink-0 text-neutral-600">{{ $event['at']?->diffForHumans(null, true) ?? '—' }}</span>
                        </div>
                    @empty
                        <x-ui.empty-state icon="fa-clock-rotate-left" title="Nothing has happened yet"
                                          message="Loans, returns and requests will appear here as they are recorded." />
                    @endforelse
                </x-ui.panel>
            </section>
        </div>
    </main>
</div>
@endsection
