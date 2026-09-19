@extends('components.default')
@section('title', 'Borrow Transactions - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

<div class="min-h-[100dvh] page-bg md:ml-64">

    {{-- Essential for keyboard users: the sidebar is six links deep, so without
         this every page begins with six tab stops before the content. --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>
    <x-ui.page-header eyebrow="Transactions" title="Borrow & returns">
        <x-slot:actions>
            <button id="open-add-modal" type="button"
                    class="inline-flex items-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold text-white rounded-md bg-primary-600 hover:bg-primary-700">
                <i class="text-base fas fa-plus"></i> Add Transaction
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <main id="main-content" class="p-4 mx-auto space-y-5 sm:p-6 max-w-content">
        <x-ui.table-card>
            @if($transactions->isEmpty())
                <x-ui.empty-state icon="fa-right-left" title="No transactions yet"
                                  message="Approving a request creates one automatically, or log a borrow by hand.">
                    <x-slot:action>
                        <button type="button" class="btn-primary js-open-add-modal">
                            <i class="text-base fas fa-plus" aria-hidden="true"></i> Add transaction
                        </button>
                    </x-slot:action>
                </x-ui.empty-state>
            @else
                <div class="p-4 overflow-x-auto">
                    <table id="transactions-table" class="w-full text-sm display nowrap">
                        <thead>
                            <tr class="text-sm tracking-wider uppercase text-neutral-600 bg-neutral-50">
                                <th class="px-4 py-3 font-semibold text-left">User</th>
                                <th class="px-4 py-3 font-semibold text-left">Equipment</th>
                                <th class="px-4 py-3 font-semibold text-left">Borrow date</th>
                                <th class="px-4 py-3 font-semibold text-left">Return date</th>
                                <th class="px-4 py-3 font-semibold text-left">Qty</th>
                                <th class="px-4 py-3 font-semibold text-left">Purpose</th>
                                <th class="px-4 py-3 font-semibold text-left">Status</th>
                                <th class="px-4 py-3 font-semibold text-left">Remarks</th>
                                <th class="px-4 py-3 font-semibold text-left">Class sched</th>
                                <th class="px-4 py-3 font-semibold text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @foreach ($transactions as $tx)
                                @php
                                    $returnDate = $tx->return_date ? \Carbon\Carbon::parse($tx->return_date)->format('Y-m-d') : null;
                                    $isDueToday = $returnDate === now()->format('Y-m-d');
                                @endphp
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-4 py-3 font-medium text-neutral-900">{{ $tx->user->name ?? 'Deleted User' }}</td>
                                    <td class="px-4 py-3 text-neutral-700">{{ $tx->equipment->equipment_name ?? 'Deleted Equipment' }}</td>
                                    <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ \Carbon\Carbon::parse($tx->borrow_date)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-neutral-700 tabular-nums {{ $isDueToday ? 'text-danger-700 font-semibold' : '' }}">{{ $returnDate ?? '—' }}</td>
                                    <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ $tx->quantity }}</td>
                                    <td class="px-4 py-3 max-w-[14rem] truncate text-neutral-600" title="{{ $tx->purpose }}">{{ $tx->purpose }}</td>
                                    <td class="px-4 py-3">
                                        <select class="status-dropdown inline-flex px-4 py-2 text-sm font-semibold rounded-md border focus:outline-none focus:ring-2 focus:ring-primary-500/30
                                            @if($tx->status === 'Borrowed') bg-warning-50 text-warning-700 border-warning-200 @endif
                                            @if($tx->status === 'Returned') bg-success-50 text-success-700 border-success-200 @endif
                                            @if($tx->status === 'Overdue') bg-danger-50 text-danger-700 border-danger-200 @endif"
                                                data-id="{{ $tx->id }}">
                                            <option value="Borrowed" {{ $tx->status === 'Borrowed' ? 'selected' : '' }}>Borrowed</option>
                                            <option value="Returned" {{ $tx->status === 'Returned' ? 'selected' : '' }}>Returned</option>
                                            <option value="Overdue" {{ $tx->status === 'Overdue' ? 'selected' : '' }}>Overdue</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 text-neutral-600 max-w-[14rem] truncate" title="{{ $tx->remarks }}">{{ $tx->remarks ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-neutral-700">
                                        @if ($tx->classSchedule)
                                            {{ $tx->classSchedule->schedule_time }} - {{ $tx->classSchedule->instructor?->name ?? 'No Instructor' }} - {{ $tx->classSchedule->room }}
                                        @else
                                            <span class="text-neutral-500">No Schedule</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5">
                                            <button type="button"
                                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-md bg-neutral-100 text-neutral-700 border border-neutral-200 hover:bg-neutral-200 edit-btn"
                                                    data-id="{{ $tx->id }}" data-user="{{ $tx->user->id ?? '' }}"
                                                    data-equipment="{{ $tx->equipment->id ?? '' }}"
                                                    data-borrow="{{ \Carbon\Carbon::parse($tx->borrow_date)->format('Y-m-d') }}"
                                                    data-return="{{ $returnDate }}" data-quantity="{{ $tx->quantity }}"
                                                    data-purpose="{{ $tx->purpose }}" data-status="{{ $tx->status }}"
                                                    data-remarks="{{ $tx->remarks ?? '' }}"
                                                    data-class="{{ $tx->classSchedule->id ?? '' }}">
                                                <i class="fas fa-edit text-sm"></i> Edit
                                            </button>
                                            <button type="button"
                                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-md bg-neutral-100 text-neutral-700 border border-neutral-200 hover:bg-neutral-200 send-email-btn"
                                                    data-id="{{ $tx->id }}" data-user-email="{{ $tx->user->email ?? '' }}">
                                                <i class="fas fa-envelope text-sm"></i> Email
                                            </button>
                                            <button type="button"
                                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-md bg-danger-50 text-danger-700 border border-danger-100 hover:bg-danger-100 delete-btn"
                                                    data-id="{{ $tx->id }}" data-name="transaction #{{ $tx->id }}">
                                                <i class="fas fa-trash text-sm"></i>
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
    </main>
</div>

@include('components.admin.transaction.email-modal')
@include('components.admin.transaction.add-modal')
@include('components.admin.transaction.edit-modal')
@include('components.admin.transaction.delete-modal')
@include('components.admin.transaction.returnlog-modal')

<script>
document.addEventListener('DOMContentLoaded', function () {
    // DataTable
    const tableEl = document.getElementById('transactions-table');
    if (tableEl && window.initAppTable) {
        try {
            window.initAppTable('#transactions-table', {
                responsive: true,
                columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }],
                language: { search: '', searchPlaceholder: 'Search transactions...' }
            });
        } catch (e) { console.error('DataTable init failed (transactions-table)', e); }
    }

    // Equipment picker for the add modal: searchable checklist, with a quantity
    // field revealed per ticked row. The quantity inputs already carry their
    // quantities[<id>] name in the markup and are disabled until ticked, so the
    // submitted payload is identical to the old multi-select.
    const equipmentList = document.getElementById('equipment-list');
    if (equipmentList) {
        const setQtyMessage = function (qtyInput, text) {
            const wrap = qtyInput.closest('.equipment-qty-wrap');
            const msg = wrap ? wrap.querySelector('.equipment-qty-msg') : null;
            if (!msg) return;
            msg.textContent = text || '';
            msg.classList.toggle('hidden', !text);
            qtyInput.classList.toggle('border-danger-300', !!text);
        };

        // Advisory only — BorrowTransactionController::store re-checks stock
        // under a row lock and remains the source of truth.
        const validateQty = function (qtyInput) {
            const max = parseInt(qtyInput.getAttribute('max'), 10);
            const val = parseInt(qtyInput.value, 10);
            if (!isNaN(max) && !isNaN(val) && val > max) {
                setQtyMessage(qtyInput, 'Only ' + max + ' available right now — please lower the quantity.');
            } else {
                setQtyMessage(qtyInput, '');
            }
        };

        const syncRow = function (checkbox) {
            const row = checkbox.closest('.equipment-option');
            if (!row) return;
            const wrap = row.querySelector('.equipment-qty-wrap');
            const qty = row.querySelector('.equipment-qty');
            if (!wrap || !qty) return;
            if (checkbox.checked) {
                wrap.classList.remove('hidden');
                qty.disabled = false;
                if (!qty.value) qty.value = 1;
                validateQty(qty);
            } else {
                wrap.classList.add('hidden');
                qty.disabled = true;
                setQtyMessage(qty, '');
            }
        };

        equipmentList.addEventListener('change', function (e) {
            const cb = e.target.closest('.equipment-checkbox');
            if (cb) syncRow(cb);

            const qty = e.target.closest('.equipment-qty');
            if (qty) {
                // Clamp once the value is committed, so typing is never fought.
                const max = parseInt(qty.getAttribute('max'), 10);
                const val = parseInt(qty.value, 10);
                if (!isNaN(max) && !isNaN(val) && val > max) qty.value = max;
                validateQty(qty);
            }
        });

        equipmentList.addEventListener('input', function (e) {
            const qty = e.target.closest('.equipment-qty');
            if (qty) validateQty(qty);
        });

        // Client-side search filter only — no request is made.
        const equipmentSearch = document.getElementById('equipment-search');
        const noMatch = document.getElementById('equipment-no-match');
        if (equipmentSearch) {
            equipmentSearch.addEventListener('input', function () {
                const term = this.value.trim().toLowerCase();
                let matched = 0;
                equipmentList.querySelectorAll('.equipment-option').forEach(function (row) {
                    const matches = !term || (row.dataset.name || '').indexOf(term) !== -1;
                    const ticked = !!row.querySelector('.equipment-checkbox:checked');
                    // Ticked rows stay visible so nothing is submitted while hidden.
                    row.classList.toggle('hidden', !(matches || ticked));
                    if (matches) matched++;
                });
                // Counts matches, not visible rows: a ticked row that is being kept
                // on screen should not suppress the "nothing found" notice.
                if (noMatch) noMatch.classList.toggle('hidden', matched > 0);
            });
        }
    }

    // Edit / Delete / Add modal open/close
    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            const d = editBtn.dataset;
            const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
            set('edit-id', d.id);
            set('edit-user', d.user);
            set('edit-equipment', d.equipment);
            set('edit-borrow', d.borrow);
            set('edit-return', d.return);
            set('edit-quantity', d.quantity);
            set('edit-purpose', d.purpose);
            set('edit-status', d.status);
            set('edit-remarks', d.remarks);
            set('edit-class', d.class);
            const m = document.getElementById('edit-modal');
            if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const d = deleteBtn.dataset;
            const nameEl = document.getElementById('delete-item-name');
            const form = document.getElementById('delete-form');
            if (nameEl) nameEl.textContent = d.name;
            if (form) form.action = '/admin/transaction/' + d.id;
            const m = document.getElementById('delete-modal');
            if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
        }

        if (e.target.closest('#open-add-modal') || e.target.closest('.js-open-add-modal')) {
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
        if (e.target.closest('#cancel-delete')) {
            const m = document.getElementById('delete-modal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
        }
        if (e.target.closest('#cancelReturn') || e.target.closest('#cancelReturn-x')) {
            const m = document.getElementById('returnLogModal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
        }
        if (e.target.closest('#closeEmailModal') || e.target.closest('#closeEmailModal-x')) {
            const m = document.getElementById('emailModal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
        }
    });

    // Confirm delete -> showConfirm() then submit
    const transactionDeleteForm = document.getElementById('delete-form');
    if (transactionDeleteForm) {
        transactionDeleteForm.addEventListener('submit', function (e) {
            // Native submit; SweetAlert2 isn't used here because the user
            // already clicked Cancel or Delete inside the modal.
        });
    }

    // Status dropdown -> return-log modal or AJAX update
    document.addEventListener('change', function (e) {
        if (e.target.classList && e.target.classList.contains('status-dropdown')) {
            const status = e.target.value;
            const id = e.target.dataset.id;
            if (status === 'Returned') {
                const idEl = document.getElementById('return-transaction-id');
                if (idEl) idEl.value = id;
                const m = document.getElementById('returnLogModal');
                if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
            } else {
                updateStatus(id, status);
            }
        }
    });

    // Return-log form submit
    const returnLogForm = document.getElementById('returnLogForm');
    if (returnLogForm) {
        returnLogForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const id = document.getElementById('return-transaction-id').value;
            const condition = document.getElementById('return-condition').value;
            const remarks = document.getElementById('return-remarks').value;
            updateStatus(id, 'Returned', condition, remarks);
            const m = document.getElementById('returnLogModal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
        });
    }

    function updateStatus(id, status, condition, remarks) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.value;
        const body = new URLSearchParams();
        body.set('id', id);
        body.set('status', status);
        if (condition) body.set('condition', condition);
        if (remarks !== undefined && remarks !== null) body.set('remarks', remarks);

        fetch('{{ route('transactions.inlineUpdate') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrf || '',
                'Accept': 'application/json',
            },
            body: body.toString(),
            credentials: 'same-origin',
        })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
        .then(function (res) {
            if (!res.ok) throw new Error((res.data && res.data.message) || 'Update failed');
            if (window.showAlert) {
                window.showAlert('success', (res.data && res.data.message) || 'Status updated successfully!');
            }
            setTimeout(function () { location.reload(); }, 900);
        })
        .catch(function (err) {
            if (window.showAlert) window.showAlert('error', err.message || 'Something went wrong.');
        });
    }

    // Email modal
    let selectedTransactionId = null;
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.send-email-btn');
        if (btn) {
            selectedTransactionId = btn.getAttribute('data-id');
            const userEmail = btn.getAttribute('data-user-email');
            const modalEmail = document.getElementById('modalEmail');
            const modalMessage = document.getElementById('modalMessage');
            if (modalEmail) modalEmail.value = userEmail || '';
            if (modalMessage) modalMessage.value = '';
            const m = document.getElementById('emailModal');
            if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
        }
    });

    const emailType = document.getElementById('emailType');
    if (emailType) {
        emailType.addEventListener('change', function () {
            const box = document.getElementById('customMessageBox');
            if (box) box.classList.toggle('hidden', this.value !== 'custom');
        });
    }

    const sendEmailConfirm = document.getElementById('sendEmailConfirm');
    if (sendEmailConfirm) {
        sendEmailConfirm.addEventListener('click', function () {
            const type = document.getElementById('emailType').value;
            const message = document.getElementById('modalMessage').value;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || document.querySelector('input[name="_token"]')?.value;
            fetch('/send-email/' + selectedTransactionId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ type: type, message: message }),
                credentials: 'same-origin',
            })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
            .then(function (res) {
                if (!res.ok) throw new Error((res.data && res.data.message) || 'Failed to send email');
                if (window.showAlert) window.showAlert('success', (res.data && res.data.message) || 'Email sent successfully!');
                const m = document.getElementById('emailModal');
                if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
            })
            .catch(function (err) {
                if (window.showAlert) window.showAlert('error', err.message || 'Failed to send email');
            });
        });
    }

    // Backdrop click closes any modal
    ['add-modal', 'edit-modal', 'delete-modal', 'emailModal', 'returnLogModal'].forEach(function (id) {
        const m = document.getElementById(id);
        if (m) m.addEventListener('click', function (e) { if (e.target === m) { m.classList.add('hidden'); m.classList.remove('flex'); } });
    });
});
</script>
@endsection
