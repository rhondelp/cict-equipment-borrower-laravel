@extends('components.default')
@section('title', 'Borrower - CICT Equipment Borrower System')
@section('content')

@php
    $user = Auth::user();

    $activeBorrows   = $transactions->whereIn('status', ['Borrowed', 'Overdue'])->count();
    $pendingRequests = $requests->where('status', 'Pending')->count();
    $overdueCount    = $transactions->where('status', 'Overdue')->count();

    $hour     = (int) \Carbon\Carbon::now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

    // Initials for the avatar tile — nicer than a generic user glyph, and it
    // needs nothing from the controller. Falls back to a dash for a blank name.
    $initials = collect(preg_split('/\s+/', trim((string) $user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('') ?: '—';

    $availableEquipment = $equipments->where('status', 'Available')->where('available_quantity', '>', 0);
@endphp

{{-- 100dvh rather than 100vh: on mobile Safari the viewport unit shifts as the
     URL bar collapses, which makes a 100vh column jump during scroll. --}}
<div class="flex flex-col min-h-[100dvh] page-bg">

    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>

    {{-- :menu="false" — this page has no sidebar, so the header's hamburger has
         nothing to toggle. See the note in components/ui/page-header. --}}
    <x-ui.page-header logo :menu="false" eyebrow="Borrower" title="Dashboard">
        <x-slot:actions>
            {{-- Notifications bell. Read-only feed of the rows written by the
                 return-reminder job; nothing here creates or mutates them. --}}
            <div class="relative">
                <button type="button" id="notif-btn"
                        class="relative inline-flex items-center justify-center transition bg-white border rounded-md w-11 h-11 text-neutral-700 border-neutral-300 hover:bg-neutral-50 hover:border-neutral-400 active:translate-y-px"
                        aria-haspopup="true" aria-expanded="false"
                        aria-label="Notifications ({{ $notifications->count() }})">
                    <i class="text-lg fas fa-bell" aria-hidden="true"></i>
                    @if($notifications->isNotEmpty())
                        {{-- A purpose-built count chip rather than x-ui.badge: that
                             component merges classes instead of replacing them, so its
                             px-3 / text-sm pill could not be shrunk into a counter from
                             the call site without relying on CSS source order. --}}
                        <span class="absolute -top-1.5 -right-1.5 grid h-5 min-w-[1.25rem] place-items-center rounded-full bg-danger-600 px-1.5 text-[11px] font-bold text-white ring-2 ring-white tabular-nums">{{ $notifications->count() }}</span>
                    @endif
                </button>

                {{-- Width is clamped to the viewport so the panel cannot overflow the
                     screen edge on a narrow phone, where a flat w-80 would. --}}
                <div id="notif-panel"
                     class="absolute right-0 z-50 mt-2 hidden w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-flat sm:w-96">
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
                                    <div class="flex items-start gap-3">
                                        <span class="grid border rounded-lg w-9 h-9 shrink-0 bg-primary-50 border-primary-100 place-items-center">
                                            <i class="text-sm fas fa-bell text-primary-600" aria-hidden="true"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm leading-relaxed text-neutral-800">{{ $notification->message }}</p>
                                            <p class="mt-1 text-xs text-neutral-600">
                                                <span class="font-semibold">{{ $notification->notification_type }}</span>
                                                &middot; {{ \Carbon\Carbon::parse($notification->send_date ?? $notification->created_at)->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
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
            <button id="open-add-modal" type="button"
                    class="js-open-add-modal inline-flex min-h-[44px] min-w-[44px] items-center justify-center gap-2 rounded-md bg-primary-600 px-3 py-3 text-base font-semibold text-white transition hover:bg-primary-700 active:translate-y-px sm:px-5"
                    title="Request an item" aria-label="Request an item">
                <i class="text-base fas fa-plus" aria-hidden="true"></i>
                <span class="hidden sm:inline">Request Item</span>
            </button>
            <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden">@csrf</form>
            <button type="button" id="logout-btn"
                    class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center gap-2 rounded-md border border-neutral-300 bg-white px-3 py-3 text-base font-semibold text-neutral-700 transition hover:border-neutral-400 hover:bg-neutral-50 active:translate-y-px sm:px-5"
                    title="Log out" aria-label="Log out">
                <i class="text-base fas fa-right-from-bracket" aria-hidden="true"></i>
                <span class="hidden sm:inline">Logout</span>
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <main id="main-content" class="flex-1 w-full p-4 mx-auto space-y-5 sm:p-6 sm:space-y-6 max-w-content">

        {{-- Welcome band. Replaces the old user card, which sat in the same
             four-column grid as the stat tiles and so had to fit a name, role,
             email and phone number into a quarter of the row. --}}
        <section class="overflow-hidden border anim-rise rounded-xl border-primary-100 bg-gradient-to-br from-primary-50 via-white to-white">
            <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div class="flex items-center min-w-0 gap-4">
                    <div class="grid text-lg font-bold text-white border w-14 h-14 shrink-0 place-items-center rounded-2xl bg-primary-600 border-primary-700"
                         aria-hidden="true">{{ $initials }}</div>
                    <div class="min-w-0">
                        <p class="text-sm text-neutral-600">{{ $greeting }},</p>
                        <div class="flex flex-wrap items-center gap-2 mt-0.5">
                            <h2 class="text-xl font-semibold truncate sm:text-2xl text-neutral-900" title="{{ $user->name }}">{{ $user->name }}</h2>
                            <x-ui.badge :status="$user->user_type" variant="neutral" />
                        </div>
                    </div>
                </div>
                <dl class="flex flex-wrap items-center text-sm gap-x-6 gap-y-2 text-neutral-700 sm:justify-end">
                    <div class="flex items-center min-w-0 gap-2">
                        <dt class="sr-only">Email</dt>
                        <i class="fas fa-envelope text-primary-600 shrink-0" aria-hidden="true"></i>
                        <dd class="truncate" title="{{ $user->email }}">{{ $user->email }}</dd>
                    </div>
                    <div class="flex items-center gap-2">
                        <dt class="sr-only">Contact number</dt>
                        <i class="fas fa-phone text-primary-600 shrink-0" aria-hidden="true"></i>
                        <dd>{{ $user->contact_number ?: 'No contact number' }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        {{-- Three across at every width. Stacking these on mobile pushed the tables
             a full screen down; each tile drops its icon and caption under sm
             instead, so the row stays glanceable at 360px. The per-tile accent rule
             is what keeps the three from reading as one card repeated. --}}
        <section class="grid grid-cols-3 gap-3 anim-rise [animation-delay:70ms] sm:gap-4" aria-label="Summary">
            <div class="relative p-4 overflow-hidden transition bg-white border rounded-xl border-neutral-200 hover:border-primary-200 sm:p-5">
                <span class="absolute inset-x-0 top-0 h-1 bg-primary-500" aria-hidden="true"></span>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold tracking-wider uppercase sm:text-sm text-neutral-600">Active borrows</p>
                        <p class="mt-2 text-2xl font-bold sm:text-3xl text-neutral-900 tabular-nums">{{ $activeBorrows }}</p>
                        <p class="hidden mt-1 text-sm sm:block text-neutral-600">Borrowed or overdue</p>
                    </div>
                    <span class="hidden border rounded-lg w-11 h-11 shrink-0 bg-primary-50 border-primary-100 place-items-center sm:grid">
                        <i class="text-lg fas fa-right-left text-primary-600" aria-hidden="true"></i>
                    </span>
                </div>
            </div>

            <div class="relative p-4 overflow-hidden transition bg-white border rounded-xl border-neutral-200 hover:border-warning-300 sm:p-5">
                <span class="absolute inset-x-0 top-0 h-1 bg-warning-400" aria-hidden="true"></span>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold tracking-wider uppercase sm:text-sm text-neutral-600">Pending requests</p>
                        <p class="mt-2 text-2xl font-bold sm:text-3xl text-neutral-900 tabular-nums">{{ $pendingRequests }}</p>
                        <p class="hidden mt-1 text-sm sm:block text-neutral-600">Awaiting approval</p>
                    </div>
                    <span class="hidden border rounded-lg w-11 h-11 shrink-0 bg-warning-50 border-warning-200 place-items-center sm:grid">
                        <i class="text-lg fas fa-hourglass-half text-warning-700" aria-hidden="true"></i>
                    </span>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border p-4 transition sm:p-5 {{ $overdueCount > 0 ? 'border-danger-200 bg-danger-50/60' : 'border-neutral-200 bg-white hover:border-neutral-300' }}">
                <span class="absolute inset-x-0 top-0 h-1 {{ $overdueCount > 0 ? 'bg-danger-500' : 'bg-neutral-300' }}" aria-hidden="true"></span>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold tracking-wider uppercase sm:text-sm text-neutral-600">Overdue</p>
                        <p class="mt-2 text-2xl font-bold sm:text-3xl tabular-nums {{ $overdueCount > 0 ? 'text-danger-700' : 'text-neutral-900' }}">{{ $overdueCount }}</p>
                        <p class="hidden mt-1 text-sm sm:block text-neutral-600">Please return soon</p>
                    </div>
                    <span class="hidden w-11 h-11 shrink-0 place-items-center rounded-lg border sm:grid {{ $overdueCount > 0 ? 'bg-danger-100 border-danger-200' : 'bg-neutral-50 border-neutral-200' }}">
                        <i class="text-lg fas fa-triangle-exclamation {{ $overdueCount > 0 ? 'text-danger-700' : 'text-neutral-400' }}" aria-hidden="true"></i>
                    </span>
                </div>
            </div>
        </section>

        {{-- Two-column on large screens, single column on mobile. Transactions
             lead as the primary column; requests and browsing sit beside them.
             min-w-0 on both: grid children default to min-width:auto, without
             which the wide transactions table would push the page sideways
             instead of scrolling inside its own container. --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3 sm:gap-6">
            <div class="space-y-5 min-w-0 anim-rise [animation-delay:140ms] sm:space-y-6 lg:col-span-2">
                <section aria-labelledby="tx-heading">
                    <x-ui.table-card>
                        {{-- The section heading lives inside the card rather than
                             floating above it: one surface instead of two, and it
                             makes room for the row count on the right. --}}
                        <div class="flex items-center justify-between gap-3 px-4 py-4 border-b sm:px-5 border-neutral-200">
                            <h2 id="tx-heading" class="flex items-center min-w-0 gap-2.5 text-base font-semibold text-neutral-900 sm:text-lg">
                                <span class="grid border rounded-lg w-9 h-9 shrink-0 bg-primary-50 border-primary-100 place-items-center">
                                    <i class="text-sm fas fa-clock-rotate-left text-primary-600" aria-hidden="true"></i>
                                </span>
                                <span class="truncate">My borrow transactions</span>
                            </h2>
                            <span class="shrink-0 rounded-full bg-neutral-100 px-2.5 py-1 text-sm font-semibold text-neutral-700 tabular-nums">{{ $transactions->count() }}</span>
                        </div>

                        @if($transactions->isEmpty())
                            <x-ui.empty-state icon="fa-clock-rotate-left" title="No transactions yet"
                                              message="Once an admin approves one of your requests, the borrow record shows up here." />
                        @else
                            {{-- Client-side status filter over the already-rendered rows. --}}
                            <div class="flex flex-wrap items-center gap-2 px-4 pt-4 sm:px-5" role="group" aria-label="Filter transactions by status">
                                @foreach (['all' => 'All', 'Borrowed' => 'Borrowed', 'Returned' => 'Returned', 'Overdue' => 'Overdue'] as $value => $label)
                                    <button type="button"
                                            class="status-filter-btn inline-flex min-h-[36px] items-center rounded-full border px-3.5 py-1.5 text-sm font-semibold transition {{ $value === 'all' ? 'bg-primary-600 text-white border-primary-600 hover:bg-primary-700' : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-50' }}"
                                            data-table="transactionTable" data-filter="{{ $value }}"
                                            aria-pressed="{{ $value === 'all' ? 'true' : 'false' }}">{{ $label }}</button>
                                @endforeach
                            </div>
                            <div class="p-4 overflow-x-auto sm:p-5">
                                <table id="transactionTable" class="w-full text-sm display nowrap">
                                    <thead>
                                        <tr class="text-sm tracking-wider uppercase text-neutral-600 bg-neutral-50">
                                            <th class="px-4 py-3 font-semibold text-left">Equipment</th>
                                            <th class="px-4 py-3 font-semibold text-left">Qty</th>
                                            <th class="px-4 py-3 font-semibold text-left">Borrow</th>
                                            <th class="px-4 py-3 font-semibold text-left">Return</th>
                                            <th class="px-4 py-3 font-semibold text-left">Purpose</th>
                                            <th class="px-4 py-3 font-semibold text-left">Status</th>
                                            <th class="px-4 py-3 font-semibold text-left">Remarks</th>
                                            <th class="px-4 py-3 font-semibold text-left">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-neutral-200">
                                        @foreach ($transactions as $tx)
                                            @php
                                                // Overdue in practice: either already swept to Overdue by the
                                                // nightly job, or still Borrowed with the return date behind us.
                                                $isOverdueRow = $tx->status === 'Overdue'
                                                    || ($tx->status === 'Borrowed' && $tx->return_date
                                                        && \Carbon\Carbon::parse($tx->return_date)->lt(\Carbon\Carbon::today()));
                                            @endphp
                                            <tr class="transition {{ $isOverdueRow ? 'bg-danger-50 hover:bg-danger-100' : 'hover:bg-neutral-50' }}" data-status="{{ $tx->status }}">
                                                <td class="px-4 py-3 font-medium text-neutral-900">{{ $tx->equipment->equipment_name ?? '—' }}</td>
                                                <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ $tx->quantity }}</td>
                                                <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ $tx->borrow_date }}</td>
                                                <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ $tx->return_date ?? '—' }}</td>
                                                <td class="px-4 py-3 text-neutral-600 max-w-[14rem] truncate" title="{{ $tx->purpose }}">{{ $tx->purpose }}</td>
                                                <td class="px-4 py-3">
                                                    @php $variant = ['Borrowed'=>'warning','Returned'=>'success','Overdue'=>'danger'][$tx->status] ?? 'neutral'; @endphp
                                                    <x-ui.badge :status="$tx->status" :variant="$variant" />
                                                </td>
                                                <td class="px-4 py-3 text-neutral-600">{{ $tx->remarks ?? '—' }}</td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        <a href="{{ route('borrower.transaction.receipt', $tx->id) }}"
                                                           target="_blank" rel="noopener"
                                                           class="inline-flex min-h-[40px] items-center gap-1.5 whitespace-nowrap rounded-md border border-neutral-300 bg-white px-3.5 py-2 text-sm font-semibold text-neutral-700 transition hover:border-neutral-400 hover:bg-neutral-50 active:translate-y-px"
                                                           title="Open a printable borrow slip in a new tab">
                                                            <i class="text-sm fas fa-print" aria-hidden="true"></i> Print
                                                        </a>
                                                        @if ($tx->status === 'Returned' && $tx->equipment)
                                                            <button type="button"
                                                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md border border-primary-200 bg-primary-50 text-primary-700 transition hover:bg-primary-100 active:translate-y-px"
                                                                    data-request-equipment="{{ $tx->equipment_id }}">
                                                                <i class="text-sm fas fa-rotate-right" aria-hidden="true"></i> Borrow again
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </x-ui.table-card>
                </section>
            </div>

            <div class="space-y-5 min-w-0 anim-rise [animation-delay:210ms] sm:space-y-6 lg:col-span-1">
                <section aria-labelledby="req-heading">
                    <x-ui.table-card>
                        <div class="flex items-center justify-between gap-3 px-4 py-4 border-b sm:px-5 border-neutral-200">
                            <h2 id="req-heading" class="flex items-center min-w-0 gap-2.5 text-base font-semibold text-neutral-900 sm:text-lg">
                                <span class="grid border rounded-lg w-9 h-9 shrink-0 bg-primary-50 border-primary-100 place-items-center">
                                    <i class="text-sm fas fa-list-check text-primary-600" aria-hidden="true"></i>
                                </span>
                                <span class="truncate">My equipment requests</span>
                            </h2>
                            <span class="shrink-0 rounded-full bg-neutral-100 px-2.5 py-1 text-sm font-semibold text-neutral-700 tabular-nums">{{ $requests->count() }}</span>
                        </div>

                        @if($requests->isEmpty())
                            <x-ui.empty-state icon="fa-inbox" title="No requests yet"
                                              message="Pick something from the equipment list, or start a request from here.">
                                <x-slot:action>
                                    <button type="button" class="btn-primary js-open-add-modal">
                                        <i class="text-base fas fa-plus" aria-hidden="true"></i> Request item
                                    </button>
                                </x-slot:action>
                            </x-ui.empty-state>
                        @else
                            {{-- Client-side status filter over the already-rendered rows. --}}
                            <div class="flex flex-wrap items-center gap-2 px-4 pt-4 sm:px-5" role="group" aria-label="Filter requests by status">
                                @foreach (['all' => 'All', 'Pending' => 'Pending', 'Approved' => 'Approved', 'Declined' => 'Declined'] as $value => $label)
                                    <button type="button"
                                            class="status-filter-btn inline-flex min-h-[36px] items-center rounded-full border px-3.5 py-1.5 text-sm font-semibold transition {{ $value === 'all' ? 'bg-primary-600 text-white border-primary-600 hover:bg-primary-700' : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-50' }}"
                                            data-table="requestTable" data-filter="{{ $value }}"
                                            aria-pressed="{{ $value === 'all' ? 'true' : 'false' }}">{{ $label }}</button>
                                @endforeach
                            </div>
                            <div class="p-4 overflow-x-auto sm:p-5">
                                <table id="requestTable" class="w-full text-sm display nowrap">
                                    <thead>
                                        <tr class="text-sm tracking-wider uppercase text-neutral-600 bg-neutral-50">
                                            <th class="px-4 py-3 font-semibold text-left">Equipment</th>
                                            <th class="px-4 py-3 font-semibold text-left">Qty</th>
                                            <th class="px-4 py-3 font-semibold text-left">Status</th>
                                            <th class="px-4 py-3 font-semibold text-left">Remarks</th>
                                            <th class="px-4 py-3 font-semibold text-left">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-neutral-200">
                                        @foreach ($requests as $request)
                                            <tr class="transition hover:bg-neutral-50" data-status="{{ $request->status }}">
                                                <td class="px-4 py-3 font-medium text-neutral-900">{{ $request->equipment->equipment_name }}</td>
                                                <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ $request->quantity }}</td>
                                                <td class="px-4 py-3">
                                                    @php $variant = ['Approved'=>'success','Declined'=>'danger','Pending'=>'warning'][$request->status] ?? 'neutral'; @endphp
                                                    <x-ui.badge :status="$request->status" :variant="$variant" />
                                                </td>
                                                <td class="px-4 py-3 text-neutral-600">{{ $request->remarks ?? '—' }}</td>
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        <button type="button"
                                                                class="edit-btn inline-flex items-center gap-1.5 rounded-md border border-neutral-300 bg-white text-neutral-700 transition hover:border-neutral-400 hover:bg-neutral-50 active:translate-y-px"
                                                                data-id="{{ $request->id }}"
                                                                data-equipment-name="{{ $request->equipment->equipment_name }}"
                                                                data-quantity="{{ $request->quantity }}"
                                                                data-status="{{ $request->status }}"
                                                                data-remarks="{{ $request->remarks }}">
                                                            <i class="text-sm fas fa-pen" aria-hidden="true"></i> Edit
                                                        </button>
                                                        <button type="button"
                                                                class="delete-btn inline-flex items-center gap-1.5 rounded-md border border-danger-200 bg-danger-50 text-danger-700 transition hover:bg-danger-100 active:translate-y-px"
                                                                data-id="{{ $request->id }}"
                                                                data-equipment-name="{{ $request->equipment->equipment_name }}">
                                                            <i class="text-sm fas fa-trash" aria-hidden="true"></i> Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </x-ui.table-card>
                </section>

                {{-- Browse what's on the shelf. Each Request button only pre-fills the
                     existing request modal — same form, same submission target. --}}
                <section aria-labelledby="browse-heading">
                    <x-ui.table-card>
                        <div class="flex items-center justify-between gap-3 px-4 py-4 border-b sm:px-5 border-neutral-200">
                            <h2 id="browse-heading" class="flex items-center min-w-0 gap-2.5 text-base font-semibold text-neutral-900 sm:text-lg">
                                <span class="grid border rounded-lg w-9 h-9 shrink-0 bg-primary-50 border-primary-100 place-items-center">
                                    <i class="text-sm fas fa-boxes-stacked text-primary-600" aria-hidden="true"></i>
                                </span>
                                <span class="truncate">Browse equipment</span>
                            </h2>
                            <span class="shrink-0 rounded-full bg-neutral-100 px-2.5 py-1 text-sm font-semibold text-neutral-700 tabular-nums">{{ $availableEquipment->count() }}</span>
                        </div>

                        @if($availableEquipment->isEmpty())
                            <x-ui.empty-state icon="fa-box-open" title="Nothing available right now"
                                              message="Check back once items have been returned." />
                        @else
                            {{-- Two-up while this panel is full width; back to one-up at lg,
                                 where it moves into the narrow secondary column and gets a
                                 scroll cap so a large inventory cannot run the column on far
                                 past the transactions table beside it. --}}
                            <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 sm:p-5 lg:max-h-[30rem] lg:grid-cols-1 lg:overflow-y-auto">
                                @foreach ($availableEquipment as $item)
                                    <div class="flex items-center justify-between gap-3 p-4 transition rounded-lg bg-neutral-50 hover:bg-primary-50">
                                        <div class="min-w-0">
                                            <p class="text-base font-semibold truncate text-neutral-900" title="{{ $item->equipment_name }}">{{ $item->equipment_name }}</p>
                                            <p class="mt-1 text-sm text-neutral-600">
                                                <span class="font-semibold text-success-700 tabular-nums">{{ $item->available_quantity }}</span> available
                                            </p>
                                        </div>
                                        <button type="button"
                                                class="inline-flex min-h-[40px] shrink-0 items-center gap-1.5 rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700 active:translate-y-px"
                                                data-request-equipment="{{ $item->id }}"
                                                aria-label="Request {{ $item->equipment_name }}">
                                            <i class="text-sm fas fa-plus" aria-hidden="true"></i> Request
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </x-ui.table-card>
                </section>
            </div>
        </div>
    </main>
</div>

{{-- Logout confirm modal --}}
<div id="logout-modal" class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-neutral-900/50">
    <div class="flex flex-col w-full max-w-sm bg-white border border-neutral-200 rounded-xl shadow-flat">
        <div class="px-6 py-5">
            <div class="flex items-start gap-3">
                <span class="grid border rounded-lg w-11 h-11 shrink-0 bg-danger-50 border-danger-200 place-items-center">
                    <i class="text-lg fas fa-right-from-bracket text-danger-700" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h3 class="text-lg font-semibold text-neutral-900">Log out</h3>
                    <p class="mt-1 text-base text-neutral-600">You will need to sign in again to reach your dashboard.</p>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
            <button id="cancel-logout" type="button" class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 transition hover:bg-neutral-50 active:translate-y-px">Cancel</button>
            <button id="confirm-logout" type="button" class="inline-flex min-h-[44px] items-center justify-center rounded-md bg-danger-600 px-5 py-3 text-base font-semibold text-white transition hover:bg-danger-700 active:translate-y-px">Log out</button>
        </div>
    </div>
</div>

@include('components.instructor.request-item-modal')
@include('components.instructor.update-request-modal')
@include('components.instructor.delete-request-modal')

<script>
document.addEventListener('DOMContentLoaded', function () {
    ['requestTable', 'transactionTable'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el && window.initAppTable) {
            try {
                window.initAppTable('#' + id, {
                    language: { search: '', searchPlaceholder: 'Search...' }
                });
            } catch (e) { console.error('DataTable init failed (' + id + ')', e); }
        }
    });

    // Status filters over the already-rendered rows — no request, no reload.
    // These hook DataTables' own search pipeline rather than hiding <tr>s directly,
    // so paging and the "showing N entries" count stay correct and survive redraws.
    // If DataTables is unavailable the buttons fall back to plain show/hide.
    const statusFilter = { requestTable: 'all', transactionTable: 'all' };
    const hasDT = !!(window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable);

    if (hasDT) {
        window.jQuery.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            const wanted = statusFilter[settings.nTable.id];
            if (!wanted || wanted === 'all') return true;
            const row = settings.aoData[dataIndex].nTr;
            return !!row && row.getAttribute('data-status') === wanted;
        });
    }

    // Notifications panel — toggle, close button, outside click and Escape.
    // No stopPropagation: the outside-click check simply ignores the bell itself,
    // so the page's other document-level click handlers still see every event.
    const notifBtn = document.getElementById('notif-btn');
    const notifPanel = document.getElementById('notif-panel');
    if (notifBtn && notifPanel) {
        const setNotifOpen = function (open) {
            notifPanel.classList.toggle('hidden', !open);
            notifBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        notifBtn.addEventListener('click', function () {
            setNotifOpen(notifPanel.classList.contains('hidden'));
        });

        document.getElementById('notif-close')?.addEventListener('click', function () {
            setNotifOpen(false);
        });

        document.addEventListener('click', function (e) {
            if (!notifBtn.contains(e.target) && !notifPanel.contains(e.target)) setNotifOpen(false);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') setNotifOpen(false);
        });
    }

    // "Request" (Browse equipment) and "Borrow again" (Returned rows) both just
    // pre-select an item in the existing request modal and open it. Nothing about
    // what the form submits, or where, changes.
    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-request-equipment]');
        if (!trigger) return;

        const id = trigger.getAttribute('data-request-equipment');
        const select = document.getElementById('add-equipment');
        if (select) {
            select.value = id;
            // If that equipment is no longer an option, fall back to the
            // placeholder rather than silently selecting the wrong item.
            if (select.value !== String(id)) select.selectedIndex = 0;
        }
        const qty = document.getElementById('add-quantity');
        if (qty && !qty.value) qty.value = 1;

        const modal = document.getElementById('add-modal');
        if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); }
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.status-filter-btn');
        if (!btn) return;

        const tableId = btn.dataset.table;
        statusFilter[tableId] = btn.dataset.filter;

        document.querySelectorAll('.status-filter-btn[data-table="' + tableId + '"]').forEach(function (b) {
            const on = b === btn;
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
            b.classList.toggle('bg-primary-600', on);
            b.classList.toggle('text-white', on);
            b.classList.toggle('border-primary-600', on);
            b.classList.toggle('hover:bg-primary-700', on);
            b.classList.toggle('bg-white', !on);
            b.classList.toggle('text-neutral-700', !on);
            b.classList.toggle('border-neutral-300', !on);
            b.classList.toggle('hover:bg-neutral-50', !on);
        });

        const table = document.getElementById(tableId);
        if (!table) return;

        if (hasDT && window.jQuery.fn.dataTable.isDataTable(table)) {
            window.jQuery(table).DataTable().draw();
        } else {
            table.querySelectorAll('tbody tr').forEach(function (row) {
                const show = statusFilter[tableId] === 'all'
                    || row.getAttribute('data-status') === statusFilter[tableId];
                row.classList.toggle('hidden', !show);
            });
        }
    });

    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            const d = editBtn.dataset;
            const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
            set('edit-id', d.id);
            const nameEl = document.getElementById('edit-equipment-name'); if (nameEl) nameEl.textContent = d.equipmentName;
            set('edit-quantity', d.quantity);
            set('edit-remarks', d.remarks);
            const m = document.getElementById('edit-modal');
            if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const d = deleteBtn.dataset;
            const nameEl = document.getElementById('delete-item-name'); if (nameEl) nameEl.textContent = d.equipmentName;
            const form = document.getElementById('delete-form'); if (form) form.action = '/borrower/request/' + d.id;
            const m = document.getElementById('delete-modal');
            if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
        }

        // Class hook rather than the id: the empty-requests state offers a second
        // "Request Item" button, and two elements cannot share one id.
        if (e.target.closest('.js-open-add-modal')) {
            const m = document.getElementById('add-modal');
            if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
        }
        if (e.target.closest('.cancel-add') || e.target.closest('#cancel-add')) {
            const m = document.getElementById('add-modal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
        }
        if (e.target.closest('.cancel-edit') || e.target.closest('#cancel-edit')) {
            const m = document.getElementById('edit-modal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
        }
        if (e.target.closest('#cancel-delete') || e.target.closest('.cancel-delete')) {
            const m = document.getElementById('delete-modal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
        }
    });

    // The delete modal is itself the confirmation step: its "Confirm Delete"
    // button is a plain type="submit" inside #delete-form. There is no
    // #confirm-delete element in components/instructor/delete-request-modal, so
    // the SweetAlert handler that used to sit here never bound to anything.

    // Logout flow
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            const m = document.getElementById('logout-modal');
            if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
        });
    }
    const cancelLogout = document.getElementById('cancel-logout');
    if (cancelLogout) {
        cancelLogout.addEventListener('click', function () {
            const m = document.getElementById('logout-modal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
        });
    }
    const confirmLogout = document.getElementById('confirm-logout');
    if (confirmLogout) {
        confirmLogout.addEventListener('click', function () {
            const form = document.getElementById('logout-form');
            if (form) form.submit();
        });
    }

    ['add-modal', 'edit-modal', 'delete-modal', 'logout-modal'].forEach(function (id) {
        const m = document.getElementById(id);
        if (m) m.addEventListener('click', function (e) { if (e.target === m) { m.classList.add('hidden'); m.classList.remove('flex'); } });
    });
});
</script>
@endsection
