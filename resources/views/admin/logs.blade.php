@extends('components.default')
@section('title', 'Return Logs - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

<div class="min-h-[100dvh] page-bg md:ml-64">

    {{-- Essential for keyboard users: the sidebar is six links deep, so without
         this every page begins with six tab stops before the content. --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>
    <x-ui.page-header eyebrow="Return Logs" title="History" />

    <main id="main-content" class="p-4 mx-auto space-y-5 sm:p-6 max-w-content">
        <x-ui.table-card>
            @if($logs->isEmpty())
                <x-ui.empty-state icon="fa-book" title="No return logs yet"
                                  message="When borrowers return equipment, the logs appear here." />
            @else
                <div class="p-4 overflow-x-auto">
                    <table id="logsTable" class="w-full text-sm display nowrap">
                        <thead>
                            <tr class="text-sm tracking-wider uppercase text-neutral-600 bg-neutral-50">
                                <th class="px-4 py-3 font-semibold text-left">Borrower</th>
                                <th class="px-4 py-3 font-semibold text-left">Equipment</th>
                                <th class="px-4 py-3 font-semibold text-left">Condition</th>
                                <th class="px-4 py-3 font-semibold text-left">Remarks</th>
                                <th class="px-4 py-3 font-semibold text-left">Return date</th>
                                <th class="px-4 py-3 font-semibold text-left">Received by</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @foreach ($logs as $log)
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-4 py-3 font-medium text-neutral-900">{{ $log->borrower->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-neutral-700">{{ $log->equipment->equipment_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        @php $variant = $log->condition === 'Good' ? 'success' : 'warning'; @endphp
                                        <x-ui.badge :status="$log->condition" :variant="$variant" />
                                    </td>
                                    <td class="max-w-xs px-4 py-3 truncate text-neutral-600" title="{{ $log->remarks }}">{{ $log->remarks }}</td>
                                    <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ \Carbon\Carbon::parse($log->return_date)->format('M j, Y') }}</td>
                                    <td class="px-4 py-3 font-medium text-neutral-900">{{ $log->receiver->name ?? 'N/A' }}</td>
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
    const tableEl = document.getElementById('logsTable');
    if (tableEl && window.initAppTable) {
        try {
            window.initAppTable('#logsTable', {
                language: { search: '', searchPlaceholder: 'Search logs...' }
            });
        } catch (e) { console.error('DataTable init failed (logsTable)', e); }
    }
});
</script>
@endsection
