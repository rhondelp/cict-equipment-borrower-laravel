@extends('components.default')
@section('title', 'My equipment - CICT Equipment Borrower System')
@section('content')

@php
    $user = Auth::user();

    $openLoans = $transactions->filter(fn ($tx) => $tx->isOut());
    $unitsHeld = $openLoans->sum('quantity');
    $overdueCount = $openLoans->filter(fn ($tx) => $tx->isOverdue())->count();
    $waitingCount = $requests->where('status', 'Pending')->count();
    $dueSoonCount = $openLoans->filter(fn ($tx) => in_array($tx->daysUntilDue(), [0, 1], true))->count();

    // The band at the top says one true sentence about where this person stands,
    // in the order that matters to them: overdue first, then due soon, then
    // waiting, then nothing at all.
    if ($overdueCount > 0) {
        $headline = $overdueCount === 1 ? 'One thing is overdue' : $overdueCount.' things are overdue';
        $subline = 'Bring it back to the equipment room today — overdue items hold up your next request.';
    } elseif ($dueSoonCount > 0) {
        $headline = $dueSoonCount === 1 ? 'Something is due back' : $dueSoonCount.' things are due back';
        $subline = 'Return them by the date on each card and nothing goes late.';
    } elseif ($waitingCount > 0) {
        $headline = $waitingCount === 1 ? 'One request is waiting' : $waitingCount.' requests are waiting';
        $subline = 'Nothing is held for you until an administrator approves it.';
    } elseif ($unitsHeld > 0) {
        $headline = 'You are all square';
        $subline = 'Everything you hold is still inside its return date.';
    } else {
        $headline = 'Nothing checked out';
        $subline = 'Request something from the shelf when you need it.';
    }

    $initials = collect(preg_split('/\s+/', trim((string) $user->name)))
        ->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('') ?: '—';

    $shelf = $equipments->sortByDesc('available_quantity');
    $lendableCount = $equipments->where('available_quantity', '>', 0)->count();

    $tones = [
        'danger' => ['border' => 'border-danger-200', 'chip' => 'bg-danger-50 text-danger-700', 'date' => 'text-danger-700'],
        'warning' => ['border' => 'border-warning-200', 'chip' => 'bg-warning-50 text-warning-700', 'date' => 'text-warning-700'],
        'primary' => ['border' => 'border-neutral-200', 'chip' => 'bg-primary-50 text-primary-700', 'date' => 'text-neutral-800'],
        'success' => ['border' => 'border-success-200', 'chip' => 'bg-success-50 text-success-700', 'date' => 'text-success-700'],
    ];
@endphp

{{-- 100dvh rather than 100vh: on mobile Safari the viewport unit shifts as the
     URL bar collapses, which makes a 100vh column jump during scroll. --}}
<div class="flex flex-col min-h-[100dvh] page-bg">

    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>

    {{-- :menu="false" — this page has no sidebar, so the header's hamburger has
         nothing to toggle. See the note in components/ui/page-header. --}}
    <x-ui.page-header logo :menu="false" eyebrow="My equipment" :title="$user->name">
        <x-slot:actions>
            {{-- Notifications bell. Read-only feed of the rows written by the
                 return-reminder job; nothing here creates or mutates them. --}}
            <div class="relative">
                <button type="button" id="notif-btn"
                        class="relative inline-flex items-center justify-center transition bg-white border rounded-md w-11 h-11 text-neutral-700 border-neutral-300 hover:bg-neutral-50 hover:border-neutral-400"
                        aria-haspopup="true" aria-expanded="false"
                        aria-label="Notifications ({{ $notifications->count() }})">
                    <i class="text-lg fas fa-bell" aria-hidden="true"></i>
                    @if($notifications->isNotEmpty())
                        <span class="absolute -top-1.5 -right-1.5 grid h-5 min-w-[1.25rem] place-items-center rounded-full bg-danger-600 px-1.5 text-[11px] font-bold text-white ring-2 ring-white tabular-nums">{{ $notifications->count() }}</span>
                    @endif
                </button>

                {{-- Width is clamped to the viewport so the panel cannot overflow the
                     screen edge on a narrow phone, where a flat w-80 would. --}}
                <div id="notif-panel"
                     class="absolute right-0 z-overlay mt-2 hidden w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-pop sm:w-96">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-neutral-200 bg-neutral-50">
                        <p class="text-base font-semibold text-neutral-900">Notifications</p>
                        <button type="button" id="notif-close"
                                class="grid transition rounded-md w-9 h-9 place-items-center text-neutral-600 hover:bg-neutral-200 hover:text-neutral-900"
                                aria-label="Close notifications">
                            <i class="text-base fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>

                    @if($notifications->isEmpty())
                        <div class="px-4 py-10 text-center">
                            <span class="grid w-12 h-12 mx-auto mb-3 border rounded-xl bg-neutral-50 border-neutral-200 place-items-center">
                                <i class="text-lg fas fa-bell-slash text-neutral-400" aria-hidden="true"></i>
                            </span>
                            <p class="text-base font-semibold text-neutral-800">No notifications yet</p>
                            <p class="mt-1 text-sm text-neutral-600">Return reminders will show up here.</p>
                        </div>
                    @else
                        <ul class="overflow-y-auto divide-y max-h-80 divide-neutral-200">
                            @foreach($notifications as $notification)
                                <li class="px-4 py-3 transition hover:bg-neutral-50">
                                    <p class="text-sm leading-relaxed text-neutral-800 text-pretty">{{ $notification->message }}</p>
                                    <p class="mt-1 text-xs text-neutral-600">
                                        {{ \Carbon\Carbon::parse($notification->send_date ?? $notification->created_at)->diffForHumans() }}
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            {{-- Both action buttons keep a 44px target but drop their label below sm,
                 which is what lets the header fit a 360px viewport without the title
                 being squeezed to nothing. The label survives for assistive tech via
                 aria-label. --}}
            <button type="button" data-modal-open="request-modal"
                    class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center gap-2 rounded-md bg-primary-600 px-3 py-3 text-base font-semibold text-white transition hover:bg-primary-700 sm:px-5"
                    aria-label="Request equipment">
                <i class="text-base fas fa-plus" aria-hidden="true"></i>
                <span class="hidden sm:inline">Request</span>
            </button>
            <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden">@csrf</form>
            <button type="button" id="logout-btn"
                    class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center gap-2 rounded-md border border-neutral-300 bg-white px-3 py-3 text-base font-semibold text-neutral-700 transition hover:bg-neutral-50 sm:px-5"
                    aria-label="Log out">
                <i class="text-base fas fa-right-from-bracket" aria-hidden="true"></i>
                <span class="hidden sm:inline">Log out</span>
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <main id="main-content" class="flex-1 w-full p-4 mx-auto space-y-5 sm:p-6 max-w-content">

        {{-- Where you stand, in a sentence. This replaced three counter tiles
             that read 0 / 0 / 0 for most borrowers most of the time. --}}
        <section class="p-5 text-white anim-rise rounded-xl bg-neutral-900 sm:p-6">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div class="min-w-0">
                    <p class="text-xs font-semibold tracking-widest uppercase text-white/60">Your standing</p>
                    <h2 class="mt-2 text-2xl font-semibold text-balance">{{ $headline }}</h2>
                    <p class="mt-1 text-base {{ $overdueCount > 0 ? 'text-danger-200' : 'text-white/70' }} text-pretty">{{ $subline }}</p>
                </div>
                <dl class="flex overflow-hidden rounded-lg bg-white/10">
                    <div class="px-5 py-3 min-w-[6.5rem]">
                        <dd class="text-2xl font-semibold tabular-nums">{{ $unitsHeld }}</dd>
                        <dt class="text-sm text-white/70">units held</dt>
                    </div>
                    <div class="px-5 py-3 min-w-[6.5rem] border-l border-white/10">
                        <dd class="text-2xl font-semibold tabular-nums {{ $overdueCount > 0 ? 'text-danger-300' : 'text-white/50' }}">{{ $overdueCount }}</dd>
                        <dt class="text-sm text-white/70">overdue</dt>
                    </div>
                    <div class="px-5 py-3 min-w-[6.5rem] border-l border-white/10">
                        <dd class="text-2xl font-semibold tabular-nums {{ $waitingCount > 0 ? 'text-warning-300' : 'text-white/50' }}">{{ $waitingCount }}</dd>
                        <dt class="text-sm text-white/70">awaiting reply</dt>
                    </div>
                </dl>
            </div>
        </section>

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">

            {{-- The agenda. One row per thing this person has to do, soonest
                 first, each with the date it hangs off in the rail beside it. --}}
            <section aria-labelledby="agenda-heading" class="min-w-0 space-y-3 anim-rise [animation-delay:70ms]">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                    <h2 id="agenda-heading" class="text-lg font-semibold text-neutral-900">What needs doing</h2>
                    <p class="text-sm text-neutral-600">
                        {{ count($agenda) }} open {{ str('item')->plural(count($agenda)) }}
                    </p>
                </div>

                @forelse($agenda as $item)
                    @php $tone = $tones[$item['tone']] ?? $tones['primary']; @endphp
                    <article class="grid grid-cols-[3.25rem_minmax(0,1fr)] gap-3 sm:gap-4">
                        <div class="flex flex-col items-center pt-4">
                            <span class="text-xs font-semibold tracking-wider uppercase text-neutral-500">
                                {{ $item['date']?->format('M') ?? '—' }}
                            </span>
                            <span class="text-xl font-semibold leading-tight tabular-nums {{ $tone['date'] }}">
                                {{ $item['date']?->format('d') ?? '' }}
                            </span>
                            <span class="flex-1 w-0.5 mt-2 rounded bg-neutral-200" aria-hidden="true"></span>
                        </div>

                        <div class="pb-2">
                            <div class="flex flex-wrap items-start justify-between gap-4 p-4 bg-white border rounded-xl {{ $tone['border'] }}">
                                <div class="min-w-0 flex-1 basis-56">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded px-2 py-0.5 text-xs font-bold uppercase tracking-wider {{ $tone['chip'] }}">
                                            {{ $item['tag'] }}
                                        </span>
                                        <span class="text-sm text-neutral-600">{{ $item['when'] }}</span>
                                    </div>
                                    <h3 class="mt-1.5 text-base font-semibold text-neutral-900 text-pretty">{{ $item['title'] }}</h3>
                                    <p class="mt-1 text-sm leading-relaxed text-neutral-600 text-pretty">{{ $item['detail'] }}</p>
                                </div>

                                <div class="shrink-0">
                                    @if($item['kind'] === 'pending')
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    class="inline-flex min-h-[40px] items-center gap-2 rounded-md border border-neutral-300 bg-white px-3.5 py-2 text-sm font-semibold text-neutral-700 hover:bg-neutral-50"
                                                    data-request-edit
                                                    data-id="{{ $item['id'] }}"
                                                    data-equipment="{{ $requests->firstWhere('id', $item['id'])?->equipment?->equipment_name }}"
                                                    data-quantity="{{ $requests->firstWhere('id', $item['id'])?->quantity }}"
                                                    data-remarks="{{ $requests->firstWhere('id', $item['id'])?->remarks }}"
                                                    data-available="{{ $requests->firstWhere('id', $item['id'])?->equipment?->available_quantity }}">
                                                <i class="text-sm fas fa-pen" aria-hidden="true"></i> Edit
                                            </button>
                                            <button type="button"
                                                    class="inline-flex min-h-[40px] items-center gap-2 rounded-md border border-neutral-300 bg-white px-3.5 py-2 text-sm font-semibold text-neutral-700 hover:border-danger-300 hover:bg-danger-50 hover:text-danger-700"
                                                    data-request-withdraw
                                                    data-id="{{ $item['id'] }}"
                                                    data-label="{{ $item['title'] }}"
                                                    data-url="{{ route('borrower.request.destroy', $item['id']) }}">
                                                <i class="text-sm fas fa-times" aria-hidden="true"></i> Withdraw
                                            </button>
                                        </div>
                                    @elseif($item['url'])
                                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener"
                                           class="inline-flex min-h-[40px] items-center gap-2 rounded-md border border-neutral-300 bg-white px-3.5 py-2 text-sm font-semibold text-neutral-700 hover:bg-neutral-50">
                                            <i class="text-sm fas fa-print" aria-hidden="true"></i> {{ $item['action'] }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-12 text-center bg-white border border-dashed rounded-xl border-neutral-300">
                        <span class="grid w-14 h-14 mx-auto mb-3 border rounded-2xl bg-neutral-50 border-neutral-200 place-items-center">
                            <i class="text-xl fas fa-check text-success-600" aria-hidden="true"></i>
                        </span>
                        <p class="text-lg font-semibold text-neutral-800">Nothing outstanding</p>
                        <p class="max-w-sm mx-auto mt-1 text-base text-neutral-600 text-pretty">
                            You have nothing borrowed and nothing waiting. Request something from the shelf when you need it.
                        </p>
                        <button type="button" data-modal-open="request-modal" class="mt-5 btn-primary">
                            <i class="text-base fas fa-plus" aria-hidden="true"></i> Request equipment
                        </button>
                    </div>
                @endforelse

                @if(count($history) > 0)
                    <div class="pt-1">
                        <button type="button" id="history-toggle" aria-expanded="false" aria-controls="history-panel"
                                class="inline-flex items-center gap-2 py-1 text-sm font-semibold rounded text-neutral-600 hover:text-neutral-900">
                            <i class="text-xs fas fa-chevron-right" aria-hidden="true"></i>
                            <span id="history-label">Show earlier activity ({{ count($history) }})</span>
                        </button>

                        <div id="history-panel" hidden class="mt-3 overflow-hidden bg-white border divide-y rounded-xl border-neutral-200 divide-neutral-200">
                            @foreach($history as $entry)
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 px-4 py-2.5">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $entry['tone'] === 'danger' ? 'bg-danger-600' : 'bg-success-600' }}" aria-hidden="true"></span>
                                    <span class="text-sm font-medium truncate text-neutral-900">{{ $entry['title'] }}</span>
                                    <span class="flex-1 min-w-0 text-sm text-neutral-600 text-pretty">{{ $entry['note'] }}</span>
                                    <span class="text-sm shrink-0 text-neutral-500 tabular-nums">{{ $entry['when']?->format('M j') ?? '' }}</span>
                                    @if($entry['receipt'])
                                        <a href="{{ $entry['receipt'] }}" target="_blank" rel="noopener"
                                           class="text-sm font-semibold shrink-0 text-primary-700 hover:text-primary-800">Slip</a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            {{-- What is on the shelf, and how to reach the office. --}}
            <aside class="space-y-4 min-w-0 anim-rise [animation-delay:140ms] lg:sticky lg:top-24">
                <button type="button" data-modal-open="request-modal"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-3.5 text-base font-semibold text-white transition hover:bg-primary-700">
                    <i class="text-base fas fa-plus" aria-hidden="true"></i> Request equipment
                </button>

                <section aria-labelledby="shelf-heading" class="overflow-hidden bg-white border rounded-xl border-neutral-200">
                    <div class="flex items-baseline justify-between gap-3 px-4 py-3 border-b border-neutral-200">
                        <h2 id="shelf-heading" class="text-base font-semibold text-neutral-900">On the shelf now</h2>
                        <span class="text-sm text-neutral-600">{{ $lendableCount }} of {{ $equipments->count() }}</span>
                    </div>

                    @forelse($shelf as $item)
                        @php
                            $none = $item->available_quantity < 1;
                            $percent = $item->quantity > 0 ? round(($item->available_quantity / $item->quantity) * 100) : 0;
                            $barTone = $none ? 'bg-danger-500' : ($percent <= 30 ? 'bg-warning-500' : 'bg-success-500');
                        @endphp
                        <button type="button" @disabled($none)
                                data-request-equipment="{{ $item->id }}"
                                class="flex w-full items-center gap-3 border-b border-neutral-100 px-4 py-2.5 text-left transition last:border-b-0 disabled:cursor-not-allowed enabled:hover:bg-neutral-50">
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm font-medium truncate {{ $none ? 'text-neutral-500' : 'text-neutral-900' }}">{{ $item->equipment_name }}</span>
                                <span class="block text-xs {{ $none ? 'text-danger-700' : 'text-neutral-600' }}">
                                    {{ $none ? 'None left' : $item->available_quantity.' of '.$item->quantity.' free' }}
                                </span>
                            </span>
                            <span class="w-10 h-1 overflow-hidden rounded-full shrink-0 bg-neutral-200" aria-hidden="true">
                                <span class="block h-full rounded-full {{ $barTone }}" style="width: {{ max($percent, 0) }}%"></span>
                            </span>
                        </button>
                    @empty
                        <p class="px-4 py-8 text-sm text-center text-neutral-600">No equipment has been added yet.</p>
                    @endforelse
                </section>

                <section class="p-4 space-y-2 bg-white border rounded-xl border-neutral-200">
                    <h2 class="text-xs font-semibold tracking-widest uppercase text-neutral-600">Equipment room</h2>
                    @php($officeHours = \App\Support\OfficeHours::fromConfig())
                    <p class="text-sm font-medium text-neutral-900">Open {{ $officeHours->timeRange() }}, {{ $officeHours->dayRange(short: true) }}</p>
                    <p class="text-sm text-neutral-600 break-words">Bring your student or faculty ID when collecting.</p>
                    <a href="{{ route('legal.terms') }}" class="inline-block text-sm font-semibold text-primary-700 hover:text-primary-800">
                        Borrowing rules →
                    </a>
                </section>
            </aside>
        </div>
    </main>
</div>

@include('components.instructor.request-item-modal')
@include('components.instructor.update-request-modal')

{{-- Submitted by the withdraw confirm; no markup of its own. --}}
<form id="withdraw-form" method="POST" action="" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ---- notifications panel ----------------------------------------- */
    const notifBtn = document.getElementById('notif-btn');
    const notifPanel = document.getElementById('notif-panel');
    if (notifBtn && notifPanel) {
        const setOpen = function (open) {
            notifPanel.hidden = !open;
            notifPanel.classList.toggle('hidden', !open);
            notifBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        notifBtn.addEventListener('click', function () {
            setOpen(notifPanel.classList.contains('hidden'));
        });
        document.getElementById('notif-close')?.addEventListener('click', function () { setOpen(false); });
        document.addEventListener('click', function (event) {
            if (!notifBtn.contains(event.target) && !notifPanel.contains(event.target)) setOpen(false);
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') setOpen(false);
        });
    }

    /* ---- earlier activity -------------------------------------------- */
    const historyToggle = document.getElementById('history-toggle');
    if (historyToggle) {
        const panel = document.getElementById('history-panel');
        const label = document.getElementById('history-label');
        const count = label.textContent.match(/\((\d+)\)/)?.[1] || '';
        historyToggle.addEventListener('click', function () {
            const open = panel.hidden;
            panel.hidden = !open;
            historyToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            historyToggle.querySelector('i').className = open ? 'fas fa-chevron-down text-xs' : 'fas fa-chevron-right text-xs';
            label.textContent = (open ? 'Hide' : 'Show') + ' earlier activity (' + count + ')';
        });
    }

    /* ---- new request -------------------------------------------------- */
    const requestForm = document.getElementById('request-form');
    if (requestForm) {
        const quantity = document.getElementById('request-quantity');
        const remarks = document.getElementById('request-remarks');
        const submit = requestForm.querySelector('[data-request-submit]');
        const hint = requestForm.querySelector('[data-request-hint]');
        const maxNote = requestForm.querySelector('[data-request-max]');
        const previewText = requestForm.querySelector('[data-request-preview-text]');
        const previewDot = requestForm.querySelector('[data-request-preview-dot]');

        function picked() {
            return requestForm.querySelector('.request-equipment:checked');
        }

        // The cap is the item's real availability, so the form cannot be used
        // to ask for five of something there are two of.
        function sync() {
            const choice = picked();
            const available = choice ? parseInt(choice.dataset.available, 10) || 0 : 0;
            const qty = parseInt(quantity.value, 10);

            quantity.max = Math.max(available, 1);
            maxNote.textContent = choice ? '(max ' + available + ')' : '';

            const qtyOk = choice && !isNaN(qty) && qty >= 1 && qty <= available;
            const purposeOk = remarks.value.trim().length > 3;
            const ok = !!choice && qtyOk && purposeOk;

            if (!choice) {
                previewText.textContent = 'Pick an item from the list';
                previewDot.className = 'w-2 h-2 rounded-full shrink-0 bg-neutral-400';
            } else if (!qtyOk) {
                previewText.textContent = 'Only ' + available + ' available — lower the quantity';
                previewDot.className = 'w-2 h-2 rounded-full shrink-0 bg-danger-600';
            } else {
                previewText.textContent = 'Asking for ' + qty + ' of ' + available + ' available';
                previewDot.className = 'w-2 h-2 rounded-full shrink-0 bg-success-600';
            }

            submit.disabled = !ok;
            hint.textContent = !choice ? 'Choose what you need'
                : !qtyOk ? 'Lower the quantity'
                : !purposeOk ? 'Say what it is for'
                : 'Reviewed within one working day';
            hint.classList.toggle('text-danger-700', !ok);
            hint.classList.toggle('text-neutral-600', ok);
        }

        requestForm.addEventListener('change', sync);
        requestForm.addEventListener('input', sync);
        requestForm.addEventListener('click', function (event) {
            const step = event.target.closest('[data-request-step]');
            if (!step) return;
            const choice = picked();
            const available = choice ? parseInt(choice.dataset.available, 10) || 1 : 1;
            const next = (parseInt(quantity.value, 10) || 0) + parseInt(step.dataset.requestStep, 10);
            quantity.value = Math.min(Math.max(1, next), available);
            sync();
        });

        // "Request" on a shelf row is the same form, opened with that item
        // already chosen.
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-request-equipment]');
            if (!trigger) return;
            const id = trigger.getAttribute('data-request-equipment');
            const option = requestForm.querySelector('.request-equipment[value="' + id + '"]');
            if (option && !option.disabled) option.checked = true;
            quantity.value = 1;
            sync();
            window.appUI.openModal('request-modal');
        });

        sync();
    }

    /* ---- edit request -------------------------------------------------- */
    const editForm = document.getElementById('edit-request-form');
    if (editForm) {
        const quantity = document.getElementById('edit-request-quantity');
        const submit = editForm.querySelector('[data-edit-request-submit]');
        const hint = editForm.querySelector('[data-edit-request-hint]');
        const maxNote = editForm.querySelector('[data-edit-request-max]');
        let available = 0;

        function sync() {
            const qty = parseInt(quantity.value, 10);
            const ok = !isNaN(qty) && qty >= 1 && qty <= available;
            quantity.max = Math.max(available, 1);
            maxNote.textContent = '(max ' + available + ')';
            submit.disabled = !ok;
            hint.textContent = ok ? 'The office sees the change straight away' : 'Only ' + available + ' available right now';
            hint.classList.toggle('text-danger-700', !ok);
            hint.classList.toggle('text-neutral-600', ok);
        }

        quantity.addEventListener('input', sync);

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-request-edit]');
            if (!trigger) return;
            available = parseInt(trigger.dataset.available, 10) || 0;
            document.getElementById('edit-request-id').value = trigger.dataset.id;
            document.getElementById('edit-request-equipment').textContent = trigger.dataset.equipment || '';
            quantity.value = trigger.dataset.quantity || 1;
            document.getElementById('edit-request-remarks').value = trigger.dataset.remarks || '';
            sync();
            window.appUI.openModal('edit-request-modal');
        });
    }

    /* ---- withdraw a request ------------------------------------------- */
    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-request-withdraw]');
        if (!trigger) return;
        const form = document.getElementById('withdraw-form');
        form.action = trigger.dataset.url;

        window.showConfirm({
            title: 'Withdraw this request?',
            text: trigger.dataset.label + ' will be removed from the queue. Nothing was held for you, so nothing changes on the shelf.',
            icon: 'warning',
            confirmText: 'Yes, withdraw',
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
    });

    /* ---- logout -------------------------------------------------------- */
    document.getElementById('logout-btn')?.addEventListener('click', function () {
        window.showConfirm({
            title: 'Log out?',
            text: 'You will need to sign in again to reach your dashboard.',
            icon: 'question',
            confirmText: 'Yes, log out',
        }).then(function (result) {
            if (result.isConfirmed) document.getElementById('logout-form').submit();
        });
    });
});
</script>
@endsection
