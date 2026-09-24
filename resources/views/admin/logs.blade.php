@extends('components.default')
@section('title', 'Return Logs - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

@php
    $late = $logs->filter(fn ($log) => $log->daysLate() > 0);
    $incidents = $logs->filter(fn ($log) => $log->isIncident());
    $units = $logs->sum(fn ($log) => $log->borrowTransaction->quantity ?? 0);
    $longest = $late->max(fn ($log) => $log->daysLate()) ?? 0;
@endphp

<div class="min-h-[100dvh] page-bg md:ml-64">

    {{-- Essential for keyboard users: the sidebar is six links deep, so without
         this every page begins with six tab stops before the content. --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>

    <x-ui.page-header eyebrow="History" title="Return logs">
        {{ $logs->count() }} {{ str('return')->plural($logs->count()) }} recorded · {{ $followUp->count() }} needing follow-up
    </x-ui.page-header>

    <main id="main-content" class="p-4 mx-auto space-y-5 sm:p-6 max-w-content">

        @if($logs->isNotEmpty())
            <x-ui.stat-strip :stats="[
                ['label' => 'Needs follow-up', 'value' => $followUp->count(), 'unit' => '', 'sub' => $followUp->isEmpty() ? 'Every incident has an outcome recorded' : 'Damaged or lost, nothing recorded yet', 'tone' => $followUp->isEmpty() ? 'neutral' : 'danger', 'chip' => 'followup', 'list' => '#return-log-list'],
                ['label' => 'Incomplete records', 'value' => $incomplete->count(), 'unit' => '', 'sub' => $incomplete->isEmpty() ? 'Every return names who received it' : 'No receiving staff recorded', 'tone' => $incomplete->isEmpty() ? 'neutral' : 'warning', 'chip' => 'incomplete', 'list' => '#return-log-list'],
                ['label' => 'Returned late', 'value' => $late->count(), 'unit' => '', 'sub' => $longest > 0 ? 'Longest: '.$longest.' '.str('day')->plural($longest) : 'Everything came back on time'],
                ['label' => 'Returns logged', 'value' => $logs->count(), 'unit' => $units.' '.str('unit')->plural($units), 'sub' => 'Every check-in writes one'],
            ]" />
        @endif

        {{-- What still needs doing, above the archive. An audit log read
             newest-first makes a damaged return from March look exactly like
             one from this morning. --}}
        @if($followUp->isNotEmpty())
            <section aria-labelledby="followup-heading" class="space-y-3">
                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <h2 id="followup-heading" class="text-lg font-semibold text-neutral-900">Needs follow-up</h2>
                    <p class="text-sm text-neutral-600">
                        {{ $followUp->count() }} {{ str('item')->plural($followUp->count()) }} came back damaged or lost with no outcome recorded
                    </p>
                </div>

                @foreach($followUp as $log)
                    @php
                        $item = $log->equipment->equipment_name ?? 'Deleted equipment';
                        $quantity = $log->borrowTransaction->quantity ?? 1;
                    @endphp
                    <article class="bg-white border rounded-xl border-neutral-200 border-l-[3px] {{ $log->conditionTone() === 'danger' ? 'border-l-danger-500' : 'border-l-warning-500' }}">
                        <div class="flex flex-wrap items-start justify-between gap-4 px-5 pt-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.status :label="$log->condition" :tone="$log->conditionTone()" />
                                    <h3 class="text-base font-semibold text-neutral-900">
                                        {{ $item }}@if($quantity > 1) <span class="text-neutral-600">×{{ $quantity }}</span>@endif
                                    </h3>
                                </div>
                                <p class="mt-1 text-sm text-neutral-600">
                                    Returned by {{ $log->borrower->name ?? 'a deleted user' }} · {{ $log->timingLine() }} ·
                                    received by
                                    @if($log->receiver)
                                        {{ $log->receiver->name }}
                                    @else
                                        <span class="font-semibold text-warning-700">nobody recorded</span>
                                    @endif
                                </p>
                            </div>

                            <button type="button"
                                    class="inline-flex min-h-[40px] shrink-0 items-center gap-2 rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700"
                                    data-resolve-trigger
                                    data-id="{{ $log->id }}"
                                    data-summary="{{ $item.' · '.$log->condition.' · returned by '.($log->borrower->name ?? 'a deleted user') }}">
                                <i class="text-sm fas fa-clipboard-check" aria-hidden="true"></i> Record outcome
                            </button>
                        </div>

                        @if($log->remarks)
                            <p class="px-5 pt-3 text-sm text-neutral-700 text-pretty">{{ $log->remarks }}</p>
                        @endif

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3 mt-3 text-sm border-t border-neutral-100">
                            <a href="{{ route('admin.transaction') }}#loan-{{ $log->borrow_transaction_id }}"
                               class="font-semibold text-primary-700 hover:text-primary-800">
                                The loan &rarr;
                            </a>
                            @if($log->equipment)
                                <a href="{{ route('admin.logs.item', $log->equipment->id) }}"
                                   class="font-semibold text-primary-700 hover:text-primary-800">
                                    This item&rsquo;s history &rarr;
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </section>
        @endif

        <x-ui.panel id="return-log-list" data-list data-active-chip="all">
            @if($logs->isEmpty())
                <x-ui.empty-state icon="fa-book" title="No returns logged yet"
                                  message="Checking a loan back in on the Loans page writes its entry here." />
            @else
                <div class="flex flex-wrap items-center gap-3 px-4 py-3 border-b sm:px-5 border-neutral-200">
                    <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter by condition">
                        @php
                            $chips = ['all' => 'All '.$logs->count()];
                            foreach (\App\Models\ReturnLog::CONDITIONS as $condition) {
                                $count = $logs->where('condition', $condition)->count();
                                if ($count > 0) {
                                    $chips[\Illuminate\Support\Str::slug($condition)] = $condition.' '.$count;
                                }
                            }
                            if ($followUp->isNotEmpty()) {
                                $chips['followup'] = 'Needs follow-up '.$followUp->count();
                            }
                            if ($incomplete->isNotEmpty()) {
                                $chips['incomplete'] = 'Incomplete '.$incomplete->count();
                            }
                        @endphp
                        @foreach($chips as $value => $label)
                            <button type="button" data-list-chip="{{ $value }}"
                                    aria-pressed="{{ $value === 'all' ? 'true' : 'false' }}"
                                    class="inline-flex min-h-[36px] items-center rounded-full border px-3.5 py-1.5 text-sm font-semibold transition
                                           {{ $value === 'all' ? 'border-primary-300 bg-primary-50 text-primary-700' : 'border-neutral-300 bg-white text-neutral-700' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-sm text-neutral-600">
                        <label for="log-from" class="shrink-0">Returned between</label>
                        <input type="date" id="log-from" data-list-from aria-label="Returned on or after"
                               class="min-h-[40px] rounded-md border border-neutral-300 px-2.5 py-2 text-sm text-neutral-800 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                        <span aria-hidden="true">&ndash;</span>
                        <input type="date" id="log-to" data-list-to aria-label="Returned on or before"
                               class="min-h-[40px] rounded-md border border-neutral-300 px-2.5 py-2 text-sm text-neutral-800 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                        <button type="button" data-list-range-clear
                                class="min-h-[40px] rounded-md px-2.5 py-2 text-sm font-semibold text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900">
                            Clear
                        </button>
                    </div>

                    <label class="relative flex-1 min-w-[12rem] max-w-xs">
                        <span class="sr-only">Search return logs</span>
                        <i class="absolute text-sm -translate-y-1/2 pointer-events-none fas fa-search left-4 top-1/2 text-neutral-500" aria-hidden="true"></i>
                        <input type="search" data-list-search autocomplete="off" placeholder="Search borrower or item"
                               class="w-full min-h-[40px] rounded-md border border-neutral-300 py-2 pl-10 pr-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </label>
                </div>

                {{-- Condition leads. This log is read when something is damaged
                     or disputed, so the thing being looked for comes first. --}}
                <div class="hidden gap-4 px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-neutral-600 bg-neutral-50 border-b border-neutral-200 md:grid md:grid-cols-[minmax(0,1.6fr)_minmax(0,1.8fr)_minmax(0,1.4fr)_minmax(0,1.2fr)]">
                    <div>Condition</div>
                    <div>Returned item</div>
                    <div>When</div>
                    <div>People</div>
                </div>

                <div data-list-rows class="divide-y divide-neutral-200">
                    @foreach ($logs as $log)
                        @php
                            $isLate = $log->daysLate() > 0;
                            $quantity = $log->borrowTransaction->quantity ?? 1;
                            $item = $log->equipment->equipment_name ?? 'Deleted equipment';
                            $chipKeys = collect(['all', \Illuminate\Support\Str::slug($log->condition)]);
                            if ($isLate) { $chipKeys->push('late'); }
                            if ($log->needsFollowUp()) { $chipKeys->push('followup'); }
                            if (! $log->receiver) { $chipKeys->push('incomplete'); }
                        @endphp
                        <div data-list-row data-chip="{{ $chipKeys->implode(' ') }}"
                             data-date="{{ $log->return_date?->toDateString() }}"
                             data-search="{{ strtolower(($log->borrower->name ?? '').' '.$item.' '.$log->remarks.' '.($log->receiver->name ?? '')) }}"
                             class="grid gap-2 px-4 py-4 sm:px-5 md:gap-4 md:items-start hover:bg-neutral-50
                                    md:grid-cols-[minmax(0,1.6fr)_minmax(0,1.8fr)_minmax(0,1.4fr)_minmax(0,1.2fr)]">

                            {{-- Colour carries the severity, and the note is
                                 shown in full: a truncated damage note is the
                                 one piece of text on this page nobody can
                                 afford to lose the end of. --}}
                            <div class="min-w-0">
                                <x-ui.status :label="$log->condition" :tone="$log->conditionTone()" />
                                @if($log->isIncident())
                                    <p class="mt-1 text-sm {{ $log->isResolved() ? 'text-success-700' : 'font-semibold text-danger-700' }}">
                                        {{ $log->resolutionLine() }}
                                    </p>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <p class="text-base font-semibold text-neutral-900">
                                    {{ $item }}@if($quantity > 1) <span class="text-neutral-600">×{{ $quantity }}</span>@endif
                                </p>
                                @if($log->remarks)
                                    <p class="mt-1 text-sm text-neutral-700 text-pretty">{{ $log->remarks }}</p>
                                @endif
                                @if($log->isResolved())
                                    <p class="mt-1 text-sm text-neutral-600 text-pretty">{{ $log->resolution }}</p>
                                @endif

                                @foreach($log->notes as $note)
                                    <p class="mt-1.5 border-l-2 border-neutral-300 pl-2.5 text-sm text-neutral-600 text-pretty" data-log-note>
                                        {{ $note->body }}
                                        <span class="text-neutral-500">
                                            — {{ $note->author->name ?? 'a deleted account' }}, {{ $note->created_at?->format('M j, Y') }}
                                        </span>
                                    </p>
                                @endforeach

                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-sm">
                                    <a href="{{ route('admin.transaction') }}#loan-{{ $log->borrow_transaction_id }}"
                                       class="font-semibold text-primary-700 hover:text-primary-800">The loan &rarr;</a>
                                    @if($log->equipment)
                                        <a href="{{ route('admin.logs.item', $log->equipment->id) }}"
                                           class="font-semibold text-primary-700 hover:text-primary-800">Item history &rarr;</a>
                                    @endif
                                    <button type="button" data-note-trigger
                                            data-id="{{ $log->id }}"
                                            data-summary="{{ $item.' · returned '.($log->return_date?->format('M j, Y') ?? '') }}"
                                            class="font-semibold text-neutral-600 hover:text-neutral-900">
                                        Add a correction
                                    </button>
                                </div>
                            </div>

                            <p class="text-sm {{ $isLate ? 'font-semibold text-danger-700' : 'text-neutral-700' }} tabular-nums">
                                {{ $log->timingLine() }}
                            </p>

                            {{-- Both names, every row. A return with no receiving
                                 staff member is an incomplete record, and the
                                 old collapsed "all received by X" line could
                                 not say which rows were missing one. --}}
                            <div class="min-w-0 text-sm">
                                <p class="text-neutral-700 truncate">
                                    <span class="text-neutral-500">Returned by</span> {{ $log->borrower->name ?? 'Deleted user' }}
                                </p>
                                <p class="truncate {{ $log->receiver ? 'text-neutral-700' : 'font-semibold text-warning-700' }}">
                                    <span class="font-normal text-neutral-500">Received by</span>
                                    {{ $log->receiver->name ?? 'not recorded' }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p data-list-empty hidden class="px-5 py-12 text-base text-center text-neutral-600">
                    No returns match that filter.
                </p>

                <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm border-t text-neutral-600 border-neutral-200">
                    <span data-list-count data-total="{{ $logs->count() }}" data-noun="returns"></span>
                    <span>Entries cannot be edited or deleted — corrections are appended.</span>
                </div>
            @endif
        </x-ui.panel>
    </main>
</div>

@include('components.admin.logs.resolve-modal')
@include('components.admin.logs.note-modal')
@endsection
