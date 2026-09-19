@extends('components.default')
@section('title', 'Notifications - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

<div class="min-h-[100dvh] page-bg md:ml-64">

    {{-- Essential for keyboard users: the sidebar is six links deep, so without
         this every page begins with six tab stops before the content. --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>
    <x-ui.page-header eyebrow="Notifications" title="History" />

    <main id="main-content" class="p-4 sm:p-6 space-y-5 max-w-content mx-auto">
        <x-ui.table-card>
            @if($notifications->isEmpty())
                <x-ui.empty-state icon="fa-bell" title="No notifications sent yet"
                                  message="Return reminders and manual emails appear here once they go out." />
            @else
                <div class="overflow-x-auto">
                    <table id="notificationTable" class="w-full display nowrap text-sm">
                        <thead>
                            <tr class="text-sm tracking-wider uppercase text-neutral-600 bg-neutral-50">
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
