@extends('components.default')

@section('title', 'Equipment - CICT Equipment Borrower System')

@section('content')
    @include('components.admin.navbar')

    <div class="dash-bg min-h-screen md:ml-80">
        <!-- Header — dense -->
        <header class="dash-header">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <button id="menu-toggle" class="text-[#94a3b8] hover:text-white md:hidden">
                        <i class="text-lg fas fa-bars"></i>
                    </button>
                    <div>
                        <h1 class="text-[11px] font-medium tracking-widest uppercase text-[#94a3b8]">Equipment</h1>
                        <p class="text-[13px] font-semibold tracking-tight text-white -mt-0.5">Manage inventory</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button id="open-add-modal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-[var(--accent)] text-white hover:bg-[var(--accent-strong)] transition">
                        <i class="fas fa-plus text-[10px]"></i> Add equipment
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content — denser -->
        <main class="p-4">
            {{-- Alerts now handled by global components.alerts (fixed toast) --}}

            <!-- Equipment Table -->
            <table id="equipmentTable" class="w-full display nowrap">
                <thead class="bg-gray-50">
                    <tr>
                        <th>EQUIPMENT NAME</th>
                        <th>DESCRIPTION</th>
                        <th>QUANTITY</th>
                        <th>AVAILABLE QUANTITY</th>
                        <th>STATUS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($equipment as $item)
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td>{{ $item->equipment_name }}</td>
                            <td class="max-w-xs truncate">{{ $item->description }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->available_quantity }}</td>
                            <td>
                                @php
                                    $statusColors = [
                                        'Available' => 'bg-green-100 text-green-800',
                                        'Unavailable' => 'bg-red-100 text-red-800',
                                    ];
                                    $statusColor = $statusColors[$item->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                    {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-1.5">
                                    <button class="px-2.5 py-1 text-xs font-medium bg-[rgba(148,163,184,0.08)] text-[#cbd5e1] border border-[rgba(255,255,255,0.06)] rounded-md hover:bg-[rgba(148,163,184,0.12)] transition edit-btn" data-id="{{ $item->id }}"
                                        data-name="{{ $item->equipment_name }}" data-description="{{ $item->description }}"
                                        data-quantity="{{ $item->quantity }}"
                                        data-available="{{ $item->available_quantity }}"
                                        data-status="{{ $item->status }}">
                                        <i class="fas fa-edit text-[11px]"></i> Edit
                                    </button>

                                    {{-- <button class="px-4 py-1 text-xs text-white bg-red-600 md:text-sm hover:bg-red-700 delete-btn"
                                        data-id="{{ $item->id }}" data-name="{{ $item->equipment_name }}">
                                        <i class="fas fa-trash"></i> Delete
                                    </button> --}}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

    </div>
    </div>
    </main>
    </div>

    {{-- Modals For (Add, Edit, Delete) --}}
    @include('components.admin.equipment.add-equipment')
    @include('components.admin.equipment.edit-modal')
    @include('components.admin.equipment.delete-modal')

    {{-- DataTables & Script — uses delegation so buttons survive DataTables redraw/pagination; wrapped in try/catch so one error doesn't block later listeners --}}
    <script>
        $(document).ready(function() {
            try {
                let table = $('#equipmentTable').DataTable({
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, -1],
                        [10, 25, 50, "All"]
                    ],
                    language: {
                        search: "🔍 ",
                        searchPlaceholder: "Search equipment..."
                    }
                });
            } catch(e) { console.error('DataTable init failed (equipmentTable)', e); }

            // Edit modal — delegated (rows are re-rendered by DataTables)
            $('#equipmentTable').on('click', '.edit-btn', function() {
                $('#edit-id').val($(this).data('id'));
                $('#edit-name').val($(this).data('name'));
                $('#edit-description').val($(this).data('description'));
                $('#edit-quantity').val($(this).data('quantity'));
                $('#edit-available').val($(this).data('available'));
                $('#edit-status').val($(this).data('status'));
                $('#edit-modal').removeClass('hidden');
            });

            // Delete modal — delegated
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


            // Add modal
            $(document).on('click', '#open-add-modal', function() {
                $('#add-modal').removeClass('hidden');
            });

            // Cancel buttons — use class + id to survive duplicate IDs and handle all modals
            $(document).on('click', '#cancel-add, #cancel-edit, #cancel-delete, .cancel-add, .cancel-edit', function() {
                $('#add-modal, #edit-modal, #delete-modal').addClass('hidden');
            });

            // Close when clicking outside — use higher z-index safe check
            $(document).on('click', '#add-modal, #edit-modal, #delete-modal', function(e) {
                if (e.target === this) $(this).addClass('hidden');
            });
        });
    </script>

    <style>
        /* DataTable Styling */
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            margin-left: 0.5rem;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.25rem 0.5rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.4rem 0.75rem;
            margin: 0 0.125rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #3b82f6;
            color: white !important;
            border-color: #3b82f6;
        }
    </style>
@endsection
