@extends('components.default')

@section('title', 'Item Request - CICT Equipment Borrower System')

@section('content')
@include('components.admin.navbar')

<div class="min-h-screen bg-gray-50 md:ml-80">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 shadow-sm">
        <div class="flex items-center justify-between px-6 py-4">
            <h1 class="text-xl font-bold text-gray-800">ITEM REQUEST</h1>
        </div>
    </header>

    <main class="p-6">
    @if ($errors->any())
        <div class="px-4 py-3 mb-6 text-red-800 bg-red-100 border-l-4 border-red-500 rounded shadow-sm" role="alert">
            <div class="flex items-center">
                <i class="mr-2 fas fa-exclamation-circle"></i>
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
    @if (session('success'))
        <div class="px-4 py-3 mb-6 text-green-800 bg-green-100 border-l-4 border-green-500 rounded shadow-sm" role="alert">
            <div class="flex items-center">
                <i class="mr-2 fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
        <!-- DataTable -->
        <div class="p-4 bg-white rounded-lg shadow">
            <table id="requestTable" class="w-full display nowrap">
                <thead class="bg-gray-50">
                    <tr>
                        <th>USER</th>
                        <th>UQUIPMENT</th>
                        <th>QUANTITY</th>
                        <th>REQUESTED DATE</th>
                        <th>REMARKS</th>
                        <th>STATUS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $request)
                        <tr class="transition-colors duration-150 hover:bg-blue-50">
                            <td>{{ $request->user->name ?? 'Deleted User' }}</td>
                            <td>{{ $request->equipment->equipment_name ?? 'Deleted Equipment' }}</td>
                            <td>{{ $request->quantity }}</td>
                            <td>{{ \Carbon\Carbon::parse($request->requested_date)->format('F j, Y') }}</td>
                            <td>{{ $request->remarks ?? '---' }}</td>
                            <td>
                                @php
                                    $statusColors = [
                                        'Pending' => 'bg-yellow-100 text-yellow-800',
                                        'Approved' => 'bg-green-100 text-green-800',
                                        'Declined' => 'bg-red-100 text-red-800',
                                    ];
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $request->status }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center space-x-2">
                                    {{-- @if ($request->status === 'pending') --}}
                                        <!-- Approve Button -->
                                        <form action="{{ route('admin.request.approve') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $request->id }}">
                                            <button type="submit"
                                                class="px-4 py-1 text-xs text-white bg-green-600 rounded md:text-sm hover:bg-green-700">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>

                                        <!-- Decline Button -->
                                        <form action="{{ route('admin.request.decline') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $request->id }}">
                                            <button type="submit"
                                                class="px-4 py-1 text-xs text-white bg-red-600 rounded md:text-sm hover:bg-red-700">
                                                <i class="fas fa-times"></i> Decline
                                            </button>
                                        </form>
                                    {{-- @else
                                        <span class="text-sm text-gray-500">No actions</span>
                                    @endif --}}
                                </div>
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </main>
</div>



<script>
$(document).ready(function () {
    let table = $('#requestTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        language: {
            search: "🔍 ",
            searchPlaceholder: "Search request..."
        }
    });



    // Approve modal
    $('.edit-btn').on('click', function() {

    });


    // Close when clicking outside
    $('#approve-modal').on('click', function(e) {
        if (e.target === this) $(this).addClass('hidden');
    });
});
</script>

    {{-- Enhanced Styles for DataTables --}}
    <style>
        .dataTables_filter input {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem;
            margin-left: 0.5rem;
        }
        .dataTables_length select {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem;
            margin-left: 0.5rem;
        }
        .dataTables_wrapper .dataTables_paginate {
            padding: 0;
            margin: 0;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            margin-left: 0.25rem;
            font-size: 0.875rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #e5e7eb;
            border-color: #d1d5db;
            color: #374151;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #2563eb;
            border-color: #2563eb;
            color: white;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            background: #f9fafb;
            color: #9ca3af;
            border-color: #d1d5db;
            cursor: not-allowed;
        }

        /* Custom spacing for table footer */
        #pagination-container {
            min-height: 2.5rem;
            display: flex;
            align-items: center;
        }

        /* Ensure proper spacing in table footer */
        .dataTables_wrapper .dataTables_info {
            padding: 0;
            margin: 0;
        }

        /* Modal content styling */
        .modal-content {
            pointer-events: auto;
        }
    </style>
@endsection
