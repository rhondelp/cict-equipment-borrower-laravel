@extends('components.default')
@section('title', 'Users - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

<div class="dash-bg min-h-screen md:ml-80">
    <!-- Header -->
    <header class="dash-header">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center space-x-4">
                <button id="menu-toggle" class="text-gray-500 hover:text-gray-700 md:hidden">
                    <i class="text-xl fas fa-bars"></i>
                </button>
                <div>
                    <h1 class="text-2xl font-bold text-white">USER MANAGEMENT</h1>
                    <p class="text-sm text-gray-500">Manage all users of the system</p>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <!-- Add User Button -->
                <button id="open-add-sched-modal"
                    class="flex items-center px-4 py-2 space-x-2 font-medium text-white transition-colors duration-200 bg-green-500 hover:bg-green-600 rounded-xl">
                    <i class="fas fa-calendar-check"></i>
                    <span>Add Schedule</span>
                </button>
                <!-- Add User Button -->
                <button id="open-add-modal"
                    class="flex items-center px-4 py-2 space-x-2 font-medium text-white transition-colors duration-200 bg-blue-500 hover:bg-blue-600 rounded-xl">
                    <i class="fas fa-user-plus"></i>
                    <span>Add User</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="p-6">
        {{-- Alerts handled by global components.alerts --}}

        <!-- Users Table -->
        <div class="overflow-x-auto">
            <table id="users-table" class="w-full display nowrap">
                <thead class="bg-gray-50">
                    <tr>
                        <th>NAME</th>
                        <th>EMAIL</th>
                        <th>USER TYPE</th>
                        <th>CONTACT</th>
                        <th>Class Schedule</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                    <tr class="transition-colors duration-150 ease-in-out hover:bg-blue-50">
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->user_type }}</td>
                        <td>{{ $user->contact_number ?? 'N/A' }}</td>
                        <td>
                            @if ($user->classSchedules->count() > 0)
                            <ul class="text-sm text-gray-700">
                                @foreach ($user->classSchedules as $sched)
                                <li>
                                    {{ $sched->subject_code }} - {{ $sched->subject_name }}
                                    ({{ $sched->schedule_time }})
                                    - Room: {{ $sched->room }}
                                </li>
                                @endforeach
                            </ul>
                            @else
                            <span class="text-gray-400">No schedules</span>
                            @endif
                        </td>

                        <td>
                            <button
                                class="px-4 py-1 text-xs text-white bg-blue-600 md:text-sm hover:bg-blue-700 edit-btn"
                                data-id="{{ $user->id }}" data-name="{{ $user->name }}" data-email="{{ $user->email }}"
                                data-user-type="{{ $user->user_type }}" data-contact="{{ $user->contact_number }}">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            {{-- <button
                                class="px-4 py-1 text-xs text-white bg-red-600 md:text-sm hover:bg-red-700 delete-btn"
                                data-id="{{ $user->id }}" data-name="{{ $user->name }}">
                                <i class="fas fa-trash"></i> Delete
                            </button> --}}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Add Schedule Modal -->
<div id="add-sched-modal" class="fixed inset-0 z-[60] flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="w-full max-w-lg mx-4 overflow-hidden bg-white shadow-2xl rounded-xl">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-white">➕ Add Class Schedule</h3>
            <button type="button" class="text-gray-400 cancel-sched hover:text-gray-600">✕</button>
        </div>

        <!-- Form -->
        <form action="{{ route('admin.add-sched') }}" method="POST" class="p-6 space-y-5">
            @csrf

            <!-- Instructor -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Instructor</label>
                <select name="user_id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    @foreach ($instructors as $inst)
                    <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Year Level -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Year Level</label>
                <input type="text" name="year_level" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Block Name -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Block Name</label>
                <input type="text" name="block_name" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Subject Code -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Subject Code</label>
                <input type="text" name="subject_code" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Subject Name -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Subject Name</label>
                <input type="text" name="subject_name" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Schedule Time -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Schedule Time</label>
                <input type="text" name="schedule_time" required placeholder="e.g., Mon/Wed 8:00 AM - 10:00 AM"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Room -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Room</label>
                <input type="text" name="room" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Actions -->
            <div class="flex justify-end pt-4 space-x-3 border-t border-gray-200">
                <button type="button"
                    class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg cancel-sched hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700">
                    Add Schedule
                </button>
            </div>
        </form>
    </div>
</div>



<!-- Add User Modal — z-[60] so it sits above sidebar (which is z-50) -->
<div id="add-modal" class="fixed inset-0 z-[60] flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="w-full max-w-lg mx-4 overflow-hidden bg-white shadow-2xl rounded-xl">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-white">➕ Add User</h3>
            <button type="button" class="cancel-add text-gray-400 transition hover:text-gray-600" aria-label="Close">
                ✕
            </button>
        </div>

        <!-- Form -->
        <form action="{{ route('admin.user.register') }}" method="POST" class="p-6 space-y-5">
            @csrf

            <!-- Name -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" name="name" required
                    class="w-full px-4 py-2 transition border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Email -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" name="email" required
                    class="w-full px-4 py-2 transition border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- User Type -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">User Type</label>
                <select name="user_type" required
                    class="w-full px-4 py-2 transition border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="" disabled selected>-- Select User Type --</option>
                    <option value="Admin">Admin</option>
                    <option value="Instructor">Instructor</option>
                    <option value="Student">Student</option>
                </select>
            </div>

            <!-- Contact -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Contact Number</label>
                <input type="text" name="contact_number"
                    class="w-full px-4 py-2 transition border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Password -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-2 transition border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full px-4 py-2 transition border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end pt-4 space-x-3 border-t border-gray-200">
                <button type="button" id="cancel-add"
                    class="px-4 py-2 text-gray-600 transition bg-gray-100 rounded-lg hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit"
                    class="px-5 py-2 text-white transition bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700">
                    Add User
                </button>
            </div>
        </form>
    </div>
</div>


<!-- Edit User Modal -->
<div id="edit-modal" class="fixed inset-0 z-[60] flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="w-full max-w-md mx-4 bg-white shadow-2xl rounded-xl">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-white">Edit User</h3>
        </div>
        <form id="edit-form" action="{{ route('admin.users.update') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="id" id="edit-id">
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" id="edit-name"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="edit-email"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">User Type</label>
                <select name="user_type" id="edit-user-type"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="Admin">Admin</option>
                    <option value="Instructor">Instructor</option>
                    <option value="Student">Student</option>
                </select>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Contact Number</label>
                <input type="text" name="contact_number" id="edit-contact"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Password (leave blank to keep
                    current)</label>
                <input type="password" name="password" id="edit-password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Confirm Password</label>
                <input type="password" name="password_confirmation" id="edit-password-confirmation"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex justify-end pt-4 space-x-3 border-t">
                <button type="button" id="cancel-edit"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="px-4 py-2 text-white bg-blue-500 rounded-lg hover:bg-blue-600">Save
                    Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 z-[60] flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="w-full max-w-md mx-4 bg-white shadow-2xl rounded-xl">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-white">Delete User</h3>
        </div>
        <div class="p-6">
            <p class="text-gray-600">Are you sure you want to delete <span id="delete-item-name"
                    class="font-semibold"></span>? This action cannot be undone.</p>
        </div>
        <form id="delete-form" method="POST" action="">
            @csrf
            @method('DELETE')
        </form>
        <div class="flex justify-end px-6 py-4 space-x-3 border-t border-gray-200">
            <button type="button" id="cancel-delete" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
            <button type="button" id="confirm-delete"
                class="px-4 py-2 text-white bg-red-500 rounded-lg hover:bg-red-600">Delete</button>
        </div>
    </div>
</div>

{{-- Scripts — delegated so buttons survive DataTables redraw; z-[60] modals sit above sidebar --}}
<script>
    $(document).ready(function() {
    let table = $('#users-table').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        language: {
            search: "🔍 ",
            searchPlaceholder: "Search users..."
        }
    });

    // Open Add Modal — delegated (button outside table)
    $(document).on('click', '#open-add-modal', function() {
        $('#add-modal').removeClass('hidden');
    });

    // Open Schedule modal
    $(document).on('click', '#open-add-sched-modal', function () {
        $('#add-sched-modal').removeClass('hidden');
    });

    // Close Schedule modal
    $(document).on('click', '.cancel-sched', function () {
        $('#add-sched-modal').addClass('hidden');
    });


    $(document).on('click', '#cancel-add, #cancel-edit, #cancel-delete, .cancel-add', function() {
        $('#add-modal, #edit-modal, #delete-modal').addClass('hidden');
    });

    // Edit User — delegated from table (rows are re-rendered by DataTables)
    // Note: data-user-type uses hyphen (standard HTML5), jQuery .data('user-type') reads it
    $('#users-table').on('click', '.edit-btn', function() {
        $('#edit-id').val($(this).data('id'));
        $('#edit-name').val($(this).data('name'));
        $('#edit-email').val($(this).data('email'));
        // Support both hyphen and legacy underscore
        const userType = $(this).data('user-type') || $(this).data('user_type') || $(this).attr('data-user-type');
        $('#edit-user-type').val(userType);
        $('#edit-contact').val($(this).data('contact'));
        $('#edit-modal').removeClass('hidden');
    });

    // Delete User — delegated
    $('#users-table').on('click', '.delete-btn', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        let name = $(this).data('name');
        $('#delete-item-name').text(name);
        $('#delete-form').attr('action', '/admin/users/' + id);
        $('#delete-modal').removeClass('hidden');
    });

    $(document).on('click', '#confirm-delete', function() {
        $('#delete-form').submit();
    });

    // Close modal when clicking outside
    $(document).on('click', '#add-modal, #edit-modal, #delete-modal, #add-sched-modal', function(e) {
        if (e.target === this) $(this).addClass('hidden');
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
