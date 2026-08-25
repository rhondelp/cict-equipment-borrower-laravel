@extends('components.default')

@section('title', 'Borrower - CICT Equipment Borrower System')

@section('content')
<div class="flex flex-col min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-md">
        <div class="flex flex-wrap items-center justify-between px-4 py-4 md:px-8">
            <!-- Left: Logo + Title -->
            <div class="flex items-center space-x-4">
                {{-- <button id="menu-toggle" class="text-gray-600 hover:text-gray-900 md:hidden">
                    <i class="text-2xl fas fa-bars"></i>
                </button> --}}

                <!-- Logo + Text -->
                <div class="flex items-center space-x-3">
                    <img class="object-contain w-auto h-12 md:h-16"
                        src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png"
                        alt="CICT Logo">

                    <div class="flex flex-col">
                        <h1 class="text-xl font-extrabold tracking-wide text-gray-900 md:text-2xl">
                            BORROWER DASHBOARD
                        </h1>
                        <p class="mt-1 text-sm text-gray-500">
                            View borrow transactions and Request Item
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right: Buttons -->
            <div class="flex items-center mt-3 space-x-3 md:mt-0">
                <button id="open-add-modal"
                    class="flex items-center px-5 py-2 text-sm font-semibold text-white transition rounded-lg shadow-md bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700">
                    <i class="mr-2 fas fa-plus"></i>
                    Request Item
                </button>

                <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden">
                    @csrf
                </form>

                <button type="button" id="logout-btn"
                    class="flex items-center px-5 py-2 text-sm font-semibold text-red-600 transition border border-red-600 rounded-lg shadow-md hover:bg-red-600 hover:text-white">
                    <i class="mr-2 fas fa-sign-out-alt"></i> Logout
                </button>

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
                                            'Approved' => 'bg-green-100 text-green-800 font-semibold',
                                            'Declined' => 'bg-red-100 text-red-800 font-semibold',
                                            'Pending' => 'bg-yellow-100 text-yellow-800 font-semibold',
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
                                        <button class="px-3 py-1 text-xs text-white bg-blue-600 md:text-sm hover:bg-blue-700 edit-btn"
                                            data-id="{{ $request->id }}"
                                            data-equipment-name="{{ $request->equipment->equipment_name }}"
                                            data-quantity="{{ $request->quantity }}"
                                            data-status="{{ $request->status }}"
                                            data-remarks="{{ $request->remarks }}">
                                            <i class="mr-1 fas fa-edit"></i>Edit
                                        </button>
                                        {{-- <button class="px-3 py-1 text-xs text-white bg-red-600 md:text-sm hover:bg-red-700 delete-btn"
                                            data-id="{{ $request->id }}"
                                            data-equipment-name="{{ $request->equipment->equipment_name }}">
                                            <i class="mr-1 fas fa-trash"></i>Delete
                                        </button> --}}
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

<!-- Logout Confirmation Modal -->
<div id="logout-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-gray-500 bg-opacity-50">
    <div class="w-full max-w-sm p-6 bg-white rounded-lg shadow-lg">
        <h3 class="mb-4 text-lg font-semibold text-gray-800">Confirm Logout</h3>
        <p class="mb-6 text-sm text-gray-600">Are you sure you want to log out?</p>
        <div class="flex justify-between">
            <button id="cancel-logout" class="px-5 py-2 text-sm font-semibold text-gray-600 bg-gray-300 rounded-lg hover:bg-gray-400">
                Cancel
            </button>
            <button id="confirm-logout" class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700">
                Logout
            </button>
        </div>
    </div>
</div>
w

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
            $('#delete-form').attr('action', '/borrower/request/' + id);
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
<script>
    $(document).ready(function () {
        // Show the logout confirmation modal when logout button is clicked
        $('#logout-btn').on('click', function () {
            $('#logout-modal').removeClass('hidden');
        });

        // Handle clicking "Cancel" to close the modal
        $('#cancel-logout').on('click', function () {
            $('#logout-modal').addClass('hidden');
        });

        // Handle clicking "Logout" to submit the logout form
        $('#confirm-logout').on('click', function () {
            $('#logout-form').submit();
        });

        // Close the modal if clicked outside
        $('#logout-modal').on('click', function (e) {
            if (e.target === this) {
                $(this).addClass('hidden');
            }
        });
    });
</script>

@endsection
