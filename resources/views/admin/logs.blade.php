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
            @if (session('success'))
                <div class="px-4 py-3 mb-6 text-green-800 bg-green-100 border-l-4 border-green-500 rounded shadow-sm" role="alert">
                    <div class="flex items-center">
                        <i class="mr-2 fas fa-check-circle"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            <!-- Logs Table -->
                <table id="logsTable" class="w-full text-sm display nowrap">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left">BORROWER</th>
                            <th class="px-4 py-2 text-left">EQUIPMENT</th>
                            <th class="px-4 py-2 text-left">CONDITION</th>
                            <th class="px-4 py-2 text-left">REMARKS</th>
                            <th class="px-4 py-2 text-left">RETURN DATE</th>
                            <th class="px-4 py-2 text-left">RECEIVED BY</th>
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
