@extends('components.default')
@section('title', 'Notifications - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

<div class="page-bg min-h-screen md:ml-64">
    <x-ui.page-header eyebrow="Notifications" title="History" />

    <main class="p-4 sm:p-6 space-y-5 max-w-content mx-auto">
        <x-ui.table-card>
            @if($notifications->isEmpty())
                <div class="py-16 text-center">
                    <i class="fas fa-bell text-4xl text-neutral-300 mb-3 block"></i>
                    <p class="text-sm font-medium text-neutral-700">No notifications sent yet</p>
                    <p class="text-xs text-neutral-500 mt-1">Return reminders and manual emails will appear here.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table id="notificationTable" class="w-full display nowrap text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-neutral-500 bg-neutral-50">
                                <th class="text-left px-4 py-3 font-semibold">Borrower</th>
                                <th class="text-left px-4 py-3 font-semibold">Message</th>
                                <th class="text-left px-4 py-3 font-semibold">Type</th>
                                <th class="text-left px-4 py-3 font-semibold">Sent</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @foreach ($notifications as $notif)
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-4 py-3 font-medium text-neutral-900">{{ $notif->user->name }}</td>
                                    <td class="px-4 py-3 text-neutral-700 break-words whitespace-normal max-w-md">{{ $notif->message }}</td>
                                    <td class="px-4 py-3"><x-ui.badge :status="$notif->notification_type" variant="neutral" /></td>
                                    <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ $notif->send_date ? \Carbon\Carbon::parse($notif->send_date)->format('M j, Y g:i A') : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.table-card>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableEl = document.getElementById('notificationTable');
    if (tableEl && window.initAppTable) {
        try {
            window.initAppTable('#notificationTable', {
                language: { search: '', searchPlaceholder: 'Search notifications...' }
            });
        } catch (e) { console.error('DataTable init failed (notificationTable)', e); }
    }
});
</script>
@endsection
