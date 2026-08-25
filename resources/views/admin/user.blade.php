@extends('components.default')
@section('title', 'Users - CICT Equipment Borrower System')
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
                    <h1 class="text-2xl font-bold text-gray-900">USER MANAGEMENT</h1>
                    <p class="text-sm text-gray-500">Manage all users of the system</p>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <!-- Add Schedule Button -->
                <button id="open-add-sched-modal"
                    class="flex items-center space-x-2 rounded-lg bg-brand px-4 py-2 font-medium text-white transition-colors duration-200 hover:bg-brand-dark">
                    <i class="fas fa-calendar-check"></i>
                    <span>Add Schedule</span>
                </button>
                <!-- Add User Button -->
                <button id="open-add-modal"
                    class="flex items-center space-x-2 rounded-lg bg-brand px-4 py-2 font-medium text-white transition-colors duration-200 hover:bg-brand-dark">
                    <i class="fas fa-user-plus"></i>
                    <span>Add User</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="p-6">
        {{-- Flash messages + validation errors --}}
        <x-ui.feedback />

        <!-- Users Table -->
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
            @if ($users->isEmpty())
                <x-ui.empty-state icon="fa-users" title="No users registered"
                    hint="Add instructors and students, or let them self-register." />
            @else
            <table id="users-table" class="w-full display nowrap">
                <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>User Type</th>
                        <th>Contact</th>
                        <th>Class Schedule</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                    <tr class="transition-colors duration-150 ease-in-out hover:bg-gray-50">
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td><x-ui.status-badge :status="$user->user_type" /></td>
                        <td>{{ $user->contact_number ?? 'N/A' }}</td>
                        <td>
                            @if ($user->classSchedules->count() > 0)
                            <div class="flex flex-col gap-1 py-1">
                                @foreach ($user->classSchedules as $sched)
                                <div class="w-fit max-w-xs rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-xs leading-relaxed text-gray-700">
                                    <span class="font-semibold">{{ $sched->subject_code }}</span>
                                    {{ $sched->subject_name }}<br>
                                    <span class="text-gray-500">{{ $sched->schedule_time }} · Room {{ $sched->room }}</span>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <span class="text-xs italic text-gray-400">No schedules</span>
                            @endif
                        </td>

                        <td>
                            <button type="button"
                                class="rounded-lg bg-brand px-4 py-1 text-xs font-medium text-white md:text-sm hover:bg-brand-dark edit-btn"
                                data-id="{{ $user->id }}" data-name="{{ $user->name }}" data-email="{{ $user->email }}"
                                data-user_type="{{ $user->user_type }}" data-contact="{{ $user->contact_number }}">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button type="button"
                                class="rounded-lg border border-red-600 px-4 py-1 text-xs font-medium text-red-600 md:text-sm transition-colors hover:bg-red-600 hover:text-white delete-btn"
                                data-id="{{ $user->id }}" data-name="{{ $user->name }}">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </main>
</div>

<!-- Add Schedule Modal -->
<div id="add-sched-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50">
    <div class="w-full max-w-lg mx-4 overflow-hidden bg-white shadow-lg rounded-lg">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-900">Add Class Schedule</h3>
            <button type="button" class="cancel-sched text-gray-400 transition hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Form -->
        <form action="{{ route('admin.add-sched') }}" method="POST" class="p-6 space-y-5">
            @csrf

            <!-- Instructor -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Instructor</label>
                <select name="user_id" required
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                    @foreach ($instructors as $inst)
                    <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Year Level -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Year Level</label>
                <input type="text" name="year_level" required
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            </div>

            <!-- Block Name -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Block Name</label>
                <input type="text" name="block_name" required
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            </div>

            <!-- Subject Code -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Subject Code</label>
                <input type="text" name="subject_code" required
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            </div>

            <!-- Subject Name -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Subject Name</label>
                <input type="text" name="subject_name" required
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            </div>

            <!-- Schedule Time -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Schedule Time</label>
                <input type="text" name="schedule_time" required placeholder="e.g., Mon/Wed 8:00 AM - 10:00 AM"
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            </div>

            <!-- Room -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Room</label>
                <input type="text" name="room" required
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            </div>

            <!-- Actions -->
            <div class="flex justify-end pt-4 space-x-3 border-t border-gray-200">
                <button type="button"
                    class="cancel-sched rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit" class="rounded-lg bg-brand px-5 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
                    Add Schedule
                </button>
            </div>
        </form>
    </div>
</div>



<!-- Add User Modal -->
<div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50">
    <div class="w-full max-w-lg mx-4 overflow-hidden bg-white shadow-lg rounded-lg">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-900">Add User</h3>
            <button type="button" data-dismiss="#add-modal" class="text-gray-400 transition hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Form -->
        <form action="{{ route('admin.user.register') }}" method="POST" class="p-6 space-y-5">
            @csrf

            <!-- Name -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" name="name" required
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            </div>

            <!-- Email -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" name="email" required
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            </div>

            <!-- User Type -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">User Type</label>
                <select name="user_type" required
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
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
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            </div>

            <!-- Password -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" required
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end pt-4 space-x-3 border-t border-gray-200">
                <button type="button" id="cancel-add"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit"
                    class="rounded-lg bg-brand px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-dark">
                    Add User
                </button>
            </div>
        </form>
    </div>
</div>


<!-- Edit User Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50">
    <div class="w-full max-w-md mx-4 bg-white shadow-lg rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Edit User</h3>
        </div>
        <form id="edit-form" action="{{ route('admin.users.update') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="id" id="edit-id">
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" id="edit-name"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="edit-email"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">User Type</label>
                <select name="user_type" id="edit-user_type"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                    <option value="Admin">Admin</option>
                    <option value="Instructor">Instructor</option>
                    <option value="Student">Student</option>
                </select>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Contact Number</label>
                <input type="text" name="contact_number" id="edit-contact"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Password (leave blank to keep
                    current)</label>
                <input type="password" name="password" id="edit-password"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Confirm Password</label>
                <input type="password" name="password_confirmation" id="edit-password-confirmation"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            </div>

            <div class="flex justify-end pt-4 space-x-3 border-t">
                <button type="button" id="cancel-edit"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Cancel</button>
                <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">Save
                    Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50">
    <div class="w-full max-w-md mx-4 bg-white shadow-lg rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Delete User</h3>
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
            <button type="button" id="cancel-delete" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Cancel</button>
            <button type="button" id="confirm-delete"
                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Delete</button>
        </div>
    </div>
</div>

{{-- Scripts --}}
<script>
    $(document).ready(function() {
    let table = $('#users-table').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        language: {
            search: "",
            searchPlaceholder: "Search users..."
        }
    });

    // Open Add Modal
    $('#open-add-modal').on('click', function() {
        $('#add-modal').removeClass('hidden');
    });

    // Open modal
    $('#open-add-sched-modal').on('click', function () {
        $('#add-sched-modal').removeClass('hidden');
    });

    // Close modal
    $('.cancel-sched').on('click', function () {
        $('#add-sched-modal').addClass('hidden');
    });


    $('#cancel-add, #cancel-edit, #cancel-delete').on('click', function() {
        $('#add-modal, #edit-modal, #delete-modal').addClass('hidden');
    });

    // Edit User
    $('.edit-btn').on('click', function() {
        $('#edit-id').val($(this).data('id'));
        $('#edit-name').val($(this).data('name'));
        $('#edit-email').val($(this).data('email'));
        $('#edit-user_type').val($(this).data('user_type'));
        $('#edit-contact').val($(this).data('contact'));
        $('#edit-modal').removeClass('hidden');
    });

    // Delete User
    $('.delete-btn').on('click', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        let name = $(this).data('name');
        $('#delete-item-name').text(name);
        $('#delete-form').attr('action', '/admin/users/' + id);
        $('#delete-modal').removeClass('hidden');
    });

    $('#confirm-delete').on('click', function() {
        $('#delete-form').submit();
    });

    // Close modal when clicking outside
    $('#add-modal, #edit-modal, #delete-modal').on('click', function(e) {
        if (e.target === this) $(this).addClass('hidden');
    });
});
</script>
@endsection
