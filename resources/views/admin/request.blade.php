@extends('components.default')
@section('title', 'Requests - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

@php
    $blockedCount = $pending->reject(fn ($request) => $request->canBeFilled())->count();
    $approvedCount = $decided->where('status', 'Approved')->count();
    $declinedCount = $decided->where('status', 'Declined')->count();
@endphp

<div class="min-h-[100dvh] page-bg md:ml-64">

    {{-- Essential for keyboard users: the sidebar is six links deep, so without
         this every page begins with six tab stops before the content. --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>

    <x-ui.page-header eyebrow="Requests" title="Item requests">
        {{ $pending->count() }} awaiting review · {{ $decided->count() }} decided
    </x-ui.page-header>

    <main id="main-content" class="p-4 mx-auto space-y-6 sm:p-6 max-w-content">

        {{-- The queue leads, because it is the only part of this page anyone
             has to do something about. The archive sits underneath it. --}}
        <section aria-labelledby="pending-heading" class="space-y-3">
            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <h2 id="pending-heading" class="text-lg font-semibold text-neutral-900">Waiting on you</h2>
                <p class="text-sm text-neutral-600">
                    @if($pending->isEmpty())
                        Nothing in the queue
                    @elseif($blockedCount > 0)
                        {{ $blockedCount }} cannot be filled from current stock
                    @else
                        All fillable from current stock
                    @endif
                </p>
            </div>

            @forelse ($pending as $request)
                @php
                    $equipment = $request->equipment;
                    $available = $equipment?->available_quantity ?? 0;
                    $total = $equipment?->quantity ?? 0;
                    $retired = $equipment?->isRetired() ?? false;
                    $fillable = $request->canBeFilled();
                    $waited = $request->requested_date
                        ? (int) $request->requested_date->startOfDay()->diffInDays(now()->startOfDay())
                        : 0;
                    $label = ($equipment->equipment_name ?? 'Deleted equipment').($request->quantity > 1 ? ' ×'.$request->quantity : '');
                @endphp

                <article class="bg-white border rounded-xl border-neutral-200 border-l-[3px] {{ $fillable ? 'border-l-primary-500' : 'border-l-danger-500' }}">
                    <div class="flex flex-wrap items-start justify-between gap-4 px-5 pt-4">
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-neutral-900">{{ $label }}</h3>
                            <p class="mt-0.5 text-sm text-neutral-600">
                                {{ $request->user->name ?? 'Deleted user' }}
                                @if($request->user) · {{ $request->user->user_type }} @endif
                                · asked {{ $waited === 0 ? 'today' : $waited.' '.str('day')->plural($waited).' ago' }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button"
                                    class="inline-flex min-h-[40px] items-center gap-2 rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-semibold text-neutral-700 hover:border-danger-300 hover:bg-danger-50 hover:text-danger-700"
                                    data-decline-trigger
                                    data-id="{{ $request->id }}"
                                    data-summary="{{ $label.' · '.($request->user->name ?? 'Deleted user') }}">
                                <i class="text-sm fas fa-times" aria-hidden="true"></i> Decline
                            </button>

                            {{-- Disabled, and says why, when the shelf cannot
                                 cover it: approving deducts stock, and stock
                                 that is not there cannot be deducted. --}}
                            <form method="POST" action="{{ route('admin.request.approve') }}" class="js-approve-form">
                                @csrf
                                <input type="hidden" name="id" value="{{ $request->id }}">
                                <button type="submit" @disabled(! $fillable)
                                        title="{{ $fillable ? 'Approve and hand over' : ($retired ? 'This item has been retired' : 'Not enough stock to fill this request') }}"
                                        class="inline-flex min-h-[40px] items-center gap-2 rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:cursor-not-allowed disabled:bg-neutral-300 disabled:hover:bg-neutral-300">
                                    <i class="text-sm fas fa-check" aria-hidden="true"></i>
                                    {{ $fillable ? 'Approve' : ($retired ? 'Retired' : "Can't fill") }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 px-5 pt-3 text-sm">
                        <span class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1 font-semibold
                                     {{ $fillable ? 'bg-success-50 text-success-700' : 'bg-danger-50 text-danger-700' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $fillable ? 'bg-success-600' : 'bg-danger-600' }}" aria-hidden="true"></span>
                            @if($retired)
                                {{ $equipment->equipment_name }} is retired
                            @elseif($fillable)
                                {{ $available }} of {{ $total }} available
                            @else
                                Only {{ $available }} of {{ $total }} available
                            @endif
                        </span>
                        <span class="text-neutral-600">
                            Needs {{ $request->quantity }} {{ str('unit')->plural($request->quantity) }} ·
                            requested {{ $request->requested_date?->format('M j') ?? '—' }}
                        </span>
                    </div>

                    <p class="px-5 py-3 mt-3 text-sm border-t text-neutral-700 border-neutral-100 text-pretty">
                        {{ $request->remarks ?: 'No reason given for the request.' }}
                    </p>
                </article>
            @empty
                <div class="bg-white border rounded-xl border-neutral-200">
                    <x-ui.empty-state icon="fa-check" title="Queue is clear"
                                      message="Every request has been decided. New ones land here as borrowers send them." />
                </div>
            @endforelse
        </section>

        {{-- The archive. Every declined row carries its reason, because that is
             the only place the borrower's explanation is ever written down. --}}
        <section aria-labelledby="decided-heading">
            <x-ui.panel data-list data-active-chip="all">
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b sm:px-5 border-neutral-200">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 id="decided-heading" class="mr-1 text-lg font-semibold text-neutral-900">Decided</h2>
                        @foreach(['all' => 'All '.$decided->count(), 'approved' => 'Approved '.$approvedCount, 'declined' => 'Declined '.$declinedCount] as $value => $label)
                            <button type="button" data-list-chip="{{ $value }}"
                                    aria-pressed="{{ $value === 'all' ? 'true' : 'false' }}"
                                    class="inline-flex min-h-[36px] items-center rounded-full border px-3.5 py-1.5 text-sm font-semibold transition
                                           {{ $value === 'all' ? 'border-primary-300 bg-primary-50 text-primary-700' : 'border-neutral-300 bg-white text-neutral-700' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    @if($decided->isNotEmpty())
                        <label class="relative flex-1 min-w-[12rem] max-w-xs">
                            <span class="sr-only">Search decided requests</span>
                            <i class="absolute text-sm -translate-y-1/2 pointer-events-none fas fa-search left-4 top-1/2 text-neutral-500" aria-hidden="true"></i>
                            <input type="search" data-list-search autocomplete="off" placeholder="Search decided"
                                   class="w-full min-h-[40px] rounded-md border border-neutral-300 py-2 pl-10 pr-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                        </label>
                    @endif
                </div>

                @if($decided->isEmpty())
                    <x-ui.empty-state icon="fa-clipboard-list" title="Nothing decided yet"
                                      message="Approvals and declines are archived here with who decided and why." />
                @else
                    <div class="divide-y divide-neutral-200">
                        @foreach ($decided as $request)
                            @php
                                $approved = $request->status === 'Approved';
                                $label = ($request->equipment->equipment_name ?? 'Deleted equipment')
                                    .($request->quantity > 1 ? ' ×'.$request->quantity : '');
                            @endphp
                            <div data-list-row data-chip="all {{ strtolower($request->status) }}"
                                 data-search="{{ strtolower($label.' '.($request->user->name ?? '').' '.$request->decision_reason) }}"
                                 class="grid gap-2 px-4 py-3 sm:px-5 md:grid-cols-[minmax(0,2fr)_minmax(0,2fr)_7rem] md:items-center md:gap-4">
                                <div class="min-w-0">
                                    <p class="text-base font-semibold truncate text-neutral-900">{{ $label }}</p>
                                    <p class="text-sm truncate text-neutral-600">{{ $request->user->name ?? 'Deleted user' }}</p>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm truncate text-neutral-700">{{ $request->decisionLine() }}</p>
                                    <p class="text-sm text-pretty {{ $approved ? 'text-neutral-600' : 'text-neutral-700' }}">
                                        {{ $approved
                                            ? 'Handed over as a loan'
                                            : ($request->decision_reason ?: 'No reason recorded — decided before reasons were required') }}
                                    </p>
                                </div>
                                <div class="md:text-right">
                                    <x-ui.status :label="$request->status" :tone="$approved ? 'success' : 'danger'" />
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <p data-list-empty hidden class="px-5 py-12 text-base text-center text-neutral-600">
                        Nothing decided matches that filter.
                    </p>

                    <div class="px-5 py-3 text-sm border-t text-neutral-600 border-neutral-200">
                        <span data-list-count data-total="{{ $decided->count() }}" data-noun="requests"></span>
                    </div>
                @endif
            </x-ui.panel>
        </section>
    </main>
</div>

{{-- Decline needs a reason. The field is required here and required again in
     ItemRequestController::requestActions, so there is no path that writes
     Declined without one — and the borrower's dashboard shows it back to them. --}}
<div id="decline-modal" data-modal
     class="fixed inset-0 z-modal items-center justify-center hidden p-4 overflow-y-auto bg-neutral-900/50"
     role="dialog" aria-modal="true" aria-labelledby="decline-title">
    <div class="w-full max-w-lg my-auto bg-white border border-neutral-200 rounded-xl shadow-pop">
        <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4">
            <div class="min-w-0">
                <h2 id="decline-title" class="text-lg font-semibold text-neutral-900">Decline this request</h2>
                <p data-decline-summary class="mt-1 text-sm text-neutral-600"></p>
            </div>
            <button type="button" data-modal-close="decline-modal" aria-label="Close"
                    class="grid w-10 h-10 border rounded-md shrink-0 place-items-center border-neutral-200 text-neutral-600 hover:bg-neutral-50">
                <i class="text-base fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <form id="decline-form" method="POST" action="{{ route('admin.request.decline') }}">
            @csrf
            <input type="hidden" name="id" id="decline-id">

            <div class="px-6 pb-5 space-y-2">
                <label for="decline-reason" class="block text-base font-medium text-neutral-800">
                    Why are you declining?
                </label>
                <p class="text-sm text-neutral-600">The borrower reads this on their dashboard, so write it to them.</p>
                <textarea id="decline-reason" name="reason" rows="3" minlength="5" maxlength="500" required data-autofocus
                          placeholder="e.g. A cable already comes with the projector loan you have."
                          class="w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30"></textarea>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <p data-decline-hint class="text-sm min-w-0 text-danger-700">A reason is required</p>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" data-modal-close="decline-modal"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                        Cancel
                    </button>
                    <button type="submit" data-decline-submit disabled
                            class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-md bg-danger-600 px-5 py-3 text-base font-semibold text-white hover:bg-danger-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-danger-600">
                        <i class="text-base fas fa-times" aria-hidden="true"></i> Decline request
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('decline-form');
    if (form) {
        const reason = document.getElementById('decline-reason');
        const submit = form.querySelector('[data-decline-submit]');
        const hint = form.querySelector('[data-decline-hint]');

        function sync() {
            const ok = reason.value.trim().length >= 5;
            submit.disabled = !ok;
            hint.textContent = ok
                ? 'Sent to the borrower with the decline'
                : 'A reason of at least 5 characters is required';
            hint.classList.toggle('text-danger-700', !ok);
            hint.classList.toggle('text-neutral-600', ok);
        }

        reason.addEventListener('input', sync);

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-decline-trigger]');
            if (!trigger) return;
            document.getElementById('decline-id').value = trigger.dataset.id;
            form.querySelector('[data-decline-summary]').textContent = trigger.dataset.summary || '';
            reason.value = '';
            sync();
            window.appUI.openModal('decline-modal');
        });

        sync();
    }

    // Approving moves stock and creates a loan, so it confirms — with the
    // consequence stated rather than "are you sure?".
    document.addEventListener('submit', function (event) {
        const approveForm = event.target.closest('.js-approve-form');
        if (!approveForm || approveForm.dataset.confirmed === '1') return;

        event.preventDefault();
        if (!window.showConfirm) { approveForm.submit(); return; }

        window.showConfirm({
            title: 'Approve this request?',
            text: 'Stock is deducted straight away and a loan is created, due back in 7 days.',
            icon: 'question',
            confirmText: 'Yes, approve',
        }).then(function (result) {
            if (result.isConfirmed) {
                approveForm.dataset.confirmed = '1';
                approveForm.submit();
            }
        });
    });
});
</script>
@endsection
