@extends('components.default')

@section('title', 'Equipment - CICT Equipment Borrower System')

@section('content')
    @include('components.admin.navbar')

    <div class="dash-bg min-h-screen md:ml-80">
        <!-- Header — dense, consistent -->
        <header class="dash-header">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <button id="menu-toggle" class="text-[#94a3b8] hover:text-white md:hidden">
                        <i class="text-lg fas fa-bars"></i>
                    </button>
                    <div>
                        <h1 class="text-[11px] font-medium tracking-widest uppercase text-[#94a3b8]">Notifications</h1>
                        <p class="text-[13px] font-semibold tracking-tight text-white -mt-0.5">History</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content — denser -->
        <main class="p-4">
            {{-- Alerts handled by global components.alerts --}}

            <div class="dash-table-wrap p-3">
            <table id="notificationTable" class="w-full display nowrap">
                <thead class="text-[#94a3b8] text-xs tracking-widest uppercase">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium">Borrower</th>
                        <th class="px-3 py-2 text-left font-medium">Message</th>
                        <th class="px-3 py-2 text-left font-medium">Type</th>
                        <th class="px-3 py-2 text-left font-medium">Sent</th>
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
