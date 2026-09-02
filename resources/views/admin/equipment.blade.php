@extends('components.default')

@section('title', 'Equipment - CICT Equipment Borrower System')

@section('content')
    @include('components.admin.navbar')

    <div class="dash-bg min-h-screen md:ml-80">
        <header class="sticky top-0 z-30 dash-header">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <button id="menu-toggle" class="text-neutral-400 hover:text-white md:hidden">
                        <i class="text-lg fas fa-bars"></i>
                    </button>
                    <div>
                        <p class="text-xs font-medium tracking-widest uppercase" style="color:var(--text-muted)">Equipment</p>
                        <p class="text-sm font-semibold tracking-tight text-white -mt-0.5">Manage inventory</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button id="open-add-modal" class="btn-primary inline-flex items-center gap-2 !py-2 !px-4 !text-sm !rounded-xl">
                        <i class="fas fa-plus text-xs"></i> Add Equipment
                    </button>
                </div>
            </div>
        </header>

        <main class="p-6 space-y-5 max-w-content mx-auto">
            {{-- Alerts handled by global components.alerts --}}

            <x-ui.table-card>
                <table id="equipmentTable" class="w-full display nowrap">
                    <thead>
                        <tr>
                            <th>Equipment name</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Available</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($equipment as $item)
                            <tr>
                                <td class="font-medium text-white">{{ $item->equipment_name }}</td>
                                <td class="max-w-xs truncate text-neutral-300">{{ $item->description }}</td>
                                <td class="tabular-nums">{{ $item->quantity }}</td>
                                <td class="tabular-nums">{{ $item->available_quantity }}</td>
                                <td>
                                    @php $variant = $item->status === 'Available' ? 'success' : 'danger'; @endphp
                                    <x-ui.badge :status="ucfirst(str_replace('_', ' ', $item->status))" :variant="$variant" />
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <button class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-primary-500/10 text-primary-300 border border-primary-500/20 hover:bg-primary-500/20 transition edit-btn" data-id="{{ $item->id }}"
                                            data-name="{{ $item->equipment_name }}" data-description="{{ $item->description }}"
                                            data-quantity="{{ $item->quantity }}"
                                            data-available="{{ $item->available_quantity }}"
                                            data-status="{{ $item->status }}">
                                            <i class="fas fa-edit text-[11px]"></i> Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.table-card>
        </main>
    </div>

    {{-- Modals For (Add, Edit, Delete) --}}
    @include('components.admin.equipment.add-equipment')
    @include('components.admin.equipment.edit-modal')
    @include('components.admin.equipment.delete-modal')

    <script>
        $(document).ready(function() {
            try {
                let table = (window.initAppTable ? window.initAppTable('#equipmentTable', {
                    language: { search: "", searchPlaceholder: "Search equipment..." }
                }) : $('#equipmentTable').DataTable({
                    responsive: true,
                    autoWidth: false,
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    language: { search: "", searchPlaceholder: "Search equipment..." }
                }));
            } catch(e) { console.error('DataTable init failed (equipmentTable)', e); }

            $('#equipmentTable').on('click', '.edit-btn', function() {
                $('#edit-id').val($(this).data('id'));
                $('#edit-name').val($(this).data('name'));
                $('#edit-description').val($(this).data('description'));
                $('#edit-quantity').val($(this).data('quantity'));
                $('#edit-available').val($(this).data('available'));
                $('#edit-status').val($(this).data('status'));
                $('#edit-modal').removeClass('hidden');
            });

            $('#equipmentTable').on('click', '.delete-btn', function() {
                let id = $(this).data('id');
                let name = $(this).data('name');
                $('#delete-item-name').text(name);
                $('#delete-form').attr('action', '/admin/equipment/' + id);
                $('#delete-modal').removeClass('hidden');
            });

            $('#confirm-delete').on('click', function () {
                $('#delete-form').submit();
            });

            $(document).on('click', '#open-add-modal', function() {
                $('#add-modal').removeClass('hidden');
            });

            $(document).on('click', '#cancel-add, #cancel-edit, #cancel-delete, .cancel-add, .cancel-edit', function() {
                $('#add-modal, #edit-modal, #delete-modal').addClass('hidden');
            });

            $(document).on('click', '#add-modal, #edit-modal, #delete-modal', function(e) {
                if (e.target === this) $(this).addClass('hidden');
            });
        });
    </script>
@endsection
