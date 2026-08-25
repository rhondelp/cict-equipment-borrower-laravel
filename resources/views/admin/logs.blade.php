@extends('components.default')

@section('title', 'Return Logs - CICT Equipment Borrower System')

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
                        <h1 class="text-2xl font-bold text-gray-900">RETURN LOGS</h1>
                        <p class="text-sm text-gray-500">History of returned equipment</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="p-6">
            {{-- Flash messages --}}
            <x-ui.feedback />

            <!-- Logs Table -->
                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                @if ($logs->isEmpty())
                    <x-ui.empty-state icon="fa-undo" title="No returns logged yet"
                        hint="Return records appear here when transactions are marked as Returned." />
                @else
                <table id="logsTable" class="w-full text-sm display nowrap">
                    <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Borrower</th>
                            <th class="px-4 py-3 text-left">Equipment</th>
                            <th class="px-4 py-3 text-left">Condition</th>
                            <th class="px-4 py-3 text-left">Remarks</th>
                            <th class="px-4 py-3 text-left">Return Date</th>
                            <th class="px-4 py-3 text-left">Received By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr class="transition border-b hover:bg-gray-50">
                                <!-- Borrower -->
                                <td class="px-4 py-3 font-medium text-gray-800">
                                    {{ $log->borrower->name ?? 'N/A' }}
                                </td>

                                <!-- Equipment -->
                                <td class="px-4 py-3">
                                    {{ $log->equipment->equipment_name ?? 'N/A' }}
                                </td>

                                <!-- Condition -->
                                <td class="px-4 py-3">
                                    <x-ui.status-badge :status="$log->condition" />
                                </td>

                                <!-- Remarks -->
                                <td class="max-w-xs px-4 py-3 truncate">
                                    {{ $log->remarks }}
                                </td>

                                <!-- Return Date -->
                                <td class="px-4 py-3 text-gray-600">
                                    {{ \Carbon\Carbon::parse($log->return_date)->format('M j, Y') }}
                                </td>

                                <!-- Receiver -->
                                <td class="px-4 py-3 text-gray-800">
                                    {{ $log->receiver->name ?? 'N/A' }}
                                </td>
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
            $('#logsTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                language: {
                    search: "",
                    searchPlaceholder: "Search logs..."
                }
            });
        });
    </script>
@endsection
