@extends('components.default')
@section('title', 'Return Logs - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

@php
    $late = $logs->filter(fn ($log) => $log->daysLate() > 0);
    $issues = $logs->reject(fn ($log) => $log->condition === 'Good');
    $units = $logs->sum(fn ($log) => $log->borrowTransaction->quantity ?? 0);
    $longest = $late->max(fn ($log) => $log->daysLate()) ?? 0;

    // Rule of thumb applied literally: a column whose value is the same on every
    // row is not a column. In a one-admin department "Received by" is exactly
    // that, so it collapses into a single line above the table and the width
    // goes to something that varies.
    $receivers = $logs->map(fn ($log) => $log->receiver?->name)->unique()->filter()->values();
    $oneReceiver = $logs->isNotEmpty() && $receivers->count() <= 1;
@endphp

<div class="min-h-[100dvh] page-bg md:ml-64">

    {{-- Essential for keyboard users: the sidebar is six links deep, so without
         this every page begins with six tab stops before the content. --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>

    <x-ui.page-header eyebrow="History" title="Return logs">
        {{ $logs->count() }} {{ str('return')->plural($logs->count()) }} recorded · {{ $late->count() }} late
    </x-ui.page-header>

    <main id="main-content" class="p-4 mx-auto space-y-5 sm:p-6 max-w-content">

        @if($logs->isNotEmpty())
            <x-ui.stat-strip :stats="[
                ['label' => 'Returns logged', 'value' => $logs->count(), 'unit' => $units.' '.str('unit')->plural($units), 'sub' => 'Every check-in writes one'],
                ['label' => 'Returned late', 'value' => $late->count(), 'unit' => '', 'sub' => $longest > 0 ? 'Longest: '.$longest.' '.str('day')->plural($longest) : 'Everything came back on time', 'tone' => $late->isEmpty() ? 'neutral' : 'danger'],
                ['label' => 'Condition issues', 'value' => $issues->count(), 'unit' => '', 'sub' => $issues->isEmpty() ? 'All returned in good order' : $issues->where('condition', 'Damaged')->count().' damaged · '.$issues->where('condition', 'Missing parts')->count().' missing parts', 'tone' => $issues->isEmpty() ? 'neutral' : 'warning'],
                ['label' => 'On time', 'value' => $logs->count() - $late->count(), 'unit' => 'of '.$logs->count(), 'sub' => 'Returned by the due date', 'tone' => 'success'],
            ]" />
        @endif

        <x-ui.panel data-list data-active-chip="all">
            @if($logs->isEmpty())
                <x-ui.empty-state icon="fa-book" title="No returns logged yet"
                                  message="Checking a loan back in on the Loans page writes its entry here." />
            @else
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b sm:px-5 border-neutral-200">
                    <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter returns">
                        @foreach(['all' => 'All '.$logs->count(), 'late' => 'Late '.$late->count(), 'issues' => 'With issues '.$issues->count()] as $value => $label)
                            <button type="button" data-list-chip="{{ $value }}"
                                    aria-pressed="{{ $value === 'all' ? 'true' : 'false' }}"
                                    class="inline-flex min-h-[36px] items-center rounded-full border px-3.5 py-1.5 text-sm font-semibold transition
                                           {{ $value === 'all' ? 'border-primary-300 bg-primary-50 text-primary-700' : 'border-neutral-300 bg-white text-neutral-700' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    <label class="relative flex-1 min-w-[12rem] max-w-xs">
                        <span class="sr-only">Search return logs</span>
                        <i class="absolute text-sm -translate-y-1/2 pointer-events-none fas fa-search left-4 top-1/2 text-neutral-500" aria-hidden="true"></i>
                        <input type="search" data-list-search autocomplete="off" placeholder="Search person or item"
                               class="w-full min-h-[40px] rounded-md border border-neutral-300 py-2 pl-10 pr-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </label>
                </div>

                @if($oneReceiver)
                    <p class="px-5 py-2 text-sm border-b text-neutral-600 border-neutral-200 bg-neutral-50">
                        All returns received by <span class="font-semibold text-neutral-800">{{ $receivers->first() ?? 'the equipment office' }}</span>.
                    </p>
                @endif

                <div class="hidden gap-4 px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-neutral-600 bg-neutral-50 border-b border-neutral-200 md:grid {{ $oneReceiver ? 'md:grid-cols-[minmax(0,2fr)_minmax(0,1.3fr)_minmax(0,1.4fr)]' : 'md:grid-cols-[minmax(0,2fr)_minmax(0,1.3fr)_minmax(0,1.4fr)_minmax(0,1fr)]' }}">
                    <div>Returned item</div>
                    <div>When</div>
                    <div>Condition</div>
                    @unless($oneReceiver)<div>Received by</div>@endunless
                </div>

                <div class="divide-y divide-neutral-200">
                    @foreach ($logs as $log)
                        @php
                            $isLate = $log->daysLate() > 0;
                            $quantity = $log->borrowTransaction->quantity ?? 1;
                            $item = $log->equipment->equipment_name ?? 'Deleted equipment';
                            $chipKeys = collect(['all']);
                            if ($isLate) { $chipKeys->push('late'); }
                            if ($log->condition !== 'Good') { $chipKeys->push('issues'); }
                        @endphp
                        <div data-list-row data-chip="{{ $chipKeys->implode(' ') }}"
                             data-search="{{ strtolower(($log->borrower->name ?? '').' '.$item.' '.$log->remarks) }}"
                             class="grid gap-2 px-4 py-4 sm:px-5 md:gap-4 md:items-start hover:bg-neutral-50
                                    {{ $oneReceiver ? 'md:grid-cols-[minmax(0,2fr)_minmax(0,1.3fr)_minmax(0,1.4fr)]' : 'md:grid-cols-[minmax(0,2fr)_minmax(0,1.3fr)_minmax(0,1.4fr)_minmax(0,1fr)]' }}">

                            <div class="min-w-0">
                                <p class="text-base font-semibold text-neutral-900">
                                    {{ $item }}@if($quantity > 1) <span class="text-neutral-600">×{{ $quantity }}</span>@endif
                                </p>
                                <p class="text-sm truncate text-neutral-600">{{ $log->borrower->name ?? 'Deleted user' }}</p>
                            </div>

                            {{-- "Sep 18 · 4 days late" against the loan's own due
                                 date, rather than a bare return date that says
                                 nothing about whether it was on time. --}}
                            <p class="text-sm {{ $isLate ? 'font-semibold text-danger-700' : 'text-neutral-700' }}">
                                {{ $log->timingLine() }}
                            </p>

                            <div class="min-w-0">
                                <x-ui.status :label="$log->condition" :tone="$log->conditionTone()" />
                                @if($log->remarks)
                                    <p class="mt-1 text-sm text-neutral-600 text-pretty">{{ $log->remarks }}</p>
                                @endif
                            </div>

                            @unless($oneReceiver)
                                <p class="text-sm truncate text-neutral-700">{{ $log->receiver->name ?? 'Not recorded' }}</p>
                            @endunless
                        </div>
                    @endforeach
                </div>

                <p data-list-empty hidden class="px-5 py-12 text-base text-center text-neutral-600">
                    No returns match that filter.
                </p>

                <div class="px-5 py-3 text-sm border-t text-neutral-600 border-neutral-200">
                    <span data-list-count data-total="{{ $logs->count() }}" data-noun="returns"></span>
                </div>
            @endif
        </x-ui.panel>
    </main>
</div>
@endsection
