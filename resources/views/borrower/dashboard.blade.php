@extends('components.default')

@section('title', 'Borrower - CICT Equipment Borrower System')

@section('content')
<div class="flex flex-col min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
        <div class="flex flex-wrap items-center justify-between px-4 py-4 md:px-8">
            <!-- Left: Logo + Title -->
            <div class="flex items-center space-x-4">
                <!-- Logo + Text -->
                <div class="flex items-center space-x-3">
                    <img class="object-contain w-auto h-12 md:h-14"
                        src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png"
                        alt="CICT Logo">

                    <div class="flex flex-col">
                        <h1 class="text-xl font-semibold tracking-tight text-gray-900 md:text-2xl">
                            Borrower Dashboard
                        </h1>
                        <p class="mt-0.5 text-sm text-gray-500">
                            Request equipment and track your borrowings
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right: Buttons -->
            <div class="flex items-center mt-3 space-x-3 md:mt-0">
                <button id="open-add-modal"
                    class="flex items-center rounded-lg bg-brand px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2">
                    <i class="mr-2 fas fa-plus"></i>
                    Request Item
                </button>

                <form method="POST" action="{{ route('logout') }}" id="logout-form" class="hidden">
                    @csrf
                </form>

                <button type="button" id="logout-btn"
                    class="flex items-center rounded-lg border border-red-600 px-5 py-2 text-sm font-semibold text-red-600 transition-colors hover:bg-red-600 hover:text-white">
                    <i class="mr-2 fas fa-sign-out-alt"></i> Logout
                </button>

            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 space-y-6 p-4 md:p-8">
        {{-- Flash messages + validation errors --}}
        <x-ui.feedback />

        <!-- Equipment Requests -->
        <section>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="flex items-center text-base font-semibold text-gray-900">
                    <i class="mr-2 text-brand fas fa-list"></i> My Equipment Requests
                </h2>
                <p class="text-xs text-gray-500">Pending requests can still be edited or cancelled.</p>
            </div>
            <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                @if ($requests->isEmpty())
                    <x-ui.empty-state icon="fa-clipboard-list" title="No equipment requests yet"
                        hint="When you need something for class, click Request Item above and pick the equipment you need." />
                @else
                <table id="requestTable" class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Equipment Name</th>
                            <th class="px-4 py-3">Quantity</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Remarks</th>
                            <th class="px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $request)
                            <tr class="transition border-b hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $request->equipment->equipment_name }}</td>
                                <td class="px-4 py-3">{{ $request->quantity }}</td>
                                <td class="px-4 py-3">
                                    <x-ui.status-badge :status="$request->status" />
                                </td>
                                <td class="px-4 py-3">{{ $request->remarks ?? '---' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @if ($request->status === 'Pending')
                                            <button type="button"
                                                class="rounded-lg bg-brand px-3 py-1 text-xs text-white md:text-sm hover:bg-brand-dark edit-btn"
                                                data-id="{{ $request->id }}"
                                                data-equipment-name="{{ $request->equipment->equipment_name }}"
                                                data-quantity="{{ $request->quantity }}"
                                                data-status="{{ $request->status }}"
                                                data-remarks="{{ $request->remarks }}">
                                                <i class="mr-1 fas fa-edit"></i>Edit
                                            </button>
                                            <button type="button"
                                                class="rounded-lg border border-red-600 px-3 py-1 text-xs text-red-600 md:text-sm transition-colors hover:bg-red-600 hover:text-white delete-btn"
                                                data-id="{{ $request->id }}"
                                                data-equipment-name="{{ $request->equipment->equipment_name }}">
                                                <i class="mr-1 fas fa-trash"></i>Cancel
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400 italic"
                                                title="This request has been {{ strtolower($request->status) }} and can no longer be changed.">
                                                Locked ({{ strtolower($request->status) }})
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </section>

        <!-- Borrow Transactions -->
        <section>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="flex items-center text-base font-semibold text-gray-900">
                    <i class="mr-2 text-brand fas fa-history"></i> My Borrow Transactions
                </h2>
                <p class="text-xs text-gray-500">Return equipment on or before the return date to avoid overdue notices.</p>
            </div>
            <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                @if ($transactions->isEmpty())
                    <x-ui.empty-state icon="fa-box-open" title="No borrow transactions yet"
                        hint="Once the admin releases equipment to you, your loans and their return dates will appear here." />
                @else
                <table id="transactionTable" class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Equipment</th>
                            <th class="px-4 py-3">Quantity</th>
                            <th class="px-4 py-3">Borrow Date</th>
                            <th class="px-4 py-3">Return Date</th>
                            <th class="px-4 py-3">Purpose</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $tx)
                            @php
                                $rd = $tx->return_date ? \Carbon\Carbon::parse($tx->return_date)->startOfDay() : null;
                                $isOverdueTx = $tx->status === 'Borrowed' && $rd && $rd->lt(\Carbon\Carbon::today());
                                $isDueToday = $tx->status === 'Borrowed' && $rd && $rd->eq(\Carbon\Carbon::today());
                            @endphp
                            <tr class="transition border-b hover:bg-gray-50 {{ $isOverdueTx ? 'bg-red-50/60' : '' }}">
                                <td class="px-4 py-3">{{ $tx->equipment->equipment_name ?? '---' }}</td>
                                <td class="px-4 py-3">{{ $tx->quantity }}</td>
                                <td class="px-4 py-3">{{ $tx->borrow_date }}</td>
                                <td class="px-4 py-3">
                                    <span class="whitespace-nowrap">{{ $tx->return_date ?? '---' }}</span>
                                    @if ($isOverdueTx)
                                        <span class="ml-1 inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700 ring-1 ring-inset ring-red-600/20">
                                            Past due
                                        </span>
                                    @elseif ($isDueToday)
                                        <span class="ml-1 inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-semibold text-orange-700 ring-1 ring-inset ring-orange-600/20">
                                            Due today
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $tx->purpose }}</td>
                                <td class="px-4 py-3">
                                    <x-ui.status-badge :status="$tx->status" />
                                </td>
                                <td class="px-4 py-3">{{ $tx->remarks ?? '---' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </section>
    </main>
</div>

{{-- Logout Confirmation Modal --}}
<div id="logout-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50">
    <div class="modal-fade w-full max-w-sm rounded-lg bg-white p-6 shadow-lg mx-4">
        <h3 class="mb-4 text-base font-semibold text-gray-900">Confirm Logout</h3>
        <p class="mb-6 text-sm text-gray-600">Are you sure you want to log out?</p>
        <div class="flex justify-end space-x-3">
            <button id="cancel-logout" class="rounded-lg bg-gray-100 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                Cancel
            </button>
            <button id="confirm-logout" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700">
                Logout
            </button>
        </div>
    </div>
</div>

{{-- Modal reopen state after validation failures --}}
@php
    $hasErrors = isset($errors) && $errors->any();
    $addOpen = $hasErrors && !old('id');
    $editOpen = $hasErrors && (bool) old('id');
@endphp

{{-- Modals --}}
@include('components.instructor.request-item-modal')
@include('components.instructor.update-request-modal')
@include('components.instructor.delete-request-modal')

{{-- Scripts --}}
<script>
    $(document).ready(function () {
        $('#requestTable, #transactionTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            language: {
                search: "",
                searchPlaceholder: "Search..."
            }
        });

        // Edit modal
        $('.edit-btn').on('click', function() {
            $('#edit-id').val($(this).data('id'));
            $('#edit-equipment-name').text($(this).data('equipment-name'));
            $('#edit-quantity').val($(this).data('quantity'));
            $('#edit-status').val($(this).data('status'));
            $('#edit-remarks').val($(this).data('remarks'));
            $('#edit-modal').removeClass('hidden');
        });

        // Cancel (delete) modal
        $('.delete-btn').on('click', function () {
            const id = $(this).data('id');
            const name = $(this).data('equipment-name');
            $('#delete-item-name').text(name);
            $('#delete-form').attr('action', '/borrower/request/' + id);
            $('#delete-modal').removeClass('hidden');
        });

        $('#confirm-delete').on('click', function () {
            $('#delete-form').submit();
        });

        // Add modal
        $('#open-add-modal').on('click', function() {
            $('#add-modal').removeClass('hidden');
        });

        // Cancel buttons
        $('#cancel-add, #cancel-edit, #cancel-delete').on('click', function() {
            $('#add-modal, #edit-modal, #delete-modal').addClass('hidden');
        });

        // Close on outside click
        $('#add-modal, #edit-modal, #delete-modal').on('click', function(e) {
            if (e.target === this) $(this).addClass('hidden');
        });
    });
</script>
<script>
    $(document).ready(function () {
        // Show the logout confirmation modal when logout button is clicked
        $('#logout-btn').on('click', function () {
            $('#logout-modal').removeClass('hidden');
        });

        // Handle clicking "Cancel" to close the modal
        $('#cancel-logout').on('click', function () {
            $('#logout-modal').addClass('hidden');
        });

        // Handle clicking "Logout" to submit the logout form
        $('#confirm-logout').on('click', function () {
            $('#logout-form').submit();
        });

        // Close the modal if clicked outside
        $('#logout-modal').on('click', function (e) {
            if (e.target === this) {
                $(this).addClass('hidden');
            }
        });
    });
</script>

@endsection
