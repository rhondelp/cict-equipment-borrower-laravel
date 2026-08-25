@extends('components.default')

@section('title', 'Notifications - CICT Equipment Borrower System')

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
                        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Notification History</h1>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="p-6">
            {{-- Flash messages --}}
            <x-ui.feedback />

            <!-- Notifications Table -->
            <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
            @if ($notifications->isEmpty())
                <x-ui.empty-state icon="fa-bell" title="No notifications sent"
                    hint="Automated return reminders and manual emails are recorded here." />
            @else
            <table id="notificationTable" class="w-full display nowrap">
                <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500">
                    <tr>
                        <th>Borrower</th>
                        <th>Message</th>
                        <th>Notif Type</th>
                        <th>Send Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($notifications as $notif)
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td>{{ $notif->user->name }}</td>
                            <td class="break-words whitespace-normal">{{ $notif->message }}</td>
                            <td><x-ui.status-badge :status="$notif->notification_type" /></td>
                            <td>{{ $notif->send_date ? \Carbon\Carbon::parse($notif->send_date)->format('M j, Y g:i A') : 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
            </div>
        </main>
    </div>

    {{-- DataTables Script --}}
    <script>
        $(document).ready(function() {
            $('#notificationTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                language: {
                    search: "",
                    searchPlaceholder: "Search notifications..."
                }
            });
        });
    </script>
@endsection
