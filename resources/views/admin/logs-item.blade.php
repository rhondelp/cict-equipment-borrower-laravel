@extends('components.default')
@section('title', $equipment->equipment_name.' history - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

@php
    $late = $logs->filter(fn ($log) => $log->daysLate() > 0);
    $damageRate = $logs->isEmpty() ? 0 : (int) round(($incidents->count() / $logs->count()) * 100);
@endphp

<div class="min-h-[100dvh] page-bg md:ml-64">

    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>

    <x-ui.page-header eyebrow="Item history" :title="$equipment->equipment_name">
        {{ $logs->count() }} {{ str('return')->plural($logs->count()) }} on record
    </x-ui.page-header>

    <main id="main-content" class="p-4 mx-auto space-y-5 sm:p-6 max-w-content">

        <p class="text-sm">
            <a href="{{ route('admin.logs') }}" class="font-semibold text-primary-700 hover:text-primary-800">
                &larr; All return logs
            </a>
        </p>

        @if($logs->isEmpty())
            <x-ui.panel>
                <x-ui.empty-state icon="fa-book" title="Nothing returned yet"
                                  message="Once a loan of this item is checked in, every return shows up here." />
            </x-ui.panel>
        @else
            {{-- The point of this page in one line: a single damaged return is
                 an accident, the fourth is a fact about the equipment, and that
                 is invisible on a date-ordered list. --}}
            <x-ui.stat-strip :stats="[
                ['label' => 'Returned damaged or lost', 'value' => $incidents->count(), 'unit' => 'of '.$logs->count(), 'sub' => $damageRate.'% of every return of this item', 'tone' => $incidents->isEmpty() ? 'neutral' : 'warning'],
                ['label' => 'Still unresolved', 'value' => $unresolved, 'unit' => '', 'sub' => $unresolved === 0 ? 'Every incident has an outcome' : 'No outcome recorded yet', 'tone' => $unresolved === 0 ? 'neutral' : 'danger'],
                ['label' => 'Returned late', 'value' => $late->count(), 'unit' => 'of '.$logs->count(), 'sub' => 'Past the due date on the loan'],
                ['label' => 'Units owned', 'value' => $equipment->quantity, 'unit' => '', 'sub' => $equipment->isRetired() ? 'Retired — no longer lendable' : $equipment->available_quantity.' on the shelf now'],
            ]" />

            <x-ui.panel>
                <div class="hidden gap-4 px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-neutral-600 bg-neutral-50 border-b border-neutral-200 md:grid md:grid-cols-[minmax(0,1.4fr)_minmax(0,1.2fr)_minmax(0,2fr)]">
                    <div>Condition</div>
                    <div>Returned</div>
                    <div>Who, and what was recorded</div>
                </div>

                <div class="divide-y divide-neutral-200">
                    @foreach($logs as $log)
                        <div class="grid gap-2 px-4 py-4 sm:px-5 md:gap-4 md:items-start md:grid-cols-[minmax(0,1.4fr)_minmax(0,1.2fr)_minmax(0,2fr)]">
                            <div class="min-w-0">
                                <x-ui.status :label="$log->condition" :tone="$log->conditionTone()" />
                                @if($log->isIncident())
                                    <p class="mt-1 text-sm {{ $log->isResolved() ? 'text-success-700' : 'font-semibold text-danger-700' }}">
                                        {{ $log->resolutionLine() }}
                                    </p>
                                @endif
                            </div>

                            <p class="text-sm tabular-nums {{ $log->daysLate() > 0 ? 'font-semibold text-danger-700' : 'text-neutral-700' }}">
                                {{ $log->timingLine() }}
                            </p>

                            <div class="min-w-0 text-sm">
                                <p class="text-neutral-700">
                                    <span class="text-neutral-500">Returned by</span> {{ $log->borrower->name ?? 'Deleted user' }} ·
                                    <span class="text-neutral-500">received by</span>
                                    <span class="{{ $log->receiver ? '' : 'font-semibold text-warning-700' }}">{{ $log->receiver->name ?? 'not recorded' }}</span>
                                </p>
                                @if($log->remarks)
                                    <p class="mt-1 text-neutral-700 text-pretty">{{ $log->remarks }}</p>
                                @endif
                                @if($log->isResolved())
                                    <p class="mt-1 text-neutral-600 text-pretty">{{ $log->resolution }}</p>
                                @endif
                                @foreach($log->notes as $note)
                                    <p class="mt-1.5 border-l-2 border-neutral-300 pl-2.5 text-neutral-600 text-pretty">
                                        {{ $note->body }}
                                        <span class="text-neutral-500">— {{ $note->author->name ?? 'a deleted account' }}, {{ $note->created_at?->format('M j, Y') }}</span>
                                    </p>
                                @endforeach
                                <p class="mt-2">
                                    <a href="{{ route('admin.transaction') }}#loan-{{ $log->borrow_transaction_id }}"
                                       class="font-semibold text-primary-700 hover:text-primary-800">The loan &rarr;</a>
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p class="px-5 py-3 text-sm border-t text-neutral-600 border-neutral-200">
                    Entries cannot be edited or deleted — corrections are appended.
                </p>
            </x-ui.panel>
        @endif
    </main>
</div>
@endsection
