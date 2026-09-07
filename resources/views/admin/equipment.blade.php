@extends('components.default')
@section('title', 'Equipment - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

<div class="min-h-screen page-bg md:ml-64">
    <x-ui.page-header eyebrow="Equipment" title="Manage inventory">
        <x-slot:actions>
            <button id="open-add-modal" type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                <i class="text-xs fas fa-plus"></i> Add Equipment
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <main class="p-4 mx-auto space-y-5 sm:p-6 max-w-content">
        <x-ui.table-card>
            @if($equipment->isEmpty())
                <div class="py-16 text-center">
                    <i class="block mb-3 text-4xl fas fa-tools text-neutral-300"></i>
                    <p class="text-sm font-medium text-neutral-700">No equipment yet</p>
                    <p class="mt-1 text-xs text-neutral-500">Click "Add Equipment" to create your first item.</p>
                </div>
            @else
                <div class="p-4 overflow-x-auto">
                    <table id="equipmentTable" class="w-full text-sm display nowrap">
                        <thead>
                            <tr class="text-xs tracking-wider uppercase text-neutral-500 bg-neutral-50">
                                <th class="px-4 py-3 font-semibold text-left">Equipment name</th>
                                <th class="px-4 py-3 font-semibold text-left">Description</th>
                                <th class="px-4 py-3 font-semibold text-left">Quantity</th>
                                <th class="px-4 py-3 font-semibold text-left">Available</th>
                                <th class="px-4 py-3 font-semibold text-left">Status</th>
                                <th class="px-4 py-3 font-semibold text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @foreach ($equipment as $item)
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-4 py-3 font-medium text-neutral-900">{{ $item->equipment_name }}</td>
                                    <td class="max-w-xs px-4 py-3 truncate text-neutral-600">{{ $item->description }}</td>
                                    <td class="px-4 py-3 text-neutral-900 tabular-nums">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 text-neutral-900 tabular-nums">{{ $item->available_quantity }}</td>
                                    <td class="px-4 py-3">
                                        @php $variant = $item->status === 'Available' ? 'success' : 'danger'; @endphp
                                        <x-ui.badge :status="ucfirst(str_replace('_', ' ', $item->status))" :variant="$variant" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md bg-primary-50 text-primary-700 border border-primary-100 hover:bg-primary-100 edit-btn"
                                                    data-id="{{ $item->id }}"
                                                    data-name="{{ $item->equipment_name }}"
                                                    data-description="{{ $item->description }}"
                                                    data-quantity="{{ $item->quantity }}"
                                                    data-available="{{ $item->available_quantity }}"
                                                    data-status="{{ $item->status }}">
                                                <i class="fas fa-edit text-[11px]"></i> Edit
                                            </button>
                                            <button type="button"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md bg-danger-50 text-danger-700 border border-danger-100 hover:bg-danger-100 delete-btn"
                                                    data-id="{{ $item->id }}"
                                                    data-name="{{ $item->equipment_name }}">
                                                <i class="fas fa-trash text-[11px]"></i> Delete
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

@include('components.admin.equipment.add-equipment')
@include('components.admin.equipment.edit-modal')
@include('components.admin.equipment.delete-modal')

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableEl = document.getElementById('equipmentTable');
    if (tableEl && window.initAppTable) {
        try {
            window.initAppTable('#equipmentTable', {
                language: { search: '', searchPlaceholder: 'Search equipment...' }
            });
        } catch (e) { console.error('DataTable init failed (equipmentTable)', e); }
    }

    // Edit: open modal with row data
    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            const d = editBtn.dataset;
            const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
            set('edit-id', d.id);
            set('edit-name', d.name);
            set('edit-description', d.description);
            set('edit-quantity', d.quantity);
            set('edit-available', d.available);
            set('edit-status', d.status);
            document.getElementById('edit-modal')?.classList.remove('hidden');
            document.getElementById('edit-modal')?.classList.add('flex');
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const d = deleteBtn.dataset;
            const nameEl = document.getElementById('delete-item-name');
            const form = document.getElementById('delete-form');
            if (nameEl) nameEl.textContent = d.name;
            if (form) form.action = '/admin/equipment/' + d.id;
            document.getElementById('delete-modal')?.classList.remove('hidden');
            document.getElementById('delete-modal')?.classList.add('flex');
        }

        // Add modal trigger
        if (e.target.closest('#open-add-modal')) {
            document.getElementById('add-modal')?.classList.remove('hidden');
            document.getElementById('add-modal')?.classList.add('flex');
        }

        // Cancel buttons
        if (e.target.closest('.cancel-add') || e.target.closest('#cancel-add')) {
            const m = document.getElementById('add-modal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
        }
        if (e.target.closest('.cancel-edit') || e.target.closest('#cancel-edit')) {
            const m = document.getElementById('edit-modal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
        }
        if (e.target.closest('#cancel-delete') || e.target.closest('#cancel-delete-btn')) {
            const m = document.getElementById('delete-modal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
        }
    });

    // Confirm delete -> showConfirm() then submit
    const confirmDelete = document.getElementById('confirm-delete');
    if (confirmDelete) {
        confirmDelete.addEventListener('click', function () {
            const form = document.getElementById('delete-form');
            if (!form) return;
            if (window.showConfirm) {
                window.showConfirm({
                    title: 'Delete this equipment?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    confirmText: 'Yes, delete'
                }).then(function (r) {
                    if (r.isConfirmed) form.submit();
                });
            } else {
                form.submit();
            }
        });
    }

    // Backdrop click closes any open modal
    ['add-modal', 'edit-modal', 'delete-modal'].forEach(function (id) {
        const m = document.getElementById(id);
        if (m) m.addEventListener('click', function (e) { if (e.target === m) { m.classList.add('hidden'); m.classList.remove('flex'); } });
    });
});
</script>
@endsection
