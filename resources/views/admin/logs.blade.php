@extends('components.default')
@section('title', 'Return Logs - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

<div class="page-bg min-h-screen md:ml-64">
    <x-ui.page-header eyebrow="Return Logs" title="History" />

    <main class="p-4 sm:p-6 space-y-5 max-w-content mx-auto">
        <x-ui.table-card>
            @if($logs->isEmpty())
                <div class="py-16 text-center">
                    <i class="fas fa-book text-4xl text-neutral-300 mb-3 block"></i>
                    <p class="text-sm font-medium text-neutral-700">No return logs yet</p>
                    <p class="text-xs text-neutral-500 mt-1">When borrowers return equipment, the logs will appear here.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table id="logsTable" class="w-full display nowrap text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-neutral-500 bg-neutral-50">
                                <th class="text-left px-4 py-3 font-semibold">Borrower</th>
                                <th class="text-left px-4 py-3 font-semibold">Equipment</th>
                                <th class="text-left px-4 py-3 font-semibold">Condition</th>
                                <th class="text-left px-4 py-3 font-semibold">Remarks</th>
                                <th class="text-left px-4 py-3 font-semibold">Return date</th>
                                <th class="text-left px-4 py-3 font-semibold">Received by</th>
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
                                    <td class="px-4 py-3 text-neutral-600 max-w-xs truncate" title="{{ $log->remarks }}">{{ $log->remarks }}</td>
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
