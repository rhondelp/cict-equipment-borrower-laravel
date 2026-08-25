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
                <h1 class="text-xl font-semibold tracking-tight text-gray-900">Item Request</h1>
            </div>
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
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
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
                                                class="rounded-lg px-4 py-1 text-xs text-white bg-green-600 md:text-sm hover:bg-green-700">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>

                                        <!-- Decline Button -->
                                        <form action="{{ route('admin.request.decline') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $request->id }}">
                                            <button type="submit"
                                                class="rounded-lg px-4 py-1 text-xs text-white bg-red-600 md:text-sm hover:bg-red-700">
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
            search: "",
            searchPlaceholder: "Search request..."
        }
    });
});
</script>

@endsection
