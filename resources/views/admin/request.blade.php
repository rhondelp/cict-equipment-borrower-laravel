@extends('components.default')

@section('title', 'Item Request - CICT Equipment Borrower System')

@section('content')
@include('components.admin.navbar')

<div class="dash-bg min-h-screen md:ml-80">
    <header class="dash-header">
        <div class="flex items-center justify-between px-4 py-3">
            <div>
                <h1 class="text-xs font-medium tracking-widest uppercase text-neutral-400">Requests</h1>
                <p class="text-sm font-semibold tracking-tight text-white -mt-0.5">Item requests</p>
            </div>
        </div>
    </header>

    <main class="p-4 space-y-4 max-w-content mx-auto">
        <x-ui.table-card>
            <table id="requestTable" class="w-full display nowrap">
                <thead>
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
                        <tr>
                            <td class="font-medium text-white">{{ $request->user->name ?? 'Deleted User' }}</td>
                            <td>{{ $request->equipment->equipment_name ?? 'Deleted Equipment' }}</td>
                            <td class="tabular-nums">{{ $request->quantity }}</td>
                            <td class="tabular-nums">{{ \Carbon\Carbon::parse($request->requested_date)->format('F j, Y') }}</td>
                            <td class="text-neutral-400 max-w-[16rem] truncate" title="{{ $request->remarks }}">{{ $request->remarks ?? '—' }}</td>
                            <td>
                                @php $variant = ['Pending'=>'warning','Approved'=>'success','Declined'=>'danger'][$request->status] ?? 'neutral'; @endphp
                                <x-ui.badge :status="$request->status" :variant="$variant" />
                            </td>
                            <td>
                                <div class="flex items-center gap-1.5">
                                        <form action="{{ route('admin.request.approve') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $request->id }}">
                                            <button type="submit" class="px-2.5 py-1 text-xs font-semibold bg-success-500/15 text-success-300 border border-success-500/20 rounded-md hover:bg-success-500/20 transition">
                                                <i class="fas fa-check text-[11px]"></i> Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.request.decline') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $request->id }}">
                                            <button type="submit" class="px-2.5 py-1 text-xs font-semibold bg-danger-500/10 text-danger-300 border border-danger-500/20 rounded-md hover:bg-danger-500/15 transition">
                                                <i class="fas fa-times text-[11px]"></i> Decline
                                            </button>
                                        </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-ui.table-card>
    </main>
</div>

<script>
$(document).ready(function () {
    try {
        let table = (window.initAppTable ? window.initAppTable('#requestTable', {
            language: { search: "", searchPlaceholder: "Search requests..." }
        }) : $('#requestTable').DataTable({
            responsive: true, autoWidth: false, pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            language: { search: "", searchPlaceholder: "Search requests..." }
        }));
    } catch(e) { console.error('DataTable init failed (requestTable)', e); }
    $('.edit-btn').on('click', function() {});
    $('#approve-modal').on('click', function(e) {
        if (e.target === this) $(this).addClass('hidden');
    });
});
</script>
@endsection
