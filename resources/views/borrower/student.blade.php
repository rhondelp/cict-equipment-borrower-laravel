@extends('components.default')

@section('title', 'Instructor - CICT Equipment Borrower System')

@section('content')
<div class="dash-bg min-h-screen flex flex-col">
    <header class="sticky top-0 z-40 dash-header">
        <div class="flex flex-wrap items-center justify-between px-6 py-4 gap-3">
            <div class="flex items-center gap-3">
                <div>
                    <p class="text-xs font-medium tracking-widest uppercase" style="color:var(--text-muted)">Instructor</p>
                    <p class="text-sm font-semibold tracking-tight text-white">Equipment Management</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button id="open-add-modal" class="btn-primary inline-flex items-center gap-2 !py-2 !px-4 !text-sm !rounded-xl">
                    <i class="fas fa-plus text-xs"></i> Request Item
                </button>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl border border-red-500/20 bg-red-500/10 text-red-300 hover:bg-red-500/20 transition">
                        <i class="fas fa-sign-out-alt text-xs"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="flex-1 p-6 md:p-8 space-y-6 max-w-content w-full mx-auto">
        <section>
            <h2 class="flex items-center gap-2 mb-3 text-sm font-bold tracking-tight text-white">
                <span class="w-7 h-7 rounded-lg bg-primary-500/15 border border-primary-500/20 grid place-items-center"><i class="fas fa-list text-primary-300 text-xs"></i></span> My Equipment Requests
            </h2>
            <x-ui.table-card>
                <table id="requestTable" class="w-full display nowrap">
                    <thead>
                        <tr><th>Equipment</th><th>Quantity</th><th>Status</th><th>Remarks</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $request)
                            <tr>
                                <td class="font-medium text-white">{{ $request->equipment->equipment_name }}</td>
                                <td class="tabular-nums">{{ $request->quantity }}</td>
                                <td>
                                    @php $variant = ['Approved'=>'success','Declined'=>'danger'][$request->status] ?? 'warning'; @endphp
                                    <x-ui.badge :status="$request->status" :variant="$variant" />
                                </td>
                                <td class="text-neutral-400">{{ $request->remarks ?? '—' }}</td>
                                <td>
                                    <div class="flex gap-2">
                                        <button class="px-3 py-1.5 rounded-lg bg-primary-600 hover:bg-primary-500 text-white text-xs font-semibold edit-btn" data-id="{{ $request->id }}" data-equipment-name="{{ $request->equipment->equipment_name }}" data-quantity="{{ $request->quantity }}" data-status="{{ $request->status }}" data-remarks="{{ $request->remarks }}"><i class="fas fa-edit mr-1"></i>Edit</button>
                                        <button class="px-3 py-1.5 rounded-lg bg-danger-600 hover:bg-danger-500 text-white text-xs font-semibold delete-btn" data-id="{{ $request->id }}" data-equipment-name="{{ $request->equipment->equipment_name }}"><i class="fas fa-trash mr-1"></i>Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.table-card>
        </section>

        <section>
            <h2 class="flex items-center gap-2 mb-3 text-sm font-bold tracking-tight text-white">
                <span class="w-7 h-7 rounded-lg bg-primary-500/10 border border-primary-500/15 grid place-items-center"><i class="fas fa-history text-primary-300 text-xs"></i></span> My Borrow Transactions
            </h2>
            <x-ui.table-card>
                <table id="transactionTable" class="w-full display nowrap">
                    <thead>
                        <tr><th>Equipment</th><th>Quantity</th><th>Borrow Date</th><th>Return Date</th><th>Purpose</th><th>Status</th><th>Remarks</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $tx)
                            <tr>
                                <td class="font-medium text-white">{{ $tx->equipment->equipment_name ?? '—' }}</td>
                                <td class="tabular-nums">{{ $tx->quantity }}</td>
                                <td class="tabular-nums">{{ $tx->borrow_date }}</td>
                                <td class="tabular-nums">{{ $tx->return_date ?? '—' }}</td>
                                <td class="max-w-[14rem] truncate">{{ $tx->purpose }}</td>
                                <td>@php $variant=['Borrowed'=>'warning','Returned'=>'success','Overdue'=>'danger'][$tx->status]??'neutral'; @endphp<x-ui.badge :status="$tx->status" :variant="$variant" /></td>
                                <td class="text-neutral-400">{{ $tx->remarks ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.table-card>
        </section>
    </main>
</div>

@include('components.instructor.request-item-modal')
@include('components.instructor.update-request-modal')
@include('components.instructor.delete-request-modal')

<script>
    $(document).ready(function () {
        try {
            if (window.initAppTable) {
                window.initAppTable('#requestTable', { language: { search: "", searchPlaceholder: "Search..." } });
                window.initAppTable('#transactionTable', { language: { search: "", searchPlaceholder: "Search..." } });
            } else {
                $('#requestTable, #transactionTable').DataTable({ responsive: true, autoWidth: false, pageLength: 10, lengthMenu: [[10,25,50,-1],[10,25,50,"All"]], language: { search: "", searchPlaceholder: "Search..." } });
            }
        } catch(e) { console.error('DataTable init failed (student)', e); }
        $('#requestTable').on('click', '.edit-btn', function() {
            $('#edit-id').val($(this).data('id')); $('#edit-equipment-name').text($(this).data('equipment-name')); $('#edit-quantity').val($(this).data('quantity')); $('#edit-status').val($(this).data('status')); $('#edit-remarks').val($(this).data('remarks'));
            $('#edit-modal').removeClass('hidden').addClass('flex');
        });
        $('#requestTable').on('click', '.delete-btn', function () { $('#delete-item-name').text($(this).data('equipment-name')); $('#delete-form').attr('action', '/borrower/request/' + $(this).data('id')); $('#delete-modal').removeClass('hidden').addClass('flex'); });
        $(document).on('click', '#confirm-delete', function () { $('#delete-form').submit(); });
        $(document).on('click', '#open-add-modal', function() { $('#add-modal').removeClass('hidden').addClass('flex'); });
        $(document).on('click', '#cancel-add, #cancel-edit, #cancel-delete', function() { $('#add-modal, #edit-modal, #delete-modal').addClass('hidden').removeClass('flex'); });
        $(document).on('click', '#add-modal, #edit-modal, #delete-modal', function(e) { if (e.target === this) $(this).addClass('hidden').removeClass('flex'); });
    });
</script>
@endsection
