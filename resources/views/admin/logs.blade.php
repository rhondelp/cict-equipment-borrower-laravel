@extends('components.default')

@section('title', 'Return Logs - CICT Equipment Borrower System')

@section('content')
    @include('components.admin.navbar')

    <div class="dash-bg min-h-screen md:ml-80">
        <!-- Header — dense -->
        <header class="dash-header">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <button id="menu-toggle" class="text-[#94a3b8] hover:text-white md:hidden">
                        <i class="text-lg fas fa-bars"></i>
                    </button>
                    <div>
                        <h1 class="text-[11px] font-medium tracking-widest uppercase text-[#94a3b8]">Return logs</h1>
                        <p class="text-[13px] font-semibold tracking-tight text-white -mt-0.5">History</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content — denser -->
        <main class="p-4">
            {{-- Alerts handled by global components.alerts --}}

            <!-- Logs Table — dense -->
                <div class="dash-table-wrap p-3">
                <table id="logsTable" class="w-full text-sm display nowrap">
                    <thead class="text-[#94a3b8] text-xs tracking-widest uppercase">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium">Borrower</th>
                            <th class="px-3 py-2 text-left font-medium">Equipment</th>
                            <th class="px-3 py-2 text-left font-medium">Condition</th>
                            <th class="px-3 py-2 text-left font-medium">Remarks</th>
                            <th class="px-3 py-2 text-left font-medium">Return date</th>
                            <th class="px-3 py-2 text-left font-medium">Received by</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr class="transition border-b hover:bg-gray-50">
                                <!-- Borrower -->
                                <td class="px-4 py-2 font-medium text-gray-800">
                                    {{ $log->borrower->name ?? 'N/A' }}
                                </td>

                                <!-- Equipment -->
                                <td class="px-4 py-2">
                                    {{ $log->equipment->equipment_name ?? 'N/A' }}
                                </td>

                                <!-- Condition -->
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $log->condition === 'Good' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $log->condition }}
                                    </span>
                                </td>

                                <!-- Remarks -->
                                <td class="max-w-xs px-4 py-2 truncate">
                                    {{ $log->remarks }}
                                </td>

                                <!-- Return Date -->
                                <td class="px-4 py-2 text-gray-600">
                                    {{ \Carbon\Carbon::parse($log->return_date)->format('M j, Y') }}
                                </td>

                                <!-- Receiver -->
                                <td class="px-4 py-2 text-gray-800">
                                    {{ $log->receiver->name ?? 'N/A' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
                </div>
        </main>
    </div>

    {{-- DataTables Script --}}
    <script>
        $(document).ready(function() {
            $('#logsTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                language: {
                    search: "🔍 ",
                    searchPlaceholder: "Search logs..."
                }
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
