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
                <h1 class="text-xl font-semibold tracking-tight text-gray-900">Borrow Transactions</h1>
            </div>
            <button id="open-add-modal"
                class="flex items-center space-x-2 rounded-lg bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700">
                <i class="fas fa-plus"></i>
                <span>Add Transaction</span>
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
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
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
                    $isDueToday = $returnDate === now()->format('Y-m-d');
                    @endphp

                    <tr class="transition-colors duration-150 hover:bg-gray-50">

                        <td>{{ $tx->user->name ?? 'Deleted User' }}</td>

                        <td>{{ $tx->equipment->equipment_name ?? 'Deleted Equipment' }}</td>

                        <td>{{ \Carbon\Carbon::parse($tx->borrow_date)->format('Y-m-d') }}</td>

                        <!-- Return Date Column -->
                        <td {{ $isDueToday ? 'class="bg-red-50 font-semibold text-red-700"' : '' }}>
                            {{ $returnDate ?? 'N/A' }}
                        </td>

                        <td>{{ $tx->quantity }}</td>

                        <td>{{ $tx->purpose }}</td>

                        <td>
                            <select class="status-dropdown rounded-lg border px-3 py-1.5 text-sm font-medium transition
                        focus:outline-none focus:ring-2 focus:ring-blue-200
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
                            <div class="flex items-center space-x-2">

                                <!-- EDIT BUTTON -->
                                <button
                                    class="rounded-lg px-4 py-1 text-xs text-white bg-blue-600 md:text-sm hover:bg-blue-700 edit-btn"
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
                                    class="rounded-lg px-4 py-1 text-xs text-white bg-green-600 md:text-sm hover:bg-green-700 send-email-btn"
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

@endsection
