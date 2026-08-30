@extends('components.default')

@section('title', 'Borrow Transactions - CICT Equipment Borrower System')

@section('content')
@include('components.admin.navbar')

<div class="dash-bg min-h-screen md:ml-80">
    <header class="dash-header">
        <div class="flex items-center justify-between px-4 py-3">
            <div>
                <h1 class="text-xs font-medium tracking-widest uppercase text-neutral-400">Transactions</h1>
                <p class="text-sm font-semibold tracking-tight text-white -mt-0.5">Borrow & returns</p>
            </div>
            <button id="open-add-modal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-primary-500 text-white hover:bg-primary-600 transition">
                <i class="fas fa-plus text-[10px]"></i> Add transaction
            </button>
        </div>
    </header>

    <main class="p-4 space-y-4 max-w-content mx-auto">
        <x-ui.table-card>
            <table id="transactions-table" class="w-full display nowrap">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Equipment</th>
                        <th>Borrow date</th>
                        <th>Return date</th>
                        <th>Qty</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Class sched</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transactions as $tx)
                    @php
                    $returnDate = $tx->return_date ? \Carbon\Carbon::parse($tx->return_date)->format('Y-m-d') : null;
                    $isDueToday = $returnDate === now()->format('Y-m-d');
                    $statusVariant = ['Borrowed' => 'warning', 'Returned' => 'success', 'Overdue' => 'danger'][$tx->status] ?? 'neutral';
                    @endphp
                    <tr>
                        <td class="font-medium text-white">{{ $tx->user->name ?? 'Deleted User' }}</td>
                        <td>{{ $tx->equipment->equipment_name ?? 'Deleted Equipment' }}</td>
                        <td class="tabular-nums">{{ \Carbon\Carbon::parse($tx->borrow_date)->format('Y-m-d') }}</td>
                        <td class="tabular-nums @if($isDueToday) text-danger-300 font-semibold @endif">{{ $returnDate ?? '—' }}</td>
                        <td class="tabular-nums">{{ $tx->quantity }}</td>
                        <td class="max-w-[14rem] truncate" title="{{ $tx->purpose }}">{{ $tx->purpose }}</td>
                        <td>
                            <select class="status-dropdown px-2.5 py-1 text-xs font-semibold rounded-full border focus:outline-none focus:ring-2 focus:ring-primary-500/30 bg-neutral-800 text-neutral-100 border-white/10
                        @if($tx->status === 'Borrowed') bg-warning-500/15 text-warning-300 border-warning-500/20 @endif
                        @if($tx->status === 'Returned') bg-success-500/15 text-success-300 border-success-500/20 @endif
                        @if($tx->status === 'Overdue') bg-danger-500/15 text-danger-300 border-danger-500/20 @endif"
                                data-id="{{ $tx->id }}">
                                <option value="Borrowed" {{ $tx->status === 'Borrowed' ? 'selected' : '' }}>Borrowed</option>
                                <option value="Returned" {{ $tx->status === 'Returned' ? 'selected' : '' }}>Returned</option>
                                <option value="Overdue" {{ $tx->status === 'Overdue' ? 'selected' : '' }}>Overdue</option>
                            </select>
                        </td>
                        <td class="text-neutral-400">{{ $tx->remarks ?? '—' }}</td>
                        <td class="text-sm">
                            @if ($tx->classSchedule)
                            {{ $tx->classSchedule->schedule_time }} - {{ $tx->classSchedule->instructor?->name ?? 'No Instructor' }} - {{ $tx->classSchedule->room }}
                            @else
                            <span class="text-neutral-400">No Schedule</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-1.5">
                                <button
                                    class="px-2.5 py-1 text-xs font-medium bg-neutral-700/40 text-neutral-200 border border-white/10 rounded-md hover:bg-neutral-700/60 transition edit-btn"
                                    data-id="{{ $tx->id }}" data-user="{{ $tx->user->id ?? '' }}"
                                    data-equipment="{{ $tx->equipment->id ?? '' }}"
                                    data-borrow="{{ \Carbon\Carbon::parse($tx->borrow_date)->format('Y-m-d') }}"
                                    data-return="{{ $returnDate }}" data-quantity="{{ $tx->quantity }}"
                                    data-purpose="{{ $tx->purpose }}" data-status="{{ $tx->status }}"
                                    data-remarks="{{ $tx->remarks ?? '' }}"
                                    data-class="{{ $tx->classSchedule->id ?? '' }}">
                                    <i class="fas fa-edit text-[11px]"></i> Edit
                                </button>
                                <button
                                    class="px-2.5 py-1 text-xs font-medium border border-white/10 bg-white/5 text-neutral-300 rounded-md hover:bg-white/10 transition send-email-btn"
                                    data-id="{{ $tx->id }}" data-user-email="{{ $tx->user->email ?? '' }}">
                                    <i class="fas fa-envelope text-[11px]"></i> Email
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </x-ui.table-card>
    </main>
</div>

@include('components.admin.transaction.email-modal')
@include('components.admin.transaction.add-modal')
@include('components.admin.transaction.edit-modal')
@include('components.admin.transaction.delete-modal')
@include('components.admin.transaction.returnlog-modal')

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sel = document.getElementById('equipment-select');
        if (!sel) return;
        sel.addEventListener('change', function() {
    const rawIds = Array.from(this.selectedOptions).map(option => option.value);
    const equipmentIds = rawIds.filter(v => v !== '' && v !== null);
    const quantitiesDiv = document.getElementById('equipment-quantities');
    if (!quantitiesDiv) return;
    quantitiesDiv.innerHTML = '';
    equipmentIds.forEach((equipmentId) => {
        const quantityField = document.createElement('div');
        quantityField.classList.add('space-y-2');
        quantityField.innerHTML = `
            <label class="block text-sm font-medium text-gray-700">Quantity for Equipment #${equipmentId}</label>
            <input type="number" name="quantities[${equipmentId}]" min="1" required
                class="w-full px-3 py-2 mt-1 transition border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
        `;
        quantitiesDiv.appendChild(quantityField);
    });
        });
    });
</script>

<script>
    $(document).ready(function () {
    try {
        let table = (window.initAppTable ? window.initAppTable('#transactions-table', {
            responsive: true, autoWidth: false, pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            language: { search: "", searchPlaceholder: "Search transactions..." },
            columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }],
        }) : $('#transactions-table').DataTable({
            responsive: true, autoWidth: false, pageLength: 10, scrollX: false,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }],
            language: { search: "", searchPlaceholder: "Search transactions..." }
        }));
    } catch(e) { console.error('DataTable init failed (transactions-table)', e); }

    $(document).on('click', '#open-add-modal', function() { $('#add-modal').removeClass('hidden'); });
    $('#transactions-table').on('click', '.edit-btn', function() {
        $('#edit-id').val($(this).data('id'));
        $('#edit-user').val($(this).data('user'));
        $('#edit-equipment').val($(this).data('equipment'));
        $('#edit-borrow').val($(this).data('borrow'));
        $('#edit-return').val($(this).data('return'));
        $('#edit-quantity').val($(this).data('quantity'));
        $('#edit-purpose').val($(this).data('purpose'));
        $('#edit-status').val($(this).data('status'));
        $('#edit-remarks').val($(this).data('remarks'));
        $('#edit-class').val($(this).data('class'));
        $('#edit-modal').removeClass('hidden');
    });
    $('#transactions-table').on('click', '.delete-btn', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        $('#delete-item-name').text(name);
        $('#delete-form').attr('action', '/admin/transaction/' + id);
        $('#delete-modal').removeClass('hidden');
    });
    $(document).on('click', '#cancel-add, #cancel-edit, #cancel-delete, .cancel-add', function() {
        $('#add-modal, #edit-modal, #delete-modal').addClass('hidden');
    });
    $(document).on('click', '#add-modal, #edit-modal, #delete-modal, #emailModal, #returnLogModal', function(e) {
        if (e.target === this) $(this).addClass('hidden');
    });
});
</script>

<script>
    $(document).ready(function () {
    $('#transactions-table').on('change', '.status-dropdown', function () {
        let status = $(this).val();
        let id = $(this).data('id');
        if (status === "Returned") {
            $('#return-transaction-id').val(id);
            $('#returnLogModal').removeClass('hidden');
        } else {
            updateStatus(id, status);
        }
    });
    $('#cancelReturn').click(function () { $('#returnLogModal').addClass('hidden'); });
    $('#returnLogForm').submit(function (e) {
        e.preventDefault();
        let id = $('#return-transaction-id').val();
        let condition = $('#return-condition').val();
        let remarks = $('#return-remarks').val();
        updateStatus(id, "Returned", condition, remarks);
        $('#returnLogModal').addClass('hidden');
    });
    function updateStatus(id, status, condition = null, remarks = null) {
        $.ajax({
            url: "{{ route('transactions.inlineUpdate') }}",
            method: "POST",
            data: { _token: "{{ csrf_token() }}", id: id, status: status, condition: condition, remarks: remarks },
            success: function (res) {
                showAlert('success', res.message || 'Status updated successfully!');
                setTimeout(function(){ location.reload(); }, 900);
            },
            error: function (xhr) {
                let msg = "Something went wrong. Please try again.";
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                else if (xhr.responseJSON && xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors).flat().join("\n");
                showAlert('error', msg);
            }
        });
    }
});
</script>
<script>
    let selectedTransactionId = null;
    $(document).on('click', '.send-email-btn', function () {
        selectedTransactionId = this.getAttribute('data-id');
        const userEmail = this.getAttribute('data-user-email');
        document.getElementById('modalEmail').value = userEmail || '';
        document.getElementById('modalMessage').value = "";
        document.getElementById('emailModal').classList.remove('hidden');
    });
    document.getElementById('emailType').addEventListener('change', function () {
        document.getElementById('customMessageBox').classList.toggle('hidden', this.value !== 'custom');
    });
    document.getElementById('closeEmailModal').addEventListener('click', () => {
        document.getElementById('emailModal').classList.add('hidden');
    });
    document.getElementById('sendEmailConfirm').addEventListener('click', () => {
        const type = document.getElementById('emailType').value;
        const message = document.getElementById('modalMessage').value;
        fetch(`/send-email/${selectedTransactionId}`, {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
            body: JSON.stringify({ type: type, message: message })
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to send email');
            return data;
        })
        .then(data => {
            showAlert('success', data.message || 'Email sent successfully!');
            document.getElementById('emailModal').classList.add('hidden');
        })
        .catch(err => { showAlert('error', err.message || 'Failed to send email'); });
    });
</script>
@endsection
