@extends('components.default')
@section('title', 'Loans - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

@php
    $live = $transactions->reject(fn ($tx) => $tx->isVoided());
    $open = $live->filter(fn ($tx) => $tx->isOut());
    $overdue = $open->filter(fn ($tx) => $tx->isOverdue());
    $returned = $live->filter(fn ($tx) => $tx->isReturned());
    $voided = $transactions->filter(fn ($tx) => $tx->isVoided());
    $unitsOut = $open->sum('quantity');
    $dueToday = $open->filter(fn ($tx) => $tx->daysUntilDue() === 0)->count();
@endphp

<div class="min-h-[100dvh] page-bg md:ml-64">

    {{-- Essential for keyboard users: the sidebar is six links deep, so without
         this every page begins with six tab stops before the content. --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>

    <x-ui.page-header eyebrow="Circulation" title="Loans">
        {{ $unitsOut }} {{ str('unit')->plural($unitsOut) }} out · {{ $overdue->count() }} overdue
        <x-slot:actions>
            <button type="button" data-modal-open="new-loan-modal"
                    class="inline-flex items-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold text-white rounded-md bg-primary-600 hover:bg-primary-700">
                <i class="text-base fas fa-plus" aria-hidden="true"></i> New loan
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <main id="main-content" class="p-4 mx-auto space-y-5 sm:p-6 max-w-content">

        @if($transactions->isNotEmpty())
            <x-ui.stat-strip :stats="[
                ['label' => 'Units out on loan', 'value' => $unitsOut, 'unit' => 'across '.$open->count().' '.str('loan')->plural($open->count()), 'sub' => $open->isEmpty() ? 'Everything is on the shelf' : 'Tracked until each comes back'],
                ['label' => 'Overdue', 'value' => $overdue->count(), 'unit' => '', 'sub' => $overdue->isEmpty() ? 'Nothing past its due date' : 'Longest: '.$overdue->max(fn ($tx) => $tx->daysLate()).' days late', 'tone' => $overdue->isEmpty() ? 'neutral' : 'danger'],
                ['label' => 'Due back today', 'value' => $dueToday, 'unit' => '', 'sub' => $dueToday > 0 ? 'Reminders go out at 08:00' : 'Nothing due today', 'tone' => $dueToday > 0 ? 'warning' : 'neutral'],
                ['label' => 'Returned', 'value' => $returned->count(), 'unit' => str('loan')->plural($returned->count()), 'sub' => 'Logged in Return Logs', 'tone' => 'success', 'url' => route('admin.logs')],
            ]" />
        @endif

        @php
            // Opening on "Active" is right while anything is out, and wrong the
            // moment nothing is: a first screen that reads "no loans match that
            // filter" over a full archive is a filter fighting its own page.
            $defaultChip = $open->isNotEmpty() ? 'active' : 'all';
        @endphp
        <x-ui.panel data-list data-active-chip="{{ $defaultChip }}">
            @if($transactions->isEmpty())
                <x-ui.empty-state icon="fa-right-left" title="No loans yet"
                                  message="Approving a request creates one automatically, or record a handover by hand.">
                    <x-slot:action>
                        <button type="button" class="btn-primary" data-modal-open="new-loan-modal">
                            <i class="text-base fas fa-plus" aria-hidden="true"></i> New loan
                        </button>
                    </x-slot:action>
                </x-ui.empty-state>
            @else
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b sm:px-5 border-neutral-200">
                    <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter loans">
                        @php
                            $chips = [
                                'active' => 'Active '.$open->count(),
                                'overdue' => 'Overdue '.$overdue->count(),
                                'returned' => 'Returned '.$returned->count(),
                                'all' => 'All '.$transactions->count(),
                            ];
                            if ($voided->isNotEmpty()) {
                                $chips['void'] = 'Void '.$voided->count();
                            }
                        @endphp
                        @foreach($chips as $value => $label)
                            <button type="button" data-list-chip="{{ $value }}"
                                    aria-pressed="{{ $value === $defaultChip ? 'true' : 'false' }}"
                                    class="inline-flex min-h-[36px] items-center rounded-full border px-3.5 py-1.5 text-sm font-semibold transition
                                           {{ $value === $defaultChip ? 'border-primary-300 bg-primary-50 text-primary-700' : 'border-neutral-300 bg-white text-neutral-700' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-2 text-sm text-neutral-600">
                        <label for="loan-sort" class="shrink-0">Sort</label>
                        <select id="loan-sort" data-list-sort
                                class="min-h-[40px] rounded-md border border-neutral-300 bg-white px-2.5 py-2 text-sm text-neutral-800 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                            <option value="urgency">Most urgent first</option>
                            <option value="due" data-type="number">Due date</option>
                            <option value="borrowed" data-type="number" data-dir="desc">Recently borrowed</option>
                            <option value="person">Borrower A&ndash;Z</option>
                        </select>
                    </div>

                    <label class="relative flex-1 min-w-[12rem] max-w-xs">
                        <span class="sr-only">Search loans</span>
                        <i class="absolute text-sm -translate-y-1/2 pointer-events-none fas fa-search left-4 top-1/2 text-neutral-500" aria-hidden="true"></i>
                        <input type="search" data-list-search autocomplete="off" placeholder="Search person or item"
                               class="w-full min-h-[40px] rounded-md border border-neutral-300 py-2 pl-10 pr-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </label>
                </div>

                {{-- Four columns where there were ten. Purpose, remarks and the
                     class schedule were the same 14rem of truncated text on
                     every row; they now live in the row's detail panel, which is
                     where anyone who actually wants them goes looking. --}}
                <div class="hidden gap-4 px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-neutral-600 bg-neutral-50 border-b border-neutral-200 md:grid md:grid-cols-[minmax(0,2fr)_minmax(0,1.3fr)_8rem_10rem]">
                    <div>Loan</div>
                    <div>Dates</div>
                    <div>Status</div>
                    <div class="text-right">Actions</div>
                </div>

                <div data-list-rows class="divide-y divide-neutral-200">
                    @foreach ($transactions as $tx)
                        @php
                            $status = $tx->derivedStatus();
                            $rank = $tx->isVoided() ? 4
                                : ($tx->isReturned() ? 3
                                : ($tx->isOverdue() ? 0
                                : (($tx->daysUntilDue() !== null && $tx->daysUntilDue() <= 1) ? 1 : 2)));
                            $lastReminder = $tx->reminders->first();
                            $chipKeys = match ($status) {
                                'Overdue' => 'all active overdue',
                                'Out' => 'all active',
                                'Returned' => 'all returned',
                                default => 'all void',
                            };
                            $isOpen = $tx->isOut();
                            $log = $tx->returnLog;
                            $blocked = $isOpen
                                ? "Can't delete — the loan is still open"
                                : ($log ? "Can't delete — it is part of the return history" : '');
                        @endphp
                        <div data-list-row id="loan-{{ $tx->id }}" data-chip="{{ $chipKeys }}"
                             data-search="{{ strtolower(($tx->user->name ?? '').' '.($tx->equipment->equipment_name ?? '').' '.$tx->purpose) }}"
                             data-sort-urgency="{{ $rank }}{{ str_pad((string) ($tx->return_date?->timestamp ?? 9999999999), 10, '0', STR_PAD_LEFT) }}"
                             data-sort-due="{{ $tx->return_date?->timestamp ?? 0 }}"
                             data-sort-borrowed="{{ $tx->borrow_date?->timestamp ?? 0 }}"
                             data-sort-person="{{ $tx->user->name ?? 'zzz' }}"
                             class="scroll-mt-24 {{ $tx->isOverdue() ? 'bg-danger-50/40' : '' }}">

                            <div class="grid gap-3 px-4 py-3 sm:px-5 md:grid-cols-[minmax(0,2fr)_minmax(0,1.3fr)_8rem_10rem] md:items-center md:gap-4">
                                <div class="flex items-center min-w-0 gap-2">
                                    <button type="button" data-loan-toggle="loan-details-{{ $tx->id }}"
                                            aria-expanded="false" aria-controls="loan-details-{{ $tx->id }}"
                                            class="grid w-8 h-8 rounded-md shrink-0 place-items-center text-neutral-500 hover:bg-neutral-100 hover:text-neutral-800">
                                        <i class="text-xs fas fa-chevron-right" aria-hidden="true"></i>
                                        <span class="sr-only">Show details</span>
                                    </button>
                                    <div class="min-w-0">
                                        <p class="text-base font-semibold truncate text-neutral-900">
                                            {{ $tx->equipment->equipment_name ?? 'Deleted equipment' }}@if($tx->quantity > 1) <span class="text-neutral-600">×{{ $tx->quantity }}</span>@endif
                                        </p>
                                        <p class="text-sm truncate text-neutral-600">{{ $tx->user->name ?? 'Deleted user' }}</p>
                                    </div>
                                </div>

                                {{-- "Sep 10 → Sep 17 · 3 days late". Never a raw
                                     ISO date, and never a bare due date with no
                                     sense of whether it has passed. --}}
                                <div class="min-w-0">
                                    <p class="text-sm truncate text-neutral-700 tabular-nums">{{ $tx->dateRangeLabel() }}</p>
                                    <p class="text-sm truncate {{ $tx->isOverdue() ? 'font-semibold text-danger-700' : 'text-neutral-600' }}">
                                        {{ $tx->timingLabel() }}
                                    </p>
                                    {{-- Whether this one has been chased, and when.
                                         Without it an admin cannot tell a first
                                         nudge from a fourth. --}}
                                    @if($lastReminder)
                                        <p class="text-xs truncate text-neutral-500" data-last-reminder>
                                            <i class="fa-regular fa-paper-plane mr-1 text-[10px]" aria-hidden="true"></i>
                                            Reminded {{ $lastReminder->send_date?->format('M j') }}@if($tx->reminders->count() > 1) · {{ $tx->reminders->count() }} sent @endif
                                        </p>
                                    @elseif($tx->isOverdue())
                                        <p class="text-xs truncate text-warning-700" data-last-reminder>
                                            <i class="fa-regular fa-paper-plane mr-1 text-[10px]" aria-hidden="true"></i>
                                            Not chased yet
                                        </p>
                                    @endif
                                </div>

                                <div>
                                    <x-ui.status :label="$status" :tone="$tx->statusTone()" />
                                </div>

                                <div class="flex items-center gap-2 md:justify-end">
                                    @if($isOpen)
                                        <button type="button"
                                                class="grid w-10 h-10 border rounded-md place-items-center border-success-200 bg-success-50 text-success-700 hover:bg-success-100"
                                                title="Check in" aria-label="Check in this loan"
                                                data-checkin-trigger
                                                data-id="{{ $tx->id }}"
                                                data-summary="{{ ($tx->equipment->equipment_name ?? 'Equipment').($tx->quantity > 1 ? ' ×'.$tx->quantity : '').' · '.($tx->user->name ?? 'Deleted user') }}"
                                                data-timing="{{ $tx->isOverdue() ? $tx->timingLabel().' — due '.($tx->return_date?->format('M j')) : 'Due '.($tx->return_date?->format('M j') ?? '—').' · on time' }}"
                                                data-late="{{ $tx->isOverdue() ? '1' : '0' }}">
                                            <i class="text-base fas fa-check" aria-hidden="true"></i>
                                        </button>
                                    @endif

                                    @if($tx->user)
                                        <button type="button"
                                                class="grid w-10 h-10 border rounded-md place-items-center border-neutral-300 bg-white text-neutral-700 hover:border-primary-300 hover:text-primary-700"
                                                title="Email borrower" aria-label="Email the borrower"
                                                data-email-trigger data-id="{{ $tx->id }}" data-email="{{ $tx->user->email }}"
                                                data-summary="{{ ($tx->equipment->equipment_name ?? 'Equipment').($tx->quantity > 1 ? ' ×'.$tx->quantity : '').' · '.($tx->user->name ?? 'Deleted user') }}"
                                                data-timing="{{ $tx->timingLabel() }}"
                                                data-reminders="{{ $tx->reminders->count() }}"
                                                data-last-reminded="{{ $lastReminder?->send_date?->format('M j') ?? '' }}">
                                            <i class="text-base fas fa-envelope" aria-hidden="true"></i>
                                        </button>
                                    @endif

                                    @if($isOpen)
                                        <button type="button"
                                                class="grid w-10 h-10 border rounded-md place-items-center border-neutral-300 bg-white text-neutral-700 hover:border-primary-300 hover:text-primary-700"
                                                title="Edit loan" aria-label="Edit this loan"
                                                data-loan-edit
                                                data-id="{{ $tx->id }}"
                                                data-user="{{ $tx->user_id }}"
                                                data-equipment="{{ $tx->equipment_id }}"
                                                data-borrow="{{ $tx->borrow_date?->toDateString() }}"
                                                data-return="{{ $tx->return_date?->toDateString() }}"
                                                data-quantity="{{ $tx->quantity }}"
                                                data-purpose="{{ $tx->purpose }}"
                                                data-remarks="{{ $tx->remarks }}"
                                                data-class="{{ $tx->class_schedule_id }}"
                                                data-summary="{{ ($tx->equipment->equipment_name ?? 'Equipment').' · '.($tx->user->name ?? 'Deleted user') }}">
                                            <i class="text-base fas fa-pen" aria-hidden="true"></i>
                                        </button>
                                    @endif

                                    @unless($tx->isVoided())
                                        <button type="button"
                                                class="grid w-10 h-10 border rounded-md place-items-center border-neutral-300 bg-white text-neutral-600 hover:border-danger-300 hover:bg-danger-50 hover:text-danger-700"
                                                title="Remove record" aria-label="Remove this loan record"
                                                data-remove-trigger data-dialog="remove-dialog"
                                                data-title="Remove loan #{{ $tx->id }}?"
                                                data-body="{{ $isOpen
                                                    ? 'This loan is still open — the equipment is with the borrower. Voiding puts its units back in stock and keeps the record visible, marked as an error.'
                                                    : 'Returned loans are the department\'s audit trail. Voiding keeps the record and marks it as an error; deleting erases it from the logs and the return history.' }}"
                                                data-fact-a="{{ $tx->user->name ?? 'Deleted user' }}"
                                                data-fact-b="{{ $tx->quantity }}" data-fact-b-alert="{{ $isOpen ? '1' : '0' }}"
                                                data-fact-c="{{ $status }}"
                                                data-blocked="{{ $blocked }}"
                                                data-delete-url="{{ route('admin.transaction.destroy', $tx->id) }}"
                                                data-safe-url="{{ route('admin.transaction.void', $tx->id) }}">
                                            <i class="text-base fas fa-trash" aria-hidden="true"></i>
                                        </button>
                                    @endunless
                                </div>
                            </div>

                            <div id="loan-details-{{ $tx->id }}" hidden
                                 class="grid gap-4 px-4 pb-4 sm:px-5 sm:pl-14 md:grid-cols-2 lg:grid-cols-4 bg-neutral-50/60">
                                <div>
                                    <p class="text-xs font-semibold tracking-wider uppercase text-neutral-600">Purpose</p>
                                    <p class="mt-1 text-sm text-neutral-800 text-pretty">{{ $tx->purpose ?: 'Not stated' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold tracking-wider uppercase text-neutral-600">Remarks</p>
                                    <p class="mt-1 text-sm text-pretty {{ $tx->remarks ? 'text-neutral-800' : 'text-neutral-500' }}">
                                        {{ $tx->remarks ?: 'None recorded' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold tracking-wider uppercase text-neutral-600">Class schedule</p>
                                    <p class="mt-1 text-sm {{ $tx->classSchedule ? 'text-neutral-800' : 'text-neutral-500' }}">
                                        @if($tx->classSchedule)
                                            {{ $tx->classSchedule->subject_code }} · {{ $tx->classSchedule->schedule_time }} ·
                                            {{ $tx->classSchedule->room }}
                                        @else
                                            Not tied to a class
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold tracking-wider uppercase text-neutral-600">
                                        {{ $tx->isVoided() ? 'Voided' : 'Returned' }}
                                    </p>
                                    <p class="mt-1 text-sm text-pretty {{ $tx->isVoided() || $log ? 'text-neutral-800' : 'text-neutral-500' }}">
                                        @if($tx->isVoided())
                                            {{ $tx->voided_at->format('M j') }} — {{ $tx->void_reason }}
                                        @elseif($log)
                                            {{ $log->timingLine() }} · {{ $log->condition }}
                                            @if($log->receiver) · received by {{ $log->receiver->name }} @endif
                                        @else
                                            Still out
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p data-list-empty hidden class="px-5 py-12 text-base text-center text-neutral-600">
                    No loans match that filter.
                </p>

                <div class="px-5 py-3 text-sm border-t text-neutral-600 border-neutral-200">
                    <span data-list-count data-total="{{ $transactions->count() }}" data-noun="loans"></span>
                </div>
            @endif
        </x-ui.panel>
    </main>
</div>

@include('components.admin.transaction.new-loan-modal')
@include('components.admin.transaction.edit-modal')
@include('components.admin.transaction.checkin-modal')
@include('components.admin.transaction.email-modal')

<x-ui.remove-dialog
    id="remove-dialog"
    safe-label="Void loan"
    safe-icon="fa-ban"
    cancel-label="Keep record"
    delete-label="Delete permanently"
    :facts="['Borrower', 'Units involved', 'Status']"
    ready-hint="The record stays in the log, marked as voided."
    blocked-hint="Say why this record is being voided — it stays in the log.">
    <x-slot:safeFields>
        <label for="void-reason" class="block text-base font-medium text-neutral-800">Reason for voiding</label>
        <textarea id="void-reason" name="void_reason" rows="2" minlength="5" maxlength="500" required
                  data-requires-reason data-autofocus placeholder="e.g. Recorded against the wrong borrower"
                  class="w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30"></textarea>
    </x-slot:safeFields>
</x-ui.remove-dialog>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ---- row detail panels ------------------------------------------- */
    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-loan-toggle]');
        if (!toggle) return;
        const panel = document.getElementById(toggle.getAttribute('data-loan-toggle'));
        if (!panel) return;
        const open = panel.hidden;
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.querySelector('i').className = open ? 'fas fa-chevron-down text-xs' : 'fas fa-chevron-right text-xs';
    });

    /* ---- new loan ---------------------------------------------------- */
    const loanForm = document.getElementById('new-loan-form');
    if (loanForm) {
        const list = document.getElementById('loan-equipment-list');
        const submit = loanForm.querySelector('[data-loan-submit]');
        const hint = loanForm.querySelector('[data-loan-hint]');
        const previewText = loanForm.querySelector('[data-loan-preview-text]');
        const previewDot = loanForm.querySelector('[data-loan-preview-dot]');
        const who = document.getElementById('loan-user');
        const from = document.getElementById('loan-borrow-date');
        const until = document.getElementById('loan-return-date');
        const purpose = document.getElementById('loan-purpose');

        const dayGap = function (a, b) {
            if (!a || !b) return NaN;
            return Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000);
        };

        // Advisory only — store() re-checks stock under a row lock and stays
        // the source of truth. What this buys is being told before the round
        // trip, next to the field that is wrong.
        function syncRow(checkbox) {
            const row = checkbox.closest('.equipment-option');
            const wrap = row?.querySelector('.equipment-qty-wrap');
            const qty = row?.querySelector('.equipment-qty');
            if (!wrap || !qty) return;
            wrap.classList.toggle('hidden', !checkbox.checked);
            qty.disabled = !checkbox.checked;
            if (checkbox.checked && !qty.value) qty.value = 1;
        }

        function validateQty(qty) {
            const max = parseInt(qty.getAttribute('max'), 10);
            const value = parseInt(qty.value, 10);
            const msg = qty.closest('.equipment-qty-wrap')?.querySelector('.equipment-qty-msg');
            const over = !isNaN(max) && !isNaN(value) && value > max;
            if (msg) {
                msg.textContent = over ? 'Only ' + max + ' available right now — lower the quantity.' : '';
                msg.classList.toggle('hidden', !over);
            }
            qty.classList.toggle('border-danger-300', over);
            return !over && !isNaN(value) && value >= 1;
        }

        function sync() {
            const picked = Array.from(list?.querySelectorAll('.equipment-checkbox:checked') || []);
            const quantities = picked.map(function (box) {
                return box.closest('.equipment-option').querySelector('.equipment-qty');
            });
            const quantitiesOk = quantities.every(validateQty);
            const units = quantities.reduce(function (sum, qty) {
                return sum + (parseInt(qty.value, 10) || 0);
            }, 0);
            const days = dayGap(from.value, until.value);
            const datesOk = !isNaN(days) && days >= 0;
            const ok = !!who.value && picked.length > 0 && quantitiesOk && datesOk && purpose.value.trim().length > 2;

            if (picked.length === 0) {
                previewText.textContent = 'Tick the items being handed over';
                previewDot.className = 'w-2 h-2 rounded-full shrink-0 bg-neutral-400';
            } else if (!datesOk) {
                previewText.textContent = 'The due date must fall on or after the day it is taken out';
                previewDot.className = 'w-2 h-2 rounded-full shrink-0 bg-danger-600';
            } else if (!quantitiesOk) {
                previewText.textContent = 'One of the quantities is more than the shelf has';
                previewDot.className = 'w-2 h-2 rounded-full shrink-0 bg-danger-600';
            } else {
                previewText.textContent = units + ' ' + (units === 1 ? 'unit' : 'units') + ' across '
                    + picked.length + ' ' + (picked.length === 1 ? 'item' : 'items')
                    + ' · out for ' + days + ' ' + (days === 1 ? 'day' : 'days');
                previewDot.className = 'w-2 h-2 rounded-full shrink-0 bg-success-600';
            }

            submit.disabled = !ok;
            hint.textContent = !who.value ? 'Pick who is borrowing'
                : picked.length === 0 ? 'Pick at least one item'
                : !quantitiesOk ? 'Lower the highlighted quantity'
                : !datesOk ? 'Check the dates'
                : purpose.value.trim().length <= 2 ? 'Say what it is for'
                : 'Marked as out the moment you save';
            hint.classList.toggle('text-danger-700', !ok);
            hint.classList.toggle('text-neutral-600', ok);
        }

        list?.addEventListener('change', function (event) {
            const box = event.target.closest('.equipment-checkbox');
            if (box) syncRow(box);
            const qty = event.target.closest('.equipment-qty');
            if (qty) {
                // Clamp once the value is committed, so typing is never fought.
                const max = parseInt(qty.getAttribute('max'), 10);
                const value = parseInt(qty.value, 10);
                if (!isNaN(max) && !isNaN(value) && value > max) qty.value = max;
            }
            sync();
        });
        list?.addEventListener('input', sync);
        [who, from, until, purpose].forEach(function (field) {
            field?.addEventListener('input', sync);
            field?.addEventListener('change', sync);
        });

        const search = document.getElementById('loan-equipment-search');
        const noMatch = document.getElementById('loan-equipment-no-match');
        search?.addEventListener('input', function () {
            const term = this.value.trim().toLowerCase();
            let matched = 0;
            list.querySelectorAll('.equipment-option').forEach(function (row) {
                const matches = !term || (row.dataset.name || '').indexOf(term) !== -1;
                const ticked = !!row.querySelector('.equipment-checkbox:checked');
                // Ticked rows stay visible so nothing is submitted while hidden.
                row.classList.toggle('hidden', !(matches || ticked));
                if (matches) matched++;
            });
            if (noMatch) noMatch.classList.toggle('hidden', matched > 0);
        });

        sync();
    }

    /* ---- edit loan --------------------------------------------------- */
    const editForm = document.getElementById('edit-loan-form');
    if (editForm) {
        const equipmentSelect = document.getElementById('edit-loan-equipment');
        const qtyField = document.getElementById('edit-loan-quantity');
        const maxNote = editForm.querySelector('[data-edit-quantity-max]');
        const borrow = document.getElementById('edit-loan-borrow');
        const due = document.getElementById('edit-loan-return');
        const submit = editForm.querySelector('[data-edit-submit]');
        const hint = editForm.querySelector('[data-edit-hint]');
        const previewText = editForm.querySelector('[data-edit-preview-text]');
        const previewDot = editForm.querySelector('[data-edit-preview-dot]');
        let originalEquipment = null;
        let originalQty = 0;

        // The ceiling is what the shelf can actually cover: whatever is free of
        // the chosen item, plus the units this loan is already holding when the
        // item has not changed.
        function ceiling() {
            const option = equipmentSelect.selectedOptions[0];
            const available = parseInt(option?.dataset.available || '0', 10) || 0;
            const sameItem = String(equipmentSelect.value) === String(originalEquipment);
            return available + (sameItem ? originalQty : 0);
        }

        function sync() {
            const max = ceiling();
            const qty = parseInt(qtyField.value, 10);
            qtyField.max = max;
            maxNote.textContent = '(max ' + max + ')';

            const qtyOk = !isNaN(qty) && qty >= 1 && qty <= max;
            const days = (borrow.value && due.value)
                ? Math.round((new Date(due.value + 'T00:00:00') - new Date(borrow.value + 'T00:00:00')) / 86400000)
                : NaN;
            const datesOk = !isNaN(days) && days >= 0;
            const ok = qtyOk && datesOk;

            if (!qtyOk) {
                previewText.textContent = 'Only ' + max + ' ' + (max === 1 ? 'unit is' : 'units are') + ' coverable for this item';
                previewDot.className = 'w-2 h-2 rounded-full shrink-0 bg-danger-600';
            } else if (!datesOk) {
                previewText.textContent = 'The due date must fall on or after the day it was taken out';
                previewDot.className = 'w-2 h-2 rounded-full shrink-0 bg-danger-600';
            } else {
                previewText.textContent = qty + ' ' + (qty === 1 ? 'unit' : 'units') + ' out for ' + days
                    + ' ' + (days === 1 ? 'day' : 'days');
                previewDot.className = 'w-2 h-2 rounded-full shrink-0 bg-success-600';
            }

            submit.disabled = !ok;
            hint.textContent = ok ? 'Stock adjusts to match' : 'Fix the highlighted detail';
            hint.classList.toggle('text-danger-700', !ok);
            hint.classList.toggle('text-neutral-600', ok);
        }

        [equipmentSelect, qtyField, borrow, due].forEach(function (field) {
            field.addEventListener('input', sync);
            field.addEventListener('change', sync);
        });

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-loan-edit]');
            if (!trigger) return;
            const d = trigger.dataset;
            originalEquipment = d.equipment;
            originalQty = parseInt(d.quantity, 10) || 0;

            document.getElementById('edit-loan-id').value = d.id;
            document.getElementById('edit-loan-user').value = d.user || '';
            equipmentSelect.value = d.equipment || '';
            borrow.value = d.borrow || '';
            due.value = d.return || '';
            qtyField.value = d.quantity || 1;
            document.getElementById('edit-loan-purpose').value = d.purpose || '';
            document.getElementById('edit-loan-remarks').value = d.remarks || '';
            document.getElementById('edit-loan-class').value = d.class || '';
            // The summary sits in the modal header, outside the <form> — scope
            // to the modal, or this lookup is null and the modal never opens.
            document.querySelector('#edit-loan-modal [data-edit-summary]').textContent = d.summary || '';

            sync();
            window.appUI.openModal('edit-loan-modal');
        });
    }

    /* ---- check in ---------------------------------------------------- */
    const checkinForm = document.getElementById('checkin-form');
    if (checkinForm) {
        const conditionField = document.getElementById('checkin-condition');
        const remarks = document.getElementById('checkin-remarks');
        const submit = checkinForm.querySelector('[data-checkin-submit]');
        const hint = checkinForm.querySelector('[data-checkin-hint]');
        const remarksNote = checkinForm.querySelector('[data-checkin-remarks-note]');

        function sync() {
            const needsNote = conditionField.value !== 'Good';
            const ok = !needsNote || remarks.value.trim().length > 2;

            remarksNote.textContent = needsNote ? 'required' : 'optional';
            remarksNote.classList.toggle('text-danger-700', needsNote && !ok);
            submit.disabled = !ok;
            hint.textContent = ok
                ? 'Writes a dated entry in Return Logs'
                : 'Describe the problem for the log';
            hint.classList.toggle('text-danger-700', !ok);
            hint.classList.toggle('text-neutral-600', ok);
        }

        checkinForm.addEventListener('click', function (event) {
            const chip = event.target.closest('[data-condition]');
            if (!chip) return;
            conditionField.value = chip.dataset.condition;
            checkinForm.querySelectorAll('[data-condition]').forEach(function (other) {
                const on = other === chip;
                other.setAttribute('aria-pressed', on ? 'true' : 'false');
                other.classList.toggle('border-primary-300', on);
                other.classList.toggle('bg-primary-50', on);
                other.classList.toggle('text-primary-700', on);
                other.classList.toggle('border-neutral-300', !on);
                other.classList.toggle('bg-white', !on);
                other.classList.toggle('text-neutral-700', !on);
            });
            sync();
        });

        remarks.addEventListener('input', sync);

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-checkin-trigger]');
            if (!trigger) return;
            document.getElementById('checkin-id').value = trigger.dataset.id;
            // Summary and timing live in the modal header, outside the <form>.
            const modal = document.getElementById('checkin-modal');
            modal.querySelector('[data-checkin-summary]').textContent = trigger.dataset.summary || '';
            const timing = modal.querySelector('[data-checkin-timing]');
            timing.textContent = trigger.dataset.timing || '';
            timing.classList.toggle('text-danger-700', trigger.dataset.late === '1');
            timing.classList.toggle('text-neutral-600', trigger.dataset.late !== '1');
            remarks.value = '';
            checkinForm.querySelector('[data-condition="Good"]').click();
            window.appUI.openModal('checkin-modal');
        });

        sync();
    }

    /* ---- email ------------------------------------------------------- */
    let emailTransactionId = null;
    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-email-trigger]');
        if (!trigger) return;
        emailTransactionId = trigger.dataset.id;
        const to = document.getElementById('modalEmail');
        const message = document.getElementById('modalMessage');
        const summary = document.querySelector('[data-email-summary]');
        const history = document.querySelector('[data-email-history]');

        if (to) to.value = trigger.dataset.email || '';
        if (message) message.value = '';

        // Every one of these falls back to a sentence rather than to the
        // dataset value: an absent attribute reads `undefined`, and a dialog
        // that says "undefined · undefined" is worse than one that says nothing.
        if (summary) {
            const loan = trigger.dataset.summary;
            const timing = trigger.dataset.timing;
            summary.textContent = loan
                ? loan + (timing ? ' · ' + timing : '')
                : 'Sent from the equipment office address.';
        }

        if (history) {
            const sent = parseInt(trigger.dataset.reminders || '0', 10) || 0;
            const last = trigger.dataset.lastReminded || '';
            history.hidden = sent === 0;
            history.textContent = sent === 0
                ? ''
                : (sent === 1
                    ? 'One reminder already sent' + (last ? ' on ' + last : '') + '.'
                    : sent + ' reminders already sent' + (last ? ', the last on ' + last : '') + '.');
        }

        window.appUI.openModal('emailModal');
    });

    document.getElementById('emailType')?.addEventListener('change', function () {
        document.getElementById('customMessageBox')?.classList.toggle('hidden', this.value !== 'custom');
    });

    document.getElementById('sendEmailConfirm')?.addEventListener('click', function () {
        const type = document.getElementById('emailType').value;
        const message = document.getElementById('modalMessage').value;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.value;

        fetch('/send-email/' + emailTransactionId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ type: type, message: message }),
            credentials: 'same-origin',
        })
        .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
        .then(function (result) {
            if (!result.ok) throw new Error((result.data && result.data.message) || 'Failed to send email');
            window.appUI.closeModal('emailModal');
            if (window.showAlert) window.showAlert('success', result.data.message, { title: 'Email sent' });
        })
        .catch(function (error) {
            if (window.showAlert) window.showAlert('error', error.message || 'Failed to send email');
        });
    });
});
</script>
@endsection
