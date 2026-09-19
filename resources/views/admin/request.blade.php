@extends('components.default')
@section('title', 'Item Request - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

<div class="min-h-[100dvh] page-bg md:ml-64">

    {{-- Essential for keyboard users: the sidebar is six links deep, so without
         this every page begins with six tab stops before the content. --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>
    <x-ui.page-header eyebrow="Requests" title="Item requests" />

    <main id="main-content" class="p-4 mx-auto space-y-5 sm:p-6 max-w-content">
        <x-ui.table-card>
            @if($requests->isEmpty())
                <x-ui.empty-state icon="fa-clipboard-list" title="No item requests"
                                  message="Requests from instructors and students land here for approval." />
            @else
                <div class="p-4 overflow-x-auto">
                    <table id="requestTable" class="w-full text-sm display nowrap">
                        <thead>
                            <tr class="text-sm tracking-wider uppercase text-neutral-600 bg-neutral-50">
                                <th class="px-4 py-3 font-semibold text-left">User</th>
                                <th class="px-4 py-3 font-semibold text-left">Equipment</th>
                                <th class="px-4 py-3 font-semibold text-left">Qty</th>
                                <th class="px-4 py-3 font-semibold text-left">Requested</th>
                                <th class="px-4 py-3 font-semibold text-left">Remarks</th>
                                <th class="px-4 py-3 font-semibold text-left">Status</th>
                                <th class="px-4 py-3 font-semibold text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @foreach ($requests as $request)
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-4 py-3 font-medium text-neutral-900">{{ $request->user->name ?? 'Deleted User' }}</td>
                                    <td class="px-4 py-3 text-neutral-700">{{ $request->equipment->equipment_name ?? 'Deleted Equipment' }}</td>
                                    <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ $request->quantity }}</td>
                                    <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ \Carbon\Carbon::parse($request->requested_date)->format('F j, Y') }}</td>
                                    <td class="px-4 py-3 text-neutral-600 max-w-[16rem] truncate" title="{{ $request->remarks }}">{{ $request->remarks ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @php $variant = ['Pending'=>'warning','Approved'=>'success','Declined'=>'danger'][$request->status] ?? 'neutral'; @endphp
                                        <x-ui.badge :status="$request->status" :variant="$variant" />
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($request->status === 'Pending')
                                            <div class="flex items-center gap-1.5">
                                                <form action="{{ route('admin.request.approve') }}" method="POST" class="inline approve-form">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $request->id }}">
                                                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-md bg-success-50 text-success-700 border border-success-200 hover:bg-success-100">
                                                        <i class="fas fa-check text-sm"></i> Approve
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.request.decline') }}" method="POST" class="inline decline-form">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $request->id }}">
                                                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-md bg-danger-50 text-danger-700 border border-danger-200 hover:bg-danger-100">
                                                        <i class="fas fa-times text-sm"></i> Decline
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-xs text-neutral-500">Processed</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.table-card>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableEl = document.getElementById('requestTable');
    if (tableEl && window.initAppTable) {
        try {
            window.initAppTable('#requestTable', {
                language: { search: '', searchPlaceholder: 'Search requests...' }
            });
        } catch (e) { console.error('DataTable init failed (requestTable)', e); }
    }

    // Confirm before approving / declining
    document.addEventListener('submit', function (e) {
        const f = e.target;
        if (!f || f.tagName !== 'FORM') return;
        if (f.classList.contains('approve-form')) {
            e.preventDefault();
            if (window.showConfirm) {
                window.showConfirm({
                    title: 'Approve this request?',
                    text: 'A borrow transaction will be created and stock will be deducted.',
                    icon: 'warning',
                    confirmText: 'Yes, approve'
                }).then(function (r) { if (r.isConfirmed) f.submit(); });
            } else { f.submit(); }
        } else if (f.classList.contains('decline-form')) {
            e.preventDefault();
            if (window.showConfirm) {
                window.showConfirm({
                    title: 'Decline this request?',
                    text: 'The borrower will see the request as declined.',
                    icon: 'warning',
                    confirmText: 'Yes, decline'
                }).then(function (r) { if (r.isConfirmed) f.submit(); });
            } else { f.submit(); }
        }
    });
});
</script>
@endsection
