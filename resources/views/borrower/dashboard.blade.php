@extends('components.default')
@section('title', 'Borrower - CICT Equipment Borrower System')
@section('content')

<div class="flex flex-col min-h-screen page-bg">
    <x-ui.page-header eyebrow="Borrower" title="Dashboard">
        <x-slot:actions>
            <button id="open-add-modal" type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                <i class="text-xs fas fa-plus"></i> Request Item
            </button>
            <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden">@csrf</form>
            <button type="button" id="logout-btn"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-white border rounded-md text-neutral-700 border-neutral-300 hover:bg-neutral-50">
                <i class="text-xs fas fa-sign-out-alt"></i> Logout
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <main class="flex-1 w-full p-4 mx-auto space-y-6 sm:p-6 max-w-content">
        <section>
            <h2 class="flex items-center gap-2 mb-3 text-base font-semibold text-neutral-900">
                <span class="grid border rounded-lg w-7 h-7 bg-primary-50 border-primary-100 place-items-center">
                    <i class="text-xs fas fa-list text-primary-600"></i>
                </span>
                My Equipment Requests
            </h2>
            <x-ui.table-card>
                @if($requests->isEmpty())
                    <div class="py-12 text-center">
                        <i class="block mb-3 text-3xl fas fa-inbox text-neutral-300"></i>
                        <p class="text-sm font-medium text-neutral-700">No requests yet</p>
                        <p class="mt-1 text-xs text-neutral-500">Click "Request Item" to submit one.</p>
                    </div>
                @else
                    <div class="p-4 overflow-x-auto">
                        <table id="requestTable" class="w-full text-sm display nowrap">
                            <thead>
                                <tr class="text-xs tracking-wider uppercase text-neutral-500 bg-neutral-50">
                                    <th class="px-4 py-3 font-semibold text-left">Equipment</th>
                                    <th class="px-4 py-3 font-semibold text-left">Qty</th>
                                    <th class="px-4 py-3 font-semibold text-left">Status</th>
                                    <th class="px-4 py-3 font-semibold text-left">Remarks</th>
                                    <th class="px-4 py-3 font-semibold text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200">
                                @foreach ($requests as $request)
                                    <tr class="hover:bg-neutral-50">
                                        <td class="px-4 py-3 font-medium text-neutral-900">{{ $request->equipment->equipment_name }}</td>
                                        <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ $request->quantity }}</td>
                                        <td class="px-4 py-3">
                                            @php $variant = ['Approved'=>'success','Declined'=>'danger','Pending'=>'warning'][$request->status] ?? 'neutral'; @endphp
                                            <x-ui.badge :status="$request->status" :variant="$variant" />
                                        </td>
                                        <td class="px-4 py-3 text-neutral-600">{{ $request->remarks ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <button type="button"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-md bg-neutral-100 text-neutral-700 border border-neutral-200 hover:bg-neutral-200 edit-btn"
                                                    data-id="{{ $request->id }}"
                                                    data-equipment-name="{{ $request->equipment->equipment_name }}"
                                                    data-quantity="{{ $request->quantity }}"
                                                    data-status="{{ $request->status }}"
                                                    data-remarks="{{ $request->remarks }}">
                                                <i class="fas fa-edit text-[11px]"></i> Edit
                                            </button>
                                            <button type="button"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-md bg-danger-50 text-danger-700 border border-danger-100 hover:bg-danger-100 delete-btn"
                                                    data-id="{{ $request->id }}"
                                                    data-equipment-name="{{ $request->equipment->equipment_name }}">
                                                <i class="fas fa-trash text-[11px]"></i> Delete
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

        <section>
            <h2 class="flex items-center gap-2 mb-3 text-base font-semibold text-neutral-900">
                <span class="grid border rounded-lg w-7 h-7 bg-primary-50 border-primary-100 place-items-center">
                    <i class="text-xs fas fa-history text-primary-600"></i>
                </span>
                My borrow transactions
            </h2>
            <x-ui.table-card>
                @if($transactions->isEmpty())
                    <div class="py-12 text-center">
                        <i class="block mb-3 text-3xl fas fa-exchange-alt text-neutral-300"></i>
                        <p class="text-sm font-medium text-neutral-700">No transactions yet</p>
                        <p class="mt-1 text-xs text-neutral-500">Once your request is approved, your transactions will appear here.</p>
                    </div>
                @else
                    <div class="p-4 overflow-x-auto">
                        <table id="transactionTable" class="w-full text-sm display nowrap">
                            <thead>
                                <tr class="text-xs tracking-wider uppercase text-neutral-500 bg-neutral-50">
                                    <th class="px-4 py-3 font-semibold text-left">Equipment</th>
                                    <th class="px-4 py-3 font-semibold text-left">Qty</th>
                                    <th class="px-4 py-3 font-semibold text-left">Borrow</th>
                                    <th class="px-4 py-3 font-semibold text-left">Return</th>
                                    <th class="px-4 py-3 font-semibold text-left">Purpose</th>
                                    <th class="px-4 py-3 font-semibold text-left">Status</th>
                                    <th class="px-4 py-3 font-semibold text-left">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200">
                                @foreach ($transactions as $tx)
                                    <tr class="hover:bg-neutral-50">
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
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.table-card>
        </section>
    </main>
</div>

{{-- Logout confirm modal --}}
<div id="logout-modal" class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-neutral-900/50">
    <div class="flex flex-col w-full max-w-sm bg-white border border-neutral-200 rounded-xl shadow-flat">
        <div class="px-6 py-5">
            <h3 class="text-base font-semibold text-neutral-900">Confirm Logout</h3>
            <p class="mt-1 text-sm text-neutral-600">Are you sure you want to log out?</p>
        </div>
        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
            <button id="cancel-logout" type="button" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium bg-white border rounded-md text-neutral-700 border-neutral-300 hover:bg-neutral-50">Cancel</button>
            <button id="confirm-logout" type="button" class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white rounded-md bg-danger-600 hover:bg-danger-700">Logout</button>
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
