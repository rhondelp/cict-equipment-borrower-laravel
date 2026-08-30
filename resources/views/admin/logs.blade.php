@extends('components.default')

@section('title', 'Return Logs - CICT Equipment Borrower System')

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
                        <h1 class="text-xs font-medium tracking-widest uppercase text-neutral-400">Return logs</h1>
                        <p class="text-sm font-semibold tracking-tight text-white -mt-0.5">History</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-4 space-y-4 max-w-content mx-auto">
            <x-ui.table-card>
                <table id="logsTable" class="w-full display nowrap">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Equipment</th>
                            <th>Condition</th>
                            <th>Remarks</th>
                            <th>Return date</th>
                            <th>Received by</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="font-medium text-white">{{ $log->borrower->name ?? 'N/A' }}</td>
                                <td>{{ $log->equipment->equipment_name ?? 'N/A' }}</td>
                                <td>
                                    @php $variant = $log->condition === 'Good' ? 'success' : 'warning'; @endphp
                                    <x-ui.badge :status="$log->condition" :variant="$variant" />
                                </td>
                                <td class="max-w-xs truncate text-neutral-300" title="{{ $log->remarks }}">{{ $log->remarks }}</td>
                                <td class="tabular-nums text-neutral-300">{{ \Carbon\Carbon::parse($log->return_date)->format('M j, Y') }}</td>
                                <td class="font-medium text-white">{{ $log->receiver->name ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.table-card>
        </main>
    </div>

    <script>
        $(document).ready(function() {
            try {
                let table = (window.initAppTable ? window.initAppTable('#logsTable', {
                    language: { search: "", searchPlaceholder: "Search logs..." }
                }) : $('#logsTable').DataTable({
                    responsive: true, autoWidth: false, pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    language: { search: "", searchPlaceholder: "Search logs..." }
                }));
            } catch(e) { console.error('DataTable init failed (logsTable)', e); }
        });
    </script>
@endsection
