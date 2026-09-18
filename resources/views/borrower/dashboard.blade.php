@extends('components.default')
@section('title', 'Borrower - CICT Equipment Borrower System')
@section('content')

<div class="flex flex-col min-h-screen page-bg">
    <x-ui.page-header logo eyebrow="Borrower" title="Dashboard">
        <x-slot:actions>
            {{-- Notifications bell. Read-only feed of the rows written by the
                 return-reminder job; nothing here creates or mutates them. --}}
            <div class="relative">
                <button type="button" id="notif-btn"
                        class="relative inline-flex items-center justify-center w-11 h-11 bg-white border rounded-md text-neutral-700 border-neutral-300 hover:bg-neutral-50"
                        aria-haspopup="true" aria-expanded="false"
                        aria-label="Notifications ({{ $notifications->count() }})">
                    <i class="text-lg fas fa-bell"></i>
                    @if($notifications->isNotEmpty())
                        <span class="absolute -top-2 -right-2">
                            {{-- No class override: badge merges (not replaces) classes,
                                 and Tailwind resolves px-2 vs px-3 by CSS order, so an
                                 override here would silently lose. --}}
                            <x-ui.badge :status="$notifications->count()" variant="danger" />
                        </span>
                    @endif
                </button>

                <div id="notif-panel"
                     class="absolute right-0 z-50 hidden w-80 mt-2 overflow-hidden bg-white border rounded-lg shadow-flat border-neutral-200 sm:w-96">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-neutral-200 bg-neutral-50">
                        <p class="text-base font-semibold text-neutral-900">Notifications</p>
                        <button type="button" id="notif-close"
                                class="grid rounded-md w-9 h-9 place-items-center text-neutral-600 hover:bg-neutral-200 hover:text-neutral-900"
                                aria-label="Close notifications">
                            <i class="text-base fas fa-times"></i>
                        </button>
                    </div>

                    @if($notifications->isEmpty())
                        <div class="px-4 py-10 text-center">
                            <i class="block mb-3 text-3xl fas fa-bell-slash text-neutral-400"></i>
                            <p class="text-base font-semibold text-neutral-700">No notifications yet</p>
                            <p class="mt-1 text-sm text-neutral-600">Return reminders will show up here.</p>
                        </div>
                    @else
                        <ul class="overflow-y-auto divide-y max-h-80 divide-neutral-200">
                            @foreach($notifications as $notification)
                                <li class="px-4 py-3">
                                    <div class="flex items-start gap-3">
                                        <span class="grid border rounded-lg w-9 h-9 shrink-0 bg-primary-50 border-primary-100 place-items-center">
                                            <i class="text-sm fas fa-bell text-primary-600"></i>
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

            <button id="open-add-modal" type="button"
                    class="inline-flex items-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold text-white rounded-md bg-primary-600 hover:bg-primary-700">
                <i class="text-base fas fa-plus"></i> Request Item
            </button>
            <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden">@csrf</form>
            <button type="button" id="logout-btn"
                    class="inline-flex items-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold bg-white border rounded-md text-neutral-700 border-neutral-300 hover:bg-neutral-50">
                <i class="text-base fas fa-sign-out-alt"></i> Logout
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <main class="flex-1 w-full p-4 mx-auto space-y-6 sm:p-6 max-w-content">
        {{-- Top row: user card + stat cards share one responsive grid. --}}
        @php
            $activeBorrows = $transactions->whereIn('status', ['Borrowed', 'Overdue'])->count();
            $pendingRequests = $requests->where('status', 'Pending')->count();
            $overdueCount = $transactions->where('status', 'Overdue')->count();
        @endphp
        {{-- Signed-in user at a glance. Auth::user() is available in every view,
             so this needs nothing from the controller. --}}
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="flex flex-wrap items-center gap-4 p-5 bg-white border rounded-lg border-neutral-200">
                <div class="grid w-12 h-12 border rounded-lg shrink-0 bg-primary-50 border-primary-100 place-items-center">
                    <i class="text-lg fas fa-user text-primary-600"></i>
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-lg font-semibold truncate text-neutral-900" title="{{ Auth::user()->name }}">{{ Auth::user()->name }}</p>
                        <x-ui.badge :status="Auth::user()->user_type" variant="neutral" />
                    </div>
                    <p class="flex flex-wrap items-center mt-1 text-base gap-x-4 gap-y-1 text-neutral-600">
                        <span class="inline-flex items-center gap-2 min-w-0">
                            <i class="fas fa-envelope text-neutral-600 shrink-0"></i>
                            <span class="truncate" title="{{ Auth::user()->email }}">{{ Auth::user()->email }}</span>
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <i class="fas fa-phone text-neutral-600 shrink-0"></i>
                            {{ Auth::user()->contact_number ?: 'No contact number' }}
                        </span>
                    </p>
                </div>
            </div>
                <div class="flex items-center justify-between p-5 bg-white border rounded-lg border-neutral-200">
                    <div>
                        <p class="text-sm font-semibold tracking-wider uppercase text-neutral-600">Active borrows</p>
                        <p class="mt-1 text-3xl font-bold text-neutral-900 tabular-nums">{{ $activeBorrows }}</p>
                        <p class="mt-1 text-sm text-neutral-600">Borrowed or overdue</p>
                    </div>
                    <div class="grid w-12 h-12 border rounded-lg bg-primary-50 border-primary-100 place-items-center shrink-0">
                        <i class="text-lg text-primary-600 fas fa-exchange-alt"></i>
                    </div>
                </div>

                <div class="flex items-center justify-between p-5 bg-white border rounded-lg border-neutral-200">
                    <div>
                        <p class="text-sm font-semibold tracking-wider uppercase text-neutral-600">Pending requests</p>
                        <p class="mt-1 text-3xl font-bold text-neutral-900 tabular-nums">{{ $pendingRequests }}</p>
                        <p class="mt-1 text-sm text-neutral-600">Awaiting approval</p>
                    </div>
                    <div class="grid w-12 h-12 border rounded-lg bg-warning-50 border-warning-200 place-items-center shrink-0">
                        <i class="text-lg text-warning-700 fas fa-clock"></i>
                    </div>
                </div>

                <div class="flex items-center justify-between p-5 bg-white border rounded-lg {{ $overdueCount > 0 ? 'border-danger-200' : 'border-neutral-200' }}">
                    <div>
                        <p class="text-sm font-semibold tracking-wider uppercase text-neutral-600">Overdue</p>
                        <p class="mt-1 text-3xl font-bold tabular-nums {{ $overdueCount > 0 ? 'text-danger-700' : 'text-neutral-900' }}">{{ $overdueCount }}</p>
                        <p class="mt-1 text-sm text-neutral-600">Please return soon</p>
                    </div>
                    <div class="grid w-12 h-12 border rounded-lg bg-danger-50 border-danger-200 place-items-center shrink-0">
                        <i class="text-lg fas fa-triangle-exclamation text-danger-700"></i>
                    </div>
                </div>
        </section>

        {{-- Two-column on large screens, single column on mobile. Transactions
             lead as the primary column; requests and browsing sit beside them. --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2 min-w-0">
                <section>
                    <h2 class="flex items-center gap-2 mb-3 text-lg font-semibold text-neutral-900">
                        <span class="grid border rounded-lg w-9 h-9 bg-primary-50 border-primary-100 place-items-center">
                            <i class="text-base fas fa-history text-primary-600"></i>
                        </span>
                        My borrow transactions
                    </h2>
                    <x-ui.table-card>
                        @if($transactions->isEmpty())
                            <div class="py-12 text-center">
                                <i class="block mb-3 text-4xl fas fa-exchange-alt text-neutral-400"></i>
                                <p class="text-lg font-semibold text-neutral-700">No transactions yet</p>
                                <p class="mt-1 text-base text-neutral-600">Once your request is approved, your transactions will appear here.</p>
                            </div>
                        @else
                            {{-- Client-side status filter over the already-rendered rows. --}}
                            <div class="flex flex-wrap items-center gap-2 px-4 pt-4" role="group" aria-label="Filter transactions by status">
                                @foreach (['all' => 'All', 'Borrowed' => 'Borrowed', 'Returned' => 'Returned', 'Overdue' => 'Overdue'] as $value => $label)
                                    <button type="button"
                                            class="status-filter-btn inline-flex items-center min-h-[40px] px-4 py-2 text-sm font-semibold rounded-md border transition {{ $value === 'all' ? 'bg-primary-50 text-primary-700 border-primary-200' : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-100' }}"
                                            data-table="transactionTable" data-filter="{{ $value }}"
                                            aria-pressed="{{ $value === 'all' ? 'true' : 'false' }}">{{ $label }}</button>
                                @endforeach
                            </div>
                            <div class="p-4 overflow-x-auto">
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
                                            <tr class="{{ $isOverdueRow ? 'bg-danger-50 hover:bg-danger-100' : 'hover:bg-neutral-50' }}" data-status="{{ $tx->status }}">
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
                                                           class="inline-flex items-center gap-1.5 whitespace-nowrap min-h-[40px] px-4 py-2 text-sm font-semibold rounded-md bg-neutral-100 text-neutral-700 border border-neutral-200 hover:bg-neutral-200"
                                                           title="Open a printable borrow slip in a new tab">
                                                            <i class="text-sm fas fa-print"></i> Print
                                                        </a>
                                                        @if ($tx->status === 'Returned' && $tx->equipment)
                                                            <button type="button"
                                                                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md bg-neutral-100 text-neutral-700 border border-neutral-200 hover:bg-neutral-200"
                                                                    data-request-equipment="{{ $tx->equipment_id }}">
                                                                <i class="text-sm fas fa-rotate-right"></i> Borrow again
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
            <div class="space-y-6 lg:col-span-1 min-w-0">
                <section>
                    <h2 class="flex items-center gap-2 mb-3 text-lg font-semibold text-neutral-900">
                        <span class="grid border rounded-lg w-9 h-9 bg-primary-50 border-primary-100 place-items-center">
                            <i class="text-base fas fa-list text-primary-600"></i>
                        </span>
                        My Equipment Requests
                    </h2>
                    <x-ui.table-card>
                        @if($requests->isEmpty())
                            <div class="py-12 text-center">
                                <i class="block mb-3 text-4xl fas fa-inbox text-neutral-400"></i>
                                <p class="text-lg font-semibold text-neutral-700">No requests yet</p>
                                <p class="mt-1 text-base text-neutral-600">Click "Request Item" to submit one.</p>
                            </div>
                        @else
                            {{-- Client-side status filter over the already-rendered rows. --}}
                            <div class="flex flex-wrap items-center gap-2 px-4 pt-4" role="group" aria-label="Filter requests by status">
                                @foreach (['all' => 'All', 'Pending' => 'Pending', 'Approved' => 'Approved', 'Declined' => 'Declined'] as $value => $label)
                                    <button type="button"
                                            class="status-filter-btn inline-flex items-center min-h-[40px] px-4 py-2 text-sm font-semibold rounded-md border transition {{ $value === 'all' ? 'bg-primary-50 text-primary-700 border-primary-200' : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-100' }}"
                                            data-table="requestTable" data-filter="{{ $value }}"
                                            aria-pressed="{{ $value === 'all' ? 'true' : 'false' }}">{{ $label }}</button>
                                @endforeach
                            </div>
                            <div class="p-4 overflow-x-auto">
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
                                            <tr class="hover:bg-neutral-50" data-status="{{ $request->status }}">
                                                <td class="px-4 py-3 font-medium text-neutral-900">{{ $request->equipment->equipment_name }}</td>
                                                <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ $request->quantity }}</td>
                                                <td class="px-4 py-3">
                                                    @php $variant = ['Approved'=>'success','Declined'=>'danger','Pending'=>'warning'][$request->status] ?? 'neutral'; @endphp
                                                    <x-ui.badge :status="$request->status" :variant="$variant" />
                                                </td>
                                                <td class="px-4 py-3 text-neutral-600">{{ $request->remarks ?? '—' }}</td>
                                                <td class="px-4 py-3">
                                                    <button type="button"
                                                            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-md bg-neutral-100 text-neutral-700 border border-neutral-200 hover:bg-neutral-200 edit-btn"
                                                            data-id="{{ $request->id }}"
                                                            data-equipment-name="{{ $request->equipment->equipment_name }}"
                                                            data-quantity="{{ $request->quantity }}"
                                                            data-status="{{ $request->status }}"
                                                            data-remarks="{{ $request->remarks }}">
                                                        <i class="fas fa-edit text-sm"></i> Edit
                                                    </button>
                                                    <button type="button"
                                                            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-md bg-danger-50 text-danger-700 border border-danger-100 hover:bg-danger-100 delete-btn"
                                                            data-id="{{ $request->id }}"
                                                            data-equipment-name="{{ $request->equipment->equipment_name }}">
                                                        <i class="fas fa-trash text-sm"></i> Delete
                                                    </button>
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
                @php $availableEquipment = $equipments->where('status', 'Available')->where('available_quantity', '>', 0); @endphp
                <section>
                    <h2 class="flex items-center gap-2 mb-3 text-lg font-semibold text-neutral-900">
                        <span class="grid border rounded-lg w-9 h-9 bg-primary-50 border-primary-100 place-items-center">
                            <i class="text-base fas fa-tools text-primary-600"></i>
                        </span>
                        Browse Equipment
                    </h2>
                    <x-ui.table-card>
                        @if($availableEquipment->isEmpty())
                            <div class="py-12 text-center">
                                <i class="block mb-3 text-4xl fas fa-box-open text-neutral-400"></i>
                                <p class="text-lg font-semibold text-neutral-700">Nothing available right now</p>
                                <p class="mt-1 text-base text-neutral-600">Check back once items have been returned.</p>
                            </div>
                        @else
                            {{-- Two-up while this panel is full width; back to one-up at
                                 lg, where it sits in the narrow secondary column. --}}
                            <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-1">
                                @foreach ($availableEquipment as $item)
                                    <div class="flex items-center justify-between gap-3 p-4 border rounded-lg border-neutral-200 bg-neutral-50">
                                        <div class="min-w-0">
                                            <p class="text-base font-semibold truncate text-neutral-900" title="{{ $item->equipment_name }}">{{ $item->equipment_name }}</p>
                                            <p class="mt-1 text-sm text-neutral-600">
                                                Available: <span class="font-semibold tabular-nums">{{ $item->available_quantity }}</span>
                                            </p>
                                        </div>
                                        <button type="button"
                                                class="inline-flex items-center gap-1.5 shrink-0 min-h-[40px] px-4 py-2 text-sm font-semibold text-white rounded-md bg-primary-600 hover:bg-primary-700"
                                                data-request-equipment="{{ $item->id }}">
                                            <i class="text-sm fas fa-plus"></i> Request
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
            <h3 class="text-lg font-semibold text-neutral-900">Confirm Logout</h3>
            <p class="mt-1 text-base text-neutral-600">Are you sure you want to log out?</p>
        </div>
        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
            <button id="cancel-logout" type="button" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 text-base font-semibold bg-white border rounded-md text-neutral-700 border-neutral-300 hover:bg-neutral-50">Cancel</button>
            <button id="confirm-logout" type="button" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 text-base font-semibold text-white rounded-md bg-danger-600 hover:bg-danger-700">Logout</button>
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

    // "Request" (Browse Equipment) and "Borrow again" (Returned rows) both just
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
            b.classList.toggle('bg-primary-50', on);
            b.classList.toggle('text-primary-700', on);
            b.classList.toggle('border-primary-200', on);
            b.classList.toggle('bg-white', !on);
            b.classList.toggle('text-neutral-700', !on);
            b.classList.toggle('border-neutral-300', !on);
            b.classList.toggle('hover:bg-neutral-100', !on);
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

        if (e.target.closest('#open-add-modal')) {
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

    // Confirm delete
    const confirmDelete = document.getElementById('confirm-delete');
    if (confirmDelete) {
        confirmDelete.addEventListener('click', function () {
            const form = document.getElementById('delete-form');
            if (!form) return;
            if (window.showConfirm) {
                window.showConfirm({
                    title: 'Delete this request?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    confirmText: 'Yes, delete'
                }).then(function (r) { if (r.isConfirmed) form.submit(); });
            } else { form.submit(); }
        });
    }

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
