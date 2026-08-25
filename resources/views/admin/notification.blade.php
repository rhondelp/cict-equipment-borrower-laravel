@extends('components.default')

@section('title', 'Equipment - CICT Equipment Borrower System')

@section('content')
    @include('components.admin.navbar')

    <div class="min-h-screen bg-gray-50 md:ml-80">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 shadow-sm">
            <div class="flex px-6 py-4 justffy-between notids-center">
                <div class="flex spacf-x-4 notids-center">
                    <button id="menu-toggle" class="text-gray-500 hover:text-gray-700 md:hidden">
                        <i class="text-xl fas fa-bars"></i>
                    </button>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">NOTIFICATION HISTORY</h1>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="p-6">
            @if (session('success'))
                <div class="px-4 py-3 mb-6 text-green-800 bg-green-100 border-l-4 border-green-500 rounded shadow-sm" role="alert">
                    <div class="flex notifs-center">
                        <i class="mr-2 fas fa-check-circle"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            <!-- Equipment Table -->
            <table id="notificationTable" class="w-full display nowrap">
                <thead class="bg-gray-50">
                    <tr>
                        <th>BORROWER</th>
                        <th>MESSAGE</th>
                        <th>NOTIF TYPE</th>
                        <th>SEND DATE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($notifications as $notif)
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td>{{ $notif->user->name }}</td>
                            <td class="break-words whitespace-normal">{{ $notif->message }}</td>
                            <td>{{ $notif->notification_type }}</td>
                            <td>{{ $notif->send_date ? \Carbon\Carbon::parse($notif->send_date)->format('M j, Y g:i A') : 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

    </div>
    </div>
    </main>
    </div>

    {{-- Include Modals (Add, Edit, Delete) --}}
    @include('components.admin.equipment.add-equipment')
    @include('components.admin.equipment.edit-modal')
    @include('components.admin.equipment.delete-modal')

    {{-- DataTables & Script --}}
    <script>
        $(document).ready(function() {
            let table = $('#notificationTable').DataTable({
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
                $('#delete-notif-name').text(name);
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
