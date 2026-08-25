@extends('components.default')

@section('title', 'Borrow Transactions - CICT Equipment Borrower System')

@section('content')
@include('components.admin.navbar')

<div class="min-h-screen bg-gray-50 md:ml-80">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 shadow-sm">
        <div class="flex items-center justify-between px-6 py-4">
            <h1 class="text-xl font-bold text-gray-800">BORROW TRANSACTIONS</h1>
            <button id="open-add-modal" class="px-4 py-2 text-white bg-blue-600 rounded-lg shadow hover:bg-blue-700">
                + Add Transaction
            </button>
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
        <div class="px-4 py-3 mb-6 text-green-800 bg-green-100 border-l-4 border-green-500 rounded shadow-sm"
            role="alert">
            <div class="flex items-center">
                <i class="mr-2 fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        @endif
        <!-- DataTable -->
        <div class="p-4 overflow-x-auto bg-white rounded-lg shadow">
            <table id="transactions-table" class="w-full border-collapse display nowrap stripe hover responsive">
                <thead class="bg-gray-50">
                    <tr>
                        <th>USER</th>
                        <th>EQUIPMENT</th>
                        <th>BORROW DATE</th>
                        <th>RETURN DATE</th>
                        <th>QUANTITY</th>
                        <th>PURPOSE</th>
                        <th>STATUS</th>
                        <th>REMARKS</th>
                        <th>CLASS SCHED</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transactions as $tx)
                    @php
                    $returnDate = $tx->return_date ? \Carbon\Carbon::parse($tx->return_date)->format('Y-m-d') : null;
                    $isDueToday = $returnDate === now()->format('Y-m-d');
                    @endphp

                    <tr class="transition-colors duration-150 hover:bg-blue-50">

                        <td>{{ $tx->user->name ?? 'Deleted User' }}</td>

                        <td>{{ $tx->equipment->equipment_name ?? 'Deleted Equipment' }}</td>

                        <td>{{ \Carbon\Carbon::parse($tx->borrow_date)->format('Y-m-d') }}</td>

                        <!-- Return Date Column -->
                        <td @if($isDueToday) style="background-color:#ffcccc; color:#a30000; font-weight:bold;" @endif>
                            {{ $returnDate ?? 'N/A' }}
                        </td>

                        <td>{{ $tx->quantity }}</td>

                        <td>{{ $tx->purpose }}</td>

                        <td>
                            <select class="px-3 py-2 text-sm font-medium transition border rounded-full status-dropdown
                        focus:outline-none focus:ring-2 focus:ring-blue-500
                        {{ $tx->status === 'Borrowed' ? 'border-yellow-400 text-yellow-600 bg-yellow-100' : '' }}
                        {{ $tx->status === 'Returned' ? 'border-green-500 text-green-600 bg-green-100' : '' }}
                        {{ $tx->status === 'Overdue' ? 'border-red-500 text-red-600 bg-red-100' : '' }}"
                                data-id="{{ $tx->id }}">
                                <option value="Borrowed" class="text-yellow-600" {{ $tx->status === 'Borrowed' ?
                                    'selected' : '' }}>
                                    Borrowed
                                </option>

                                <option value="Returned" class="text-green-600" {{ $tx->status === 'Returned' ?
                                    'selected' : '' }}>
                                    Returned
                                </option>

                                <option value="Overdue" class="text-red-600" {{ $tx->status === 'Overdue' ? 'selected' :
                                    '' }}>
                                    Overdue
                                </option>
                            </select>
                        </td>

                        <td>{{ $tx->remarks ?? '—' }}</td>

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
                            <div class="flex items-center space-x-2">

                                <!-- EDIT BUTTON -->
                                <button
                                    class="px-4 py-1 text-xs text-white bg-blue-600 md:text-sm hover:bg-blue-700 edit-btn"
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
                                <button
                                    class="px-4 py-1 text-xs text-white bg-green-600 md:text-sm hover:bg-green-700 send-email-btn"
                                    data-id="{{ $tx->id }}" data-user-email="{{ $tx->user->email ?? '' }}">
                                    <i class="fas fa-envelope"></i> Send Email
                                </button>

                            </div>
                        </td>

                    </tr>
                    @endforeach
                </tbody>

            </table>
        </div>
    </main>
</div>

<!-- Modals -->


@include('components.admin.transaction.email-modal')
@include('components.admin.transaction.add-modal')
@include('components.admin.transaction.edit-modal')
@include('components.admin.transaction.delete-modal')
@include('components.admin.transaction.returnlog-modal')


<script>
    document.getElementById('equipment-select').addEventListener('change', function() {
    const equipmentIds = Array.from(this.selectedOptions).map(option => option.value);
    const quantitiesDiv = document.getElementById('equipment-quantities');


    quantitiesDiv.innerHTML = '';


    equipmentIds.forEach((equipmentId) => {
        const quantityField = document.createElement('div');
        quantityField.classList.add('space-y-2');
        quantityField.innerHTML = `
            <label class="block text-sm font-medium text-gray-700">Quantity for Equipment #${equipmentId}</label>
            <input type="number" name="quantities[${equipmentId}]" min="1" required
                class="w-full px-3 py-2 mt-1 transition border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
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
            search: "🔍 ",
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

<script>
    $(document).ready(function () {
    $('.status-dropdown').change(function () {
        let status = $(this).val();
        let id = $(this).data('id');

        if (status === "Returned") {
            $('#return-transaction-id').val(id);
            $('#returnLogModal').removeClass('hidden');
        } else {
            updateStatus(id, status);
        }
    });

    // Cancel modal
    $('#cancelReturn').click(function () {
        $('#returnLogModal').addClass('hidden');
    });

    // Submit modal
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
            data: {
                _token: "{{ csrf_token() }}",
                id: id,
                status: status,
                condition: condition,
                remarks: remarks
            },
            success: function (res) {
                Swal.fire({
                    title: 'Success!',
                    text: res.message,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#10B981'
                }).then(() => {
                    location.reload();
                });
            },
            error: function (xhr) {
                alert("Error: " + xhr.responseText);
            }
        });
    }
});

</script>
<script>
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
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            document.getElementById('emailModal').classList.add('hidden');
        });
    });
</script>

{{-- Enhanced Styles for DataTables --}}
<style>
    .dataTables_filter input {
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.5rem;
        margin-left: 0.5rem;
    }

    .dataTables_length select {
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.5rem;
        margin-left: 0.5rem;
    }

    .dataTables_wrapper .dataTables_paginate {
        padding: 0;
        margin: 0;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        margin-left: 0.25rem;
        font-size: 0.875rem;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #e5e7eb;
        border-color: #d1d5db;
        color: #374151;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #2563eb;
        border-color: #2563eb;
        color: white;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        background: #f9fafb;
        color: #9ca3af;
        border-color: #d1d5db;
        cursor: not-allowed;
    }

    /* Custom spacing for table footer */
    #pagination-container {
        min-height: 2.5rem;
        display: flex;
        align-items: center;
    }

    /* Ensure proper spacing in table footer */
    .dataTables_wrapper .dataTables_info {
        padding: 0;
        margin: 0;
    }

    /* Modal content styling */
    .modal-content {
        pointer-events: auto;
    }
</style>
@endsection
