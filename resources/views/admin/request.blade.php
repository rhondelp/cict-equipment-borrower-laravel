@extends('components.default')

@section('title', 'Item Request - CICT Equipment Borrower System')

@section('content')
@include('components.admin.navbar')

<div class="dash-bg min-h-screen md:ml-80">

    <!-- Header — dense -->
    <header class="dash-header">
        <div class="flex items-center justify-between px-4 py-3">
            <div>
                <h1 class="text-[11px] font-medium tracking-widest uppercase text-[#94a3b8]">Requests</h1>
                <p class="text-[13px] font-semibold tracking-tight text-white -mt-0.5">Item requests</p>
            </div>
        </div>
    </header>

    <main class="p-4">
        {{-- Alerts handled by global components.alerts --}}
        <!-- DataTable — dense -->
        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table id="requestTable" class="w-full display nowrap">
                <thead class="bg-gray-50">
                    <tr>
                        <th>User</th>
                        <th>Equipment</th>
                        <th>Qty</th>
                        <th>Requested</th>
                        <th>Remarks</th>
                        <th>Status</th>
                        <th>Actions</th>
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
                                <div class="flex items-center gap-1.5">
                                        <form action="{{ route('admin.request.approve') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $request->id }}">
                                            <button type="submit" class="px-2.5 py-1 text-xs font-medium bg-[rgba(16,185,129,0.12)] text-emerald-300 border border-emerald-500/20 rounded-md hover:bg-[rgba(16,185,129,0.18)] transition">
                                                <i class="fas fa-check text-[11px]"></i> Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.request.decline') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $request->id }}">
                                            <button type="submit" class="px-2.5 py-1 text-xs font-medium bg-[rgba(239,68,68,0.10)] text-red-300 border border-red-500/20 rounded-md hover:bg-[rgba(239,68,68,0.16)] transition">
                                                <i class="fas fa-times text-[11px]"></i> Decline
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
