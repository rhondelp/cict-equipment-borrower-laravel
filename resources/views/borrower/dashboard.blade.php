@extends('components.default')

@section('title', 'Borrower - CICT Equipment Borrower System')

@section('content')
<div class="dash-bg min-h-screen flex flex-col">
    <!-- Header — dense -->
    <header class="sticky top-0 z-40 dash-header">
        <div class="flex flex-wrap items-center justify-between px-4 py-3 gap-3">
            <div class="flex items-center gap-2.5">
                <img class="h-9 w-9 rounded-lg object-cover border border-white/10 bg-white/5" src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT">
                <div class="leading-none">
                    <h1 class="text-[11px] font-medium tracking-widest uppercase text-[#94a3b8]">Borrower</h1>
                    <p class="text-[13px] font-semibold tracking-tight text-white">Dashboard</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button id="open-add-modal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-[var(--accent)] text-white hover:bg-[var(--accent-strong)] transition">
                    <i class="fas fa-plus text-[10px]"></i> Request item
                </button>
                <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden">@csrf</form>
                <button type="button" id="logout-btn" class="px-3 py-1.5 rounded-lg text-xs font-medium border border-white/10 bg-white/5 text-[#cbd5e1] hover:bg-white/10 transition flex items-center gap-1.5">
                    <i class="fas fa-sign-out-alt text-[11px]"></i> Logout
                </button>
            </div>
        </div>
    </header>

    <!-- Main — denser, max-w 1440 -->
    <main class="flex-1 p-4 space-y-4 max-w-[1440px] w-full mx-auto">
        {{-- Alerts handled by global components.alerts --}}

        <section>
            <h2 class="flex items-center gap-2 mb-3 text-sm font-bold tracking-tight text-white">
                <span class="w-7 h-7 rounded-lg bg-blue-500/15 border border-blue-500/20 grid place-items-center"><i class="fas fa-list text-blue-400 text-xs"></i></span>
                My Equipment Requests
            </h2>
            <div class="dash-table-wrap p-3 overflow-x-auto">
                <table id="requestTable" class="w-full text-sm">
                    <thead class="text-[#94a3b8] text-xs tracking-widest uppercase">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium">Equipment</th>
                            <th class="px-3 py-2 text-left font-medium">Qty</th>
                            <th class="px-3 py-2 text-left font-medium">Status</th>
                            <th class="px-3 py-2 text-left font-medium">Remarks</th>
                            <th class="px-3 py-2 text-left font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-200 text-[13px]">
                        @foreach ($requests as $request)
                            <tr class="border-t border-white/5 hover:bg-white/[0.02] transition">
                                <td class="px-3 py-2 tabular-nums">{{ $request->equipment->equipment_name }}</td>
                                <td class="px-3 py-2 tabular-nums">{{ $request->quantity }}</td>
                                <td class="px-3 py-2">
                                    @php
                                        $statusColors = ['Approved' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/20','Declined' => 'bg-red-500/15 text-red-300 border-red-500/20','Pending' => 'bg-amber-500/15 text-amber-300 border-amber-500/20'];
                                        $statusColor = $statusColors[$request->status] ?? 'bg-white/5 text-slate-300 border-white/10';
                                    @endphp
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full border {{ $statusColor }}">{{ $request->status }}</span>
                                </td>
                                <td class="px-3 py-2 text-[#94a3b8]">{{ $request->remarks ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <button class="px-2.5 py-1 text-xs font-medium bg-[rgba(148,163,184,0.08)] text-[#cbd5e1] border border-[rgba(255,255,255,0.06)] rounded-md hover:bg-[rgba(148,163,184,0.12)] transition edit-btn" data-id="{{ $request->id }}" data-equipment-name="{{ $request->equipment->equipment_name }}" data-quantity="{{ $request->quantity }}" data-status="{{ $request->status }}" data-remarks="{{ $request->remarks }}">
                                        <i class="fas fa-edit text-[11px] mr-1"></i>Edit
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <h2 class="flex items-center gap-2 mb-3 text-xs font-medium tracking-widest uppercase text-[#94a3b8]">
                <span class="w-6 h-6 rounded-md bg-[rgba(91,141,224,0.10)] border border-[rgba(91,141,224,0.14)] grid place-items-center"><i class="fas fa-history text-[#8aa8e8] text-xs"></i></span>
                My borrow transactions
            </h2>
            <div class="dash-table-wrap p-3 overflow-x-auto">
                <table id="transactionTable" class="w-full text-sm">
                    <thead class="text-[#94a3b8] text-xs tracking-widest uppercase">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium">Equipment</th>
                            <th class="px-3 py-2 text-left font-medium">Qty</th>
                            <th class="px-3 py-2 text-left font-medium">Borrow</th>
                            <th class="px-3 py-2 text-left font-medium">Return</th>
                            <th class="px-3 py-2 text-left font-medium">Purpose</th>
                            <th class="px-3 py-2 text-left font-medium">Status</th>
                            <th class="px-3 py-2 text-left font-medium">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-200 text-[13px]">
                        @foreach ($transactions as $tx)
                            <tr class="border-t border-white/5 hover:bg-white/[0.02] transition">
                                <td class="px-3 py-2">{{ $tx->equipment->equipment_name ?? '—' }}</td>
                                <td class="px-3 py-2 tabular-nums">{{ $tx->quantity }}</td>
                                <td class="px-3 py-2 tabular-nums">{{ $tx->borrow_date }}</td>
                                <td class="px-3 py-2 tabular-nums">{{ $tx->return_date ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $tx->purpose }}</td>
                                <td class="px-3 py-2">
                                    @php $txColors = ['Borrowed'=>'bg-amber-500/15 text-amber-300 border-amber-500/20','Returned'=>'bg-emerald-500/15 text-emerald-300 border-emerald-500/20','Overdue'=>'bg-red-500/15 text-red-300 border-red-500/20']; $txColor = $txColors[$tx->status] ?? 'bg-white/5 text-slate-300'; @endphp
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full border {{ $txColor }}">{{ $tx->status }}</span>
                                </td>
                                <td class="px-3 py-2 text-[#94a3b8]">{{ $tx->remarks ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<!-- Logout Confirmation Modal — dark -->
<div id="logout-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4" style="display:none">
    <div class="w-full max-w-sm rounded-2xl border border-white/10 bg-[#131a2b] p-6 shadow-2xl">
        <h3 class="text-sm font-bold text-white">Confirm Logout</h3>
        <p class="text-sm text-[#8b93a8] mt-1">Are you sure you want to log out?</p>
        <div class="flex justify-end gap-3 mt-6">
            <button id="cancel-logout" class="px-4 py-2 rounded-xl text-sm font-medium bg-white/5 border border-white/10 text-slate-200 hover:bg-white/10">Cancel</button>
            <button id="confirm-logout" class="px-5 py-2 rounded-xl text-sm font-semibold bg-red-600 hover:bg-red-500 text-white">Logout</button>
        </div>
    </div>
</div>

@include('components.instructor.request-item-modal')
@include('components.instructor.update-request-modal')
@include('components.instructor.delete-request-modal')

<script>
    $(document).ready(function () {
        try {
            $('#requestTable, #transactionTable').DataTable({
                responsive: true, pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                language: { search: "🔍 ", searchPlaceholder: "Search..." }
            });
        } catch(e) { console.error('DataTable init failed (borrower)', e); }
        // Delegated — rows are re-rendered by DataTables, so direct binding breaks after pagination/search
        $('#requestTable').on('click', '.edit-btn', function() {
            $('#edit-id').val($(this).data('id'));
            $('#edit-equipment-name').text($(this).data('equipment-name'));
            $('#edit-quantity').val($(this).data('quantity'));
            $('#edit-status').val($(this).data('status'));
            $('#edit-remarks').val($(this).data('remarks'));
            $('#edit-modal').removeClass('hidden').addClass('flex');
        });
        $('#requestTable').on('click', '.delete-btn', function () {
            $('#delete-item-name').text($(this).data('equipment-name'));
            $('#delete-form').attr('action', '/borrower/request/' + $(this).data('id'));
            $('#delete-modal').removeClass('hidden').addClass('flex');
        });
        $(document).on('click', '#confirm-delete', function () { $('#delete-form').submit(); });
        $(document).on('click', '#open-add-modal', function() { $('#add-modal').removeClass('hidden').addClass('flex'); });
        $(document).on('click', '#cancel-add, #cancel-edit, #cancel-delete', function() {
            $('#add-modal, #edit-modal, #delete-modal').addClass('hidden').removeClass('flex');
        });
        $(document).on('click', '#add-modal, #edit-modal, #delete-modal', function(e) {
            if (e.target === this) $(this).addClass('hidden').removeClass('flex');
        });
    });
    $(document).ready(function () {
        $('#logout-btn').on('click', function () { $('#logout-modal').removeClass('hidden').addClass('flex').show(); });
        $('#cancel-logout').on('click', function () { $('#logout-modal').addClass('hidden').removeClass('flex').hide(); });
        $('#confirm-logout').on('click', function () { $('#logout-form').submit(); });
        $('#logout-modal').on('click', function (e) { if (e.target === this) $(this).addClass('hidden').removeClass('flex').hide(); });
    });
</script>
@endsection
