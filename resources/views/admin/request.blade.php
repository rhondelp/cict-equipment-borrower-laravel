@extends('components.default')

@section('title', 'Item Request - CICT Equipment Borrower System')

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
                    <h1 class="text-xl font-semibold tracking-tight text-gray-900">Item Requests</h1>
                    <p class="text-sm text-gray-500">Approve or decline borrowing requests</p>
                </div>
            </div>
        </div>
    </header>

    <main class="p-6">
        {{-- Flash messages + validation errors --}}
        <x-ui.feedback />

        <!-- Requests Table -->
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
            @if ($requests->isEmpty())
                <x-ui.empty-state icon="fa-clipboard-check" title="No item requests"
                    hint="New requests from students and instructors will appear here for approval." />
            @else
            <table id="requestTable" class="w-full display nowrap">
                <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500">
                    <tr>
                        <th>User</th>
                        <th>Equipment</th>
                        <th>Quantity</th>
                        <th>Requested Date</th>
                        <th>Remarks</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $request)
                        <tr class="transition-colors duration-150 hover:bg-gray-50">
                            <td>{{ $request->user->name ?? 'Deleted User' }}</td>
                            <td>{{ $request->equipment->equipment_name ?? 'Deleted Equipment' }}</td>
                            <td>{{ $request->quantity }}</td>
                            <td>{{ \Carbon\Carbon::parse($request->requested_date)->format('M j, Y') }}</td>
                            <td>{{ $request->remarks ?? '---' }}</td>
                            <td>
                                <x-ui.status-badge :status="$request->status" />
                            </td>
                            <td>
                                @if ($request->status === 'Pending')
                                    <div class="flex flex-wrap items-center gap-2">
                                        <!-- Approve Button -->
                                        <form action="{{ route('admin.request.approve') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $request->id }}">
                                            <button type="submit"
                                                class="rounded-lg bg-green-600 px-3 py-1 text-xs font-medium text-white hover:bg-green-700">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>

                                        <!-- Decline Button (destructive: asks first) -->
                                        <form action="{{ route('admin.request.decline') }}" method="POST" class="inline"
                                            data-confirm="Decline this request? The borrower will see it as declined.">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $request->id }}">
                                            <button type="submit"
                                                class="rounded-lg bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700">
                                                <i class="fas fa-times"></i> Decline
                                            </button>
                                        </form>
                                    </div>
                                @elseif ($request->status === 'Approved')
                                    <span class="text-xs italic text-gray-400">Approved - release via Borrow Transactions</span>
                                @else
                                    <span class="text-xs italic text-gray-400">No action needed</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </main>
</div>

<script>
$(document).ready(function () {
    $('#requestTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        language: { search: "", searchPlaceholder: "Search requests..." }
    });
});
</script>
@endsection
