@extends('components.default')
@section('title', 'Borrow Transactions - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

<div class="min-h-screen page-bg md:ml-64">
    <x-ui.page-header eyebrow="Transactions" title="Borrow & returns">
        <x-slot:actions>
            <button id="open-add-modal" type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                <i class="text-xs fas fa-plus"></i> Add Transaction
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <main class="p-4 mx-auto space-y-5 sm:p-6 max-w-content">
        <x-ui.table-card>
            @if($transactions->isEmpty())
                <div class="py-16 text-center">
                    <i class="block mb-3 text-4xl fas fa-exchange-alt text-neutral-300"></i>
                    <p class="text-sm font-medium text-neutral-700">No transactions yet</p>
                    <p class="mt-1 text-xs text-neutral-500">Click "Add Transaction" to log a new borrow.</p>
                </div>
            @else
                <div class="p-4 overflow-x-auto">
                    <table id="transactions-table" class="w-full text-sm display nowrap">
                        <thead>
                            <tr class="text-xs tracking-wider uppercase text-neutral-500 bg-neutral-50">
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
                                        <select class="status-dropdown inline-flex px-2.5 py-1 text-xs font-semibold rounded-md border focus:outline-none focus:ring-2 focus:ring-primary-500/30
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
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-md bg-neutral-100 text-neutral-700 border border-neutral-200 hover:bg-neutral-200 edit-btn"
                                                    data-id="{{ $tx->id }}" data-user="{{ $tx->user->id ?? '' }}"
                                                    data-equipment="{{ $tx->equipment->id ?? '' }}"
                                                    data-borrow="{{ \Carbon\Carbon::parse($tx->borrow_date)->format('Y-m-d') }}"
                                                    data-return="{{ $returnDate }}" data-quantity="{{ $tx->quantity }}"
                                                    data-purpose="{{ $tx->purpose }}" data-status="{{ $tx->status }}"
                                                    data-remarks="{{ $tx->remarks ?? '' }}"
                                                    data-class="{{ $tx->classSchedule->id ?? '' }}">
                                                <i class="fas fa-edit text-[11px]"></i> Edit
                                            </button>
                                            <button type="button"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-md bg-neutral-100 text-neutral-700 border border-neutral-200 hover:bg-neutral-200 send-email-btn"
                                                    data-id="{{ $tx->id }}" data-user-email="{{ $tx->user->email ?? '' }}">
                                                <i class="fas fa-envelope text-[11px]"></i> Email
                                            </button>
                                            <button type="button"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-md bg-danger-50 text-danger-700 border border-danger-100 hover:bg-danger-100 delete-btn"
                                                    data-id="{{ $tx->id }}" data-name="transaction #{{ $tx->id }}">
                                                <i class="fas fa-trash text-[11px]"></i>
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

    // Dynamic quantity fields for the add modal
    const sel = document.getElementById('equipment-select');
    if (sel) {
        sel.addEventListener('change', function () {
            const rawIds = Array.from(this.selectedOptions).map(function (o) { return o.value; });
            const equipmentIds = rawIds.filter(function (v) { return v !== '' && v !== null; });
            const quantitiesDiv = document.getElementById('equipment-quantities');
            if (!quantitiesDiv) return;
            quantitiesDiv.innerHTML = '';
            equipmentIds.forEach(function (equipmentId) {
                const field = document.createElement('div');
                field.innerHTML =
                    '<label class="block text-sm font-medium text-neutral-700">Quantity for Equipment #' + equipmentId + '</label>' +
                    '<input type="number" name="quantities[' + equipmentId + ']" min="1" required ' +
                    'class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none tabular-nums">';
                quantitiesDiv.appendChild(field);
            });
        });
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
