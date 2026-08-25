@extends('components.default')

@section('title', 'Borrow Transactions - CICT Equipment Borrower System')

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
                    <h1 class="text-xl font-semibold tracking-tight text-gray-900">Borrow Transactions</h1>
                    <p class="text-sm text-gray-500">Track loans and process returns</p>
                </div>
            </div>
            <button id="open-add-modal"
                class="flex items-center space-x-2 rounded-lg bg-brand px-4 py-2 font-medium text-white transition-colors hover:bg-brand-dark">
                <i class="fas fa-plus"></i>
                <span>Add Transaction</span>
            </button>
        </div>
    </header>

    <main class="p-6">
        {{-- Flash messages + validation errors --}}
        <x-ui.feedback />

        <!-- DataTable -->
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
            @if ($transactions->isEmpty())
                <x-ui.empty-state icon="fa-exchange-alt" title="No borrow transactions"
                    hint="Transactions you create will appear here. Use Add Transaction to release equipment." />
            @else
            <table id="transactions-table" class="w-full border-collapse display nowrap stripe hover responsive">
                <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500">
                    <tr>
                        <th>User</th>
                        <th>Equipment</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Quantity</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Class Sched</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transactions as $tx)
                    @php
                    $returnDate = $tx->return_date ? \Carbon\Carbon::parse($tx->return_date)->format('Y-m-d') : null;
                    $rd = $returnDate ? \Carbon\Carbon::parse($returnDate)->startOfDay() : null;
                    $isOverdueTx = $tx->status === 'Borrowed' && $rd && $rd->lt(\Carbon\Carbon::today());
                    $isDueToday = $tx->status === 'Borrowed' && $rd && $rd->eq(\Carbon\Carbon::today());
                    @endphp

                    <tr class="transition-colors duration-150 hover:bg-gray-50 {{ $isOverdueTx ? 'bg-red-50/60' : '' }}">

                        <td>{{ $tx->user->name ?? 'Deleted User' }}</td>

                        <td>{{ $tx->equipment->equipment_name ?? 'Deleted Equipment' }}</td>

                        <td>{{ \Carbon\Carbon::parse($tx->borrow_date)->format('Y-m-d') }}</td>

                        <!-- Return Date Column -->
                        <td>
                            <span class="whitespace-nowrap">{{ $returnDate ?? 'N/A' }}</span>
                            @if ($isOverdueTx)
                                <span class="ml-1 inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700 ring-1 ring-inset ring-red-600/20">Past due</span>
                            @elseif ($isDueToday)
                                <span class="ml-1 inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-semibold text-orange-700 ring-1 ring-inset ring-orange-600/20">Due today</span>
                            @endif
                        </td>

                        <td>{{ $tx->quantity }}</td>

                        <td>{{ $tx->purpose }}</td>

                        <td>
                            <select class="status-dropdown rounded-lg border px-3 py-1.5 text-sm font-medium transition
                        focus:outline-none focus:ring-2 focus:ring-brand/20
                        {{ $tx->status === 'Borrowed' ? 'border-blue-300 text-blue-700 bg-blue-50' : '' }}
                        {{ $tx->status === 'Returned' ? 'border-green-500 text-green-700 bg-green-50' : '' }}
                        {{ $tx->status === 'Overdue' ? 'border-red-500 text-red-700 bg-red-50' : '' }}"
                                data-id="{{ $tx->id }}">
                                <option value="Borrowed" {{ $tx->status === 'Borrowed' ? 'selected' : '' }}>
                                    Borrowed
                                </option>

                                <option value="Returned" {{ $tx->status === 'Returned' ? 'selected' : '' }}>
                                    Returned
                                </option>

                                <option value="Overdue" {{ $tx->status === 'Overdue' ? 'selected' : '' }}>
                                    Overdue
                                </option>
                            </select>
                        </td>

                        <td>{{ $tx->remarks ?? '---' }}</td>

                        <td>
                            @if ($tx->classSchedule)
                            {{ $tx->classSchedule->schedule_time }}
                            - {{ $tx->classSchedule->instructor?->name ?? 'No Instructor' }}
                            - {{ $tx->classSchedule->room }}
                            @else
                            No Schedule
                            @endif
                        </td>

                        <td>
                            <div class="flex flex-wrap items-center gap-2">

                                <!-- EDIT BUTTON -->
                                <button type="button"
                                    class="rounded-lg bg-brand px-3 py-1 text-xs font-medium text-white hover:bg-brand-dark edit-btn"
                                    data-id="{{ $tx->id }}" data-user="{{ $tx->user->id ?? '' }}"
                                    data-equipment="{{ $tx->equipment->id ?? '' }}"
                                    data-borrow="{{ \Carbon\Carbon::parse($tx->borrow_date)->format('Y-m-d') }}"
                                    data-return="{{ $returnDate }}" data-quantity="{{ $tx->quantity }}"
                                    data-purpose="{{ $tx->purpose }}" data-status="{{ $tx->status }}"
                                    data-remarks="{{ $tx->remarks ?? '' }}"
                                    data-class="{{ $tx->classSchedule->id ?? '' }}">
                                    <i class="fas fa-edit"></i> Edit
                                </button>

                                <!-- SEND EMAIL BUTTON -->
                                <button type="button"
                                    class="rounded-lg bg-green-600 px-3 py-1 text-xs font-medium text-white hover:bg-green-700 send-email-btn"
                                    data-id="{{ $tx->id }}" data-user-email="{{ $tx->user->email ?? '' }}">
                                    <i class="fas fa-envelope"></i> Email
                                </button>

                                <!-- DELETE BUTTON -->
                                <button type="button"
                                    class="rounded-lg border border-red-600 px-3 py-1 text-xs font-medium text-red-600 transition-colors hover:bg-red-600 hover:text-white delete-btn"
                                    data-id="{{ $tx->id }}"
                                    data-name="{{ ($tx->user->name ?? 'Unknown') . ' - ' . ($tx->equipment->equipment_name ?? 'Unknown') }}">
                                    <i class="fas fa-trash"></i> Delete
                                </button>

                            </div>
                        </td>

                    </tr>
                    @endforeach
                </tbody>

            </table>
            @endif
        </div>
    </main>
</div>

<!-- Modals -->

@include('components.admin.transaction.email-modal')
@include('components.admin.transaction.add-modal')
@include('components.admin.transaction.edit-modal')
@include('components.admin.transaction.delete-modal')
@include('components.admin.transaction.returnlog-modal')

{{-- Dynamic quantity fields when multiple equipment selected --}}
<script>
    document.getElementById('equipment-select').addEventListener('change', function() {
    const equipmentIds = Array.from(this.selectedOptions).map(option => option.value);
    const quantitiesDiv = document.getElementById('equipment-quantities');

    quantitiesDiv.innerHTML = '';

    equipmentIds.forEach((equipmentId) => {
        const option = this.querySelector(`option[value="${equipmentId}"]`);
        // Option text is "ID | Equipment Name"
        const parts = option ? option.textContent.split('|') : [];
        const equipmentName = parts.length > 1 ? parts.slice(1).join('|').trim() : ('#' + equipmentId);

        const quantityField = document.createElement('div');
        quantityField.classList.add('space-y-2');
        quantityField.innerHTML = `
            <label class="block text-sm font-medium text-gray-700">Quantity for ${equipmentName}</label>
            <input type="number" name="quantities[${equipmentId}]" min="1" required
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
        `;
        quantitiesDiv.appendChild(quantityField);
    });
});
</script>

<script>
    $(document).ready(function () {
    let table = $('#transactions-table').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        scrollX: true,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        columnDefs: [
                { responsivePriority: 1, targets: 0 },
                { responsivePriority: 2, targets: -1 },
            ],
        language: {
            search: "",
            searchPlaceholder: "Search transactions..."
        }
    });

    // Add modal
    $('#open-add-modal').on('click', function() {
        $('#add-modal').removeClass('hidden');
    });

    // Edit modal
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
        $('#delete-form').attr('action', '/admin/transactions/' + id);
        $('#delete-modal').removeClass('hidden');
    });

    // Cancel buttons
    $('#cancel-add, #cancel-edit, #cancel-delete').on('click', function() {
        $('#add-modal, #edit-modal, #delete-modal').addClass('hidden');
    });

    // Close when clicking outside
    $('#add-modal, #edit-modal, #delete-modal').on('click', function(e) {
        if (e.target === this) $(this).addClass('hidden');
    });
});
</script>

{{-- Inline status updates: selecting "Returned" opens a return-log prompt first --}}
<script>
    $(document).ready(function () {

    // Remember the value before the user changes it, so we can roll back on cancel
    let pendingSelect = null;

    $('.status-dropdown').on('focus', function () {
        $(this).data('prev', $(this).val());
    });

    $('.status-dropdown').change(function () {
        let status = $(this).val();
        let id = $(this).data('id');
        pendingSelect = this;

        if (status === "Returned") {
            $('#return-transaction-id').val(id);
            $('#returnLogModal').removeClass('hidden');
        } else {
            updateStatus(this, id, status);
        }
    });

    // Cancel modal: roll the select back to its previous value
    $('#cancelReturn').click(function () {
        if (pendingSelect) {
            pendingSelect.value = $(pendingSelect).data('prev');
        }
        $('#returnLogModal').addClass('hidden');
    });

    // Submit modal
    $('#returnLogForm').submit(function (e) {
        e.preventDefault();

        let id = $('#return-transaction-id').val();
        let condition = $('#return-condition').val();
        let remarks = $('#return-remarks').val();

        updateStatus(pendingSelect, id, "Returned", condition, remarks);
        $('#returnLogModal').addClass('hidden');
    });

    function updateStatus(selectEl, id, status, condition = null, remarks = null) {
        if (selectEl) selectEl.disabled = true;

        $.ajax({
            url: "{{ route('transactions.inlineUpdate') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                id: id,
                status: status,
                condition: condition,
                remarks: remarks
            },
            success: function () {
                // Controller replies with a redirect; treat any completed response as success.
                window.toast('Status updated successfully.', 'success');
                window.setTimeout(function () { location.reload(); }, 600);
            },
            error: function (xhr) {
                let message = 'Could not update the status. Please try again.';
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (res && res.message) message = res.message;
                } catch (e) {}
                if (selectEl) {
                    selectEl.disabled = false;
                    selectEl.value = $(selectEl).data('prev');
                }
                window.toast(message, 'error');
            }
        });
    }
});
</script>

{{-- Email modal logic --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
    let selectedTransactionId = null;

    // Open modal
    document.querySelectorAll('.send-email-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            selectedTransactionId = this.getAttribute('data-id');
            const userEmail = this.getAttribute('data-user-email');

            document.getElementById('modalEmail').value = userEmail;
            document.getElementById('modalMessage').value = "";

            document.getElementById('emailModal').classList.remove('hidden');
        });
    });

    // Show/hide custom message box
    document.getElementById('emailType').addEventListener('change', function () {
        document.getElementById('customMessageBox').classList.toggle('hidden', this.value !== 'custom');
    });

    // Close modal
    document.getElementById('closeEmailModal').addEventListener('click', () => {
        document.getElementById('emailModal').classList.add('hidden');
    });

    // Send email
    document.getElementById('sendEmailConfirm').addEventListener('click', () => {
        const type = document.getElementById('emailType').value;
        const message = document.getElementById('modalMessage').value;
        const sendBtn = document.getElementById('sendEmailConfirm');

        if (type === 'custom' && !message.trim()) {
            window.toast('Please write a message first.', 'error');
            return;
        }

        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-1"></i>Sending...';

        fetch(`/send-email/${selectedTransactionId}`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                type: type,
                message: message
            })
        })
        .then(res => res.json().then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            sendBtn.disabled = false;
            sendBtn.innerHTML = 'Send Email';
            if (ok) {
                document.getElementById('emailModal').classList.add('hidden');
                window.toast(data.message || 'Email sent successfully!', 'success');
            } else {
                window.toast(data.message || 'Could not send the email.', 'error');
            }
        })
        .catch(() => {
            sendBtn.disabled = false;
            sendBtn.innerHTML = 'Send Email';
            window.toast('Could not send the email. Please try again.', 'error');
        });
    });

    // Close email modal on outside click
    document.getElementById('emailModal').addEventListener('click', function (e) {
        if (e.target === this) this.classList.add('hidden');
    });
});
</script>
@endsection
