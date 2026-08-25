@extends('components.default')

@section('title', 'Instructor - CICT Equipment Borrower System')

@section('content')
<div class="flex flex-col min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-md">
        <div class="flex flex-wrap items-center justify-between px-4 py-4 md:px-8">
            <!-- Left: Title -->
            <div class="flex items-center space-x-4">
                <button id="menu-toggle" class="text-gray-600 hover:text-gray-800 md:hidden">
                    <i class="text-2xl fas fa-bars"></i>
                </button>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 md:text-2xl">Equipment Management</h1>
                    <p class="text-sm text-gray-500">Manage all equipment and inventory</p>
                </div>
            </div>

            <!-- Right: Buttons -->
            <div class="flex items-center mt-3 space-x-3 md:mt-0">
                <!-- Request Item Button -->
                <button id="open-add-modal"
                    class="flex items-center px-4 py-2 text-sm font-medium text-white transition bg-blue-600 rounded-lg shadow md:text-base hover:bg-blue-700">
                    <i class="mr-2 fas fa-plus"></i>
                    <span>Request Item</span>
                </button>

                <!-- Logout -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="flex items-center px-4 py-2 text-sm font-medium text-red-600 transition border border-red-600 rounded-lg shadow md:text-base hover:bg-red-600 hover:text-white">
                        <i class="mr-2 fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>

        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 p-4 space-y-10 md:p-8">
        @if (session('success'))
            <div class="flex items-center px-4 py-3 text-green-800 bg-green-100 border-l-4 border-green-500 rounded-lg shadow-sm">
                <i class="mr-2 fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Equipment Requests -->
        <section>
            <h2 class="flex items-center mb-3 text-lg font-semibold text-gray-700 md:text-xl">
                <i class="mr-2 text-blue-600 fas fa-list"></i> My Equipment Requests
            </h2>
            <div class="p-4 overflow-x-auto bg-white shadow-lg rounded-xl">
                <table id="requestTable" class="w-full text-sm md:text-base">
                    <thead class="text-gray-700 bg-gray-100">
                        <tr>
                            <th class="px-4 py-3">Equipment Name</th>
                            <th class="px-4 py-3">Quantity</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Remarks</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $request)
                            <tr class="transition border-b hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $request->equipment->equipment_name }}</td>
                                <td class="px-4 py-3">{{ $request->quantity }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusColors = [
                                            'Approved' => 'bg-green-100 text-green-800',
                                            'Declined' => 'bg-red-100 text-red-800',
                                        ];
                                        $statusColor = $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 inline-flex text-xs font-semibold rounded-full {{ $statusColor }}">
                                        {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $request->remarks ?? '---' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <button class="px-3 py-1 text-xs text-white bg-blue-600 rounded-lg md:text-sm hover:bg-blue-700 edit-btn"
                                            data-id="{{ $request->id }}"
                                            data-equipment-name="{{ $request->equipment->equipment_name }}"
                                            data-quantity="{{ $request->quantity }}"
                                            data-status="{{ $request->status }}"
                                            data-remarks="{{ $request->remarks }}">
                                            <i class="mr-1 fas fa-edit"></i>Edit
                                        </button>
                                        <button class="px-3 py-1 text-xs text-white bg-red-600 rounded-lg md:text-sm hover:bg-red-700 delete-btn"
                                            data-id="{{ $request->id }}"
                                            data-equipment-name="{{ $request->equipment->equipment_name }}">
                                            <i class="mr-1 fas fa-trash"></i>Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Borrow Transactions -->
        <section>
            <h2 class="flex items-center mb-3 text-lg font-semibold text-gray-700 md:text-xl">
                <i class="mr-2 text-indigo-600 fas fa-history"></i> My Borrow Transactions
            </h2>
            <div class="p-4 overflow-x-auto bg-white shadow-lg rounded-xl">
                <table id="transactionTable" class="w-full text-sm md:text-base">
                    <thead class="text-gray-700 bg-gray-100">
                        <tr>
                            <th class="px-4 py-3">Equipment</th>
                            <th class="px-4 py-3">Quantity</th>
                            <th class="px-4 py-3">Borrow Date</th>
                            <th class="px-4 py-3">Return Date</th>
                            <th class="px-4 py-3">Purpose</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $tx)
                            <tr class="transition border-b hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $tx->equipment->equipment_name ?? '---' }}</td>
                                <td class="px-4 py-3">{{ $tx->quantity }}</td>
                                <td class="px-4 py-3">{{ $tx->borrow_date }}</td>
                                <td class="px-4 py-3">{{ $tx->return_date ?? '---' }}</td>
                                <td class="px-4 py-3">{{ $tx->purpose }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $txColors = [
                                            'Borrowed' => 'bg-yellow-100 text-yellow-800',
                                            'Returned' => 'bg-green-100 text-green-800',
                                            'Overdue' => 'bg-red-100 text-red-800',
                                        ];
                                        $txColor = $txColors[$tx->status] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 inline-flex text-xs font-semibold rounded-full {{ $txColor }}">
                                        {{ $tx->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $tx->remarks ?? '---' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

{{-- Modals --}}
@include('components.instructor.request-item-modal')
@include('components.instructor.update-request-modal')
@include('components.instructor.delete-request-modal')

{{-- Scripts --}}
<script>
    $(document).ready(function () {
        $('#requestTable, #transactionTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            language: {
                search: "🔍 ",
                searchPlaceholder: "Search..."
            }
        });

        // Edit modal
        $('.edit-btn').on('click', function() {
            $('#edit-id').val($(this).data('id'));
            $('#edit-equipment-name').text($(this).data('equipment-name'));
            $('#edit-quantity').val($(this).data('quantity'));
            $('#edit-status').val($(this).data('status'));
            $('#edit-remarks').val($(this).data('remarks'));
            $('#edit-modal').removeClass('hidden');
        });

        // Delete modal
        $('.delete-btn').on('click', function () {
            const id = $(this).data('id');
            const name = $(this).data('equipment-name');
            $('#delete-item-name').text(name);
            $('#delete-form').attr('action', '/instructor/item-request/' + id);
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

        // Close on outside click
        $('#add-modal, #edit-modal, #delete-modal').on('click', function(e) {
            if (e.target === this) $(this).addClass('hidden');
        });
    });
</script>

<style>
    /* DataTable Custom Styling */
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
        transition: all 0.2s;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #3b82f6;
        color: white !important;
        border-color: #3b82f6;
    }
</style>
@endsection


