@extends('components.default')

@section('title', 'Instructor - CICT Equipment Borrower System')

@section('content')
<div class="dash-bg min-h-screen flex flex-col">
    <header class="sticky top-0 z-40 dash-header">
        <div class="flex flex-wrap items-center justify-between px-4 py-4 md:px-8 gap-3">
            <div class="flex items-center gap-3">
                <button id="menu-toggle" class="text-[#8b93a8] hover:text-white md:hidden"><i class="text-xl fas fa-bars"></i></button>
                <div>
                    <h1 class="text-[15px] font-bold tracking-tight text-white">Equipment Management</h1>
                    <p class="text-xs text-[#8b93a8]">Manage all equipment and inventory</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button id="open-add-modal" class="btn-primary !w-auto px-5 py-2.5 text-sm rounded-xl"><i class="fas fa-plus text-xs"></i> Request Item</button>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold border border-red-500/20 bg-red-500/10 text-red-300 hover:bg-red-500/15 transition flex items-center gap-2"><i class="fas fa-sign-out-alt text-xs"></i> Logout</button>
                </form>
            </div>
        </div>
    </header>

    <main class="flex-1 p-4 md:p-8 space-y-8 max-w-[1400px] w-full mx-auto">
        {{-- Alerts handled by global components.alerts --}}

        <section>
            <h2 class="flex items-center gap-2 mb-3 text-sm font-bold tracking-tight text-white">
                <span class="w-7 h-7 rounded-lg bg-blue-500/15 border border-blue-500/20 grid place-items-center"><i class="fas fa-list text-blue-400 text-xs"></i></span> My Equipment Requests
            </h2>
            <div class="dash-table-wrap p-4 overflow-x-auto">
                <table id="requestTable" class="w-full text-sm">
                    <thead class="text-[#8b93a8] text-xs tracking-widest uppercase">
                        <tr><th class="px-4 py-3 text-left">Equipment Name</th><th class="px-4 py-3 text-left">Quantity</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-left">Remarks</th><th class="px-4 py-3 text-left">Actions</th></tr>
                    </thead>
                    <tbody class="text-slate-200">
                        @foreach ($requests as $request)
                            <tr class="border-t border-white/5 hover:bg-white/[0.02] transition">
                                <td class="px-4 py-3">{{ $request->equipment->equipment_name }}</td>
                                <td class="px-4 py-3">{{ $request->quantity }}</td>
                                <td class="px-4 py-3">
                                    @php $c = ['Approved'=>'bg-emerald-500/15 text-emerald-300 border-emerald-500/20','Declined'=>'bg-red-500/15 text-red-300 border-red-500/20']; $col=$c[$request->status]??'bg-white/5 text-slate-300 border-white/10'; @endphp
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full border {{ $col }}">{{ $request->status }}</span>
                                </td>
                                <td class="px-4 py-3 text-[#8b93a8]">{{ $request->remarks ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <button class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold edit-btn" data-id="{{ $request->id }}" data-equipment-name="{{ $request->equipment->equipment_name }}" data-quantity="{{ $request->quantity }}" data-status="{{ $request->status }}" data-remarks="{{ $request->remarks }}"><i class="fas fa-edit mr-1"></i>Edit</button>
                                        <button class="px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-500 text-white text-xs font-semibold delete-btn" data-id="{{ $request->id }}" data-equipment-name="{{ $request->equipment->equipment_name }}"><i class="fas fa-trash mr-1"></i>Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <h2 class="flex items-center gap-2 mb-3 text-sm font-bold tracking-tight text-white">
                <span class="w-7 h-7 rounded-lg bg-violet-500/15 border border-violet-500/20 grid place-items-center"><i class="fas fa-history text-violet-400 text-xs"></i></span> My Borrow Transactions
            </h2>
            <div class="dash-table-wrap p-4 overflow-x-auto">
                <table id="transactionTable" class="w-full text-sm">
                    <thead class="text-[#8b93a8] text-xs tracking-widest uppercase">
                        <tr><th class="px-4 py-3 text-left">Equipment</th><th class="px-4 py-3 text-left">Quantity</th><th class="px-4 py-3 text-left">Borrow Date</th><th class="px-4 py-3 text-left">Return Date</th><th class="px-4 py-3 text-left">Purpose</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-left">Remarks</th></tr>
                    </thead>
                    <tbody class="text-slate-200">
                        @foreach ($transactions as $tx)
                            <tr class="border-t border-white/5 hover:bg-white/[0.02] transition">
                                <td class="px-4 py-3">{{ $tx->equipment->equipment_name ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $tx->quantity }}</td>
                                <td class="px-4 py-3">{{ $tx->borrow_date }}</td>
                                <td class="px-4 py-3">{{ $tx->return_date ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $tx->purpose }}</td>
                                <td class="px-4 py-3">@php $txC=['Borrowed'=>'bg-amber-500/15 text-amber-300 border-amber-500/20','Returned'=>'bg-emerald-500/15 text-emerald-300 border-emerald-500/20','Overdue'=>'bg-red-500/15 text-red-300 border-red-500/20']; $col=$txC[$tx->status]??'bg-white/5 text-slate-300'; @endphp<span class="px-2.5 py-1 text-xs font-semibold rounded-full border {{ $col }}">{{ $tx->status }}</span></td>
                                <td class="px-4 py-3 text-[#8b93a8]">{{ $tx->remarks ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

@include('components.instructor.request-item-modal')
@include('components.instructor.update-request-modal')
@include('components.instructor.delete-request-modal')

<script>
    $(document).ready(function () {
        try { $('#requestTable, #transactionTable').DataTable({ responsive: true, pageLength: 10, lengthMenu: [[10,25,50,-1],[10,25,50,"All"]], language: { search: "🔍 ", searchPlaceholder: "Search..." } }); } catch(e) { console.error('DataTable init failed (student)', e); }
        // Delegated — survive DataTables redraw
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
