@extends('components.default')

@section('title', 'Equipment - CICT Equipment Borrower System')

@section('content')
    @include('components.admin.navbar')

    <div class="min-h-screen bg-gray-50 md:ml-80">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 shadow-sm">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center space-x-4">
                    <button id="menu-toggle" class="text-gray-500 hover:text-gray-700 md:hidden">
                        <i class="text-xl fas fa-bars"></i>
                    </button>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">EQUIPMENT MANAGEMENT</h1>
                        <p class="text-sm text-gray-500">Manage all equipment</p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Add Equipment Button -->
                    <button id="open-add-modal"
                        class="flex items-center px-4 py-2 space-x-2 font-medium text-white transition-colors duration-200 bg-blue-500 hover:bg-blue-600 rounded-xl">
                        <i class="fas fa-plus"></i>
                        <span>Add Equipment</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="p-6">
            @if (session('success'))
                <div class="px-4 py-3 mb-6 text-green-800 bg-green-100 border-l-4 border-green-500 rounded shadow-sm" role="alert">
                    <div class="flex items-center">
                        <i class="mr-2 fas fa-check-circle"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

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
                                <div class="flex items-center space-x-2">
                                    <button class="px-4 py-1 text-xs text-white bg-blue-600 md:text-sm hover:bg-blue-700 edit-btn" data-id="{{ $item->id }}"
                                        data-name="{{ $item->equipment_name }}" data-description="{{ $item->description }}"
                                        data-quantity="{{ $item->quantity }}"
                                        data-available="{{ $item->available_quantity }}"
                                        data-status="{{ $item->status }}">
                                        <i class="fas fa-edit"></i> Edit
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

    {{-- DataTables & Script --}}
    <script>
        $(document).ready(function() {
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

            // Edit modal
            $('.edit-btn').on('click', function() {
                $('#edit-id').val($(this).data('id'));
                $('#edit-name').val($(this).data('name'));
                $('#edit-description').val($(this).data('description'));
                $('#edit-quantity').val($(this).data('quantity'));
                $('#edit-available').val($(this).data('available'));
                $('#edit-status').val($(this).data('status'));
                $('#edit-modal').removeClass('hidden');
            });

            // Delete modal
            $('.delete-btn').on('click', function() {
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
            $('#open-add-modal').on('click', function() {
                $('#add-modal').removeClass('hidden');
            });

            // Cancel buttons
            $('#cancel-add, #cancel-edit, #cancel-delete').on('click', function() {
                $('#add-modal, #edit-modal, #delete-modal').addClass('hidden');
            });

            // Close when clicking outside
            $('#add-modal, #edit-modal, #delete-modal').on('click', function(e) {
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
