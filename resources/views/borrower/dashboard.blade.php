@extends('components.default')

@section('title', 'Borrower - CICT Equipment Borrower System')

@section('content')
<div class="dash-bg min-h-screen flex flex-col">
    <header class="sticky top-0 z-40 dash-header">
        <div class="flex flex-wrap items-center justify-between px-6 py-4 gap-3">
            <div class="flex items-center gap-2.5">
                <img class="h-9 w-9 rounded-xl object-cover border border-white/10 bg-white/5" src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT">
                <div class="leading-none">
                    <p class="text-xs font-medium tracking-widest uppercase" style="color:var(--text-muted)">Borrower</p>
                    <p class="text-sm font-semibold tracking-tight text-white">Dashboard</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button id="open-add-modal" class="btn-primary inline-flex items-center gap-2 !py-2 !px-4 !text-sm !rounded-xl">
                    <i class="fas fa-plus text-xs"></i> Request Item
                </button>
                <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden">@csrf</form>
                <button type="button" id="logout-btn" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl border border-white/10 bg-white/5 text-neutral-300 hover:bg-white/10 transition">
                    <i class="fas fa-sign-out-alt text-xs"></i> Logout
                </button>
            </div>
        </div>
    </header>

    <main class="flex-1 p-6 space-y-6 max-w-content w-full mx-auto">
        <section>
            <h2 class="flex items-center gap-2 mb-3 text-sm font-bold tracking-tight text-white">
                <span class="w-7 h-7 rounded-lg bg-primary-500/15 border border-primary-500/20 grid place-items-center"><i class="fas fa-list text-primary-300 text-xs"></i></span>
                My Equipment Requests
            </h2>
            <x-ui.table-card>
                <table id="requestTable" class="w-full display nowrap">
                    <thead>
                        <tr>
                            <th>Equipment</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $request)
                            <tr>
                                <td class="font-medium text-white">{{ $request->equipment->equipment_name }}</td>
                                <td class="tabular-nums">{{ $request->quantity }}</td>
                                <td>
                                    @php $variant = ['Approved'=>'success','Declined'=>'danger','Pending'=>'warning'][$request->status] ?? 'neutral'; @endphp
                                    <x-ui.badge :status="$request->status" :variant="$variant" />
                                </td>
                                <td class="text-neutral-400">{{ $request->remarks ?? '—' }}</td>
                                <td>
                                    <button class="px-2.5 py-1 text-xs font-medium bg-neutral-700/40 text-neutral-200 border border-white/10 rounded-md hover:bg-neutral-700/60 transition edit-btn" data-id="{{ $request->id }}" data-equipment-name="{{ $request->equipment->equipment_name }}" data-quantity="{{ $request->quantity }}" data-status="{{ $request->status }}" data-remarks="{{ $request->remarks }}">
                                        <i class="fas fa-edit text-[11px] mr-1"></i>Edit
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.table-card>
        </section>

        <section>
            <h2 class="flex items-center gap-2 mb-3 text-xs font-medium tracking-widest uppercase text-neutral-400">
                <span class="w-6 h-6 rounded-md bg-primary-500/10 border border-primary-500/15 grid place-items-center"><i class="fas fa-history text-primary-300 text-xs"></i></span>
                My borrow transactions
            </h2>
            <x-ui.table-card>
                <table id="transactionTable" class="w-full display nowrap">
                    <thead>
                        <tr>
                            <th>Equipment</th>
                            <th>Qty</th>
                            <th>Borrow</th>
                            <th>Return</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $tx)
                            <tr>
                                <td class="font-medium text-white">{{ $tx->equipment->equipment_name ?? '—' }}</td>
                                <td class="tabular-nums">{{ $tx->quantity }}</td>
                                <td class="tabular-nums">{{ $tx->borrow_date }}</td>
                                <td class="tabular-nums">{{ $tx->return_date ?? '—' }}</td>
                                <td class="max-w-[14rem] truncate">{{ $tx->purpose }}</td>
                                <td>
                                    @php $variant = ['Borrowed'=>'warning','Returned'=>'success','Overdue'=>'danger'][$tx->status] ?? 'neutral'; @endphp
                                    <x-ui.badge :status="$tx->status" :variant="$variant" />
                                </td>
                                <td class="text-neutral-400">{{ $tx->remarks ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.table-card>
        </section>
    </main>
</div>

<div id="logout-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4" style="display:none">
    <div class="w-full max-w-sm rounded-2xl border border-white/10 bg-neutral-900 p-6 shadow-2xl">
        <h3 class="text-sm font-bold text-white">Confirm Logout</h3>
        <p class="text-sm text-neutral-400 mt-1">Are you sure you want to log out?</p>
        <div class="flex justify-end gap-3 mt-6">
            <button id="cancel-logout" class="px-4 py-2 rounded-xl text-sm font-medium bg-white/5 border border-white/10 text-neutral-200 hover:bg-white/10">Cancel</button>
            <button id="confirm-logout" class="px-5 py-2 rounded-xl text-sm font-semibold bg-danger-500 hover:bg-danger-600 text-white">Logout</button>
        </div>
    </div>
</div>

@include('components.instructor.request-item-modal')
@include('components.instructor.update-request-modal')
@include('components.instructor.delete-request-modal')

<script>
    $(document).ready(function () {
        try {
            if (window.initAppTable) {
                window.initAppTable('#requestTable', { language: { search: "", searchPlaceholder: "Search requests..." } });
                window.initAppTable('#transactionTable', { language: { search: "", searchPlaceholder: "Search transactions..." } });
            } else {
                $('#requestTable, #transactionTable').DataTable({
                    responsive: true, autoWidth: false, pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    language: { search: "", searchPlaceholder: "Search..." }
                });
            }
        } catch(e) { console.error('DataTable init failed (borrower)', e); }
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
