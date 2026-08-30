@extends('components.default')

@section('title', 'Notifications - CICT Equipment Borrower System')

@section('content')
    @include('components.admin.navbar')

    <div class="dash-bg min-h-screen md:ml-80">
        <header class="dash-header">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <button id="menu-toggle" class="text-neutral-400 hover:text-white md:hidden">
                        <i class="text-lg fas fa-bars"></i>
                    </button>
                    <div>
                        <h1 class="text-xs font-medium tracking-widest uppercase text-neutral-400">Notifications</h1>
                        <p class="text-sm font-semibold tracking-tight text-white -mt-0.5">History</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-4 space-y-4 max-w-content mx-auto">
            <x-ui.table-card>
                <table id="notificationTable" class="w-full display nowrap">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Message</th>
                            <th>Type</th>
                            <th>Sent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($notifications as $notif)
                            <tr>
                                <td class="font-medium text-white">{{ $notif->user->name }}</td>
                                <td class="break-words whitespace-normal max-w-md">{{ $notif->message }}</td>
                                <td><x-ui.badge :status="$notif->notification_type" variant="neutral" /></td>
                                <td class="tabular-nums text-neutral-300">{{ $notif->send_date ? \Carbon\Carbon::parse($notif->send_date)->format('M j, Y g:i A') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.table-card>
        </main>
    </div>

    @include('components.admin.equipment.add-equipment')
    @include('components.admin.equipment.edit-modal')
    @include('components.admin.equipment.delete-modal')

    <script>
        $(document).ready(function() {
            try {
                let table = (window.initAppTable ? window.initAppTable('#notificationTable', {
                    language: { search: "", searchPlaceholder: "Search notifications..." }
                }) : $('#notificationTable').DataTable({
                    responsive: true, autoWidth: false, pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    language: { search: "", searchPlaceholder: "Search notifications..." }
                }));
            } catch(e) { console.error('DataTable init failed (notificationTable)', e); }
            $('.edit-btn').on('click', function() {
                $('#edit-id').val($(this).data('id'));
                $('#edit-name').val($(this).data('name'));
                $('#edit-description').val($(this).data('description'));
                $('#edit-quantity').val($(this).data('quantity'));
                $('#edit-available').val($(this).data('available'));
                $('#edit-status').val($(this).data('status'));
                $('#edit-modal').removeClass('hidden');
            });
            $('.delete-btn').on('click', function() {
                let id = $(this).data('id');
                let name = $(this).data('name');
                $('#delete-notif-name').text(name);
                $('#delete-form').attr('action', '/admin/equipment/' + id);
                $('#delete-modal').removeClass('hidden');
            });
            $('#confirm-delete').on('click', function () { $('#delete-form').submit(); });
            $('#open-add-modal').on('click', function() { $('#add-modal').removeClass('hidden'); });
            $('#cancel-add, #cancel-edit, #cancel-delete').on('click', function() {
                $('#add-modal, #edit-modal, #delete-modal').addClass('hidden');
            });
            $('#add-modal, #edit-modal, #delete-modal').on('click', function(e) {
                if (e.target === this) $(this).addClass('hidden');
            });
        });
    </script>
@endsection
