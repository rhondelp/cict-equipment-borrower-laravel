@extends('components.default')
@section('title', 'Users - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

<div class="dash-bg min-h-screen md:ml-80">
    <header class="sticky top-0 z-30 dash-header">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
                <button id="menu-toggle" class="text-neutral-400 hover:text-white md:hidden">
                    <i class="text-lg fas fa-bars"></i>
                </button>
                <div>
                    <p class="text-xs font-medium tracking-widest uppercase" style="color:var(--text-muted)">Users</p>
                    <p class="text-sm font-semibold tracking-tight text-white -mt-0.5">Manage accounts & schedules</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button id="open-add-sched-modal" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl border border-white/10 bg-white/5 text-neutral-300 hover:bg-white/10 transition">
                    <i class="fas fa-calendar-check text-xs"></i> Add Schedule
                </button>
                <button id="open-add-modal" class="btn-primary inline-flex items-center gap-2 !py-2 !px-4 !text-sm !rounded-xl">
                    <i class="fas fa-user-plus text-xs"></i> Add User
                </button>
            </div>
        </div>
    </header>

    <main class="p-6 space-y-5 max-w-content mx-auto">
        <x-ui.table-card>
            <table id="users-table" class="w-full display nowrap">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>User type</th>
                        <th>Contact</th>
                        <th>Class schedule</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                    <tr>
                        <td class="font-medium text-white">{{ $user->name }}</td>
                        <td class="text-neutral-200">{{ $user->email }}</td>
                        <td><x-ui.badge :status="$user->user_type" variant="neutral" /></td>
                        <td class="tabular-nums">{{ $user->contact_number ?? '—' }}</td>
                        <td>
                            @if ($user->classSchedules->count() > 0)
                            <ul class="text-sm text-neutral-300 space-y-1">
                                @foreach ($user->classSchedules as $sched)
                                <li class="leading-relaxed">
                                    {{ $sched->subject_code }} - {{ $sched->subject_name }}
                                    ({{ $sched->schedule_time }}) - Room: {{ $sched->room }}
                                </li>
                                @endforeach
                            </ul>
                            @else
                            <span class="text-neutral-400 text-sm">No schedules</span>
                            @endif
                        </td>
                        <td>
                            <button
                                class="px-2.5 py-1 text-xs font-medium bg-neutral-700/40 text-neutral-200 border border-white/10 rounded-md hover:bg-neutral-700/60 transition edit-btn"
                                data-id="{{ $user->id }}" data-name="{{ $user->name }}" data-email="{{ $user->email }}"
                                data-user-type="{{ $user->user_type }}" data-contact="{{ $user->contact_number }}">
                                <i class="fas fa-edit text-[11px]"></i> Edit
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </x-ui.table-card>
    </main>
</div>

<!-- Add Schedule Modal -->
<div id="add-sched-modal" class="fixed inset-0 z-[60] flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="w-full max-w-lg mx-4 overflow-hidden bg-white shadow-2xl rounded-xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-white">➕ Add Class Schedule</h3>
            <button type="button" class="text-gray-400 cancel-sched hover:text-gray-600">✕</button>
        </div>
        <form action="{{ route('admin.add-sched') }}" method="POST" class="p-6 space-y-5">
            @csrf
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Instructor</label>
                <select name="user_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    @foreach ($instructors as $inst)
                    <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Year Level</label>
                <input type="text" name="year_level" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Block Name</label>
                <input type="text" name="block_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Subject Code</label>
                <input type="text" name="subject_code" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Subject Name</label>
                <input type="text" name="subject_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Schedule Time</label>
                <input type="text" name="schedule_time" required placeholder="e.g., Mon/Wed 8:00 AM - 10:00 AM" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Room</label>
                <input type="text" name="room" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
            </div>
            <div class="flex justify-end pt-4 space-x-3 border-t border-gray-200">
                <button type="button" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg cancel-sched hover:bg-gray-200">Cancel</button>
                <button type="submit" class="px-5 py-2 text-white bg-success-500 rounded-lg hover:bg-success-600">Add Schedule</button>
            </div>
        </form>
    </div>
</div>

<!-- Add User Modal — z-[60] so it sits above sidebar (which is z-50) -->
<div id="add-modal" class="fixed inset-0 z-[60] flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="w-full max-w-lg mx-4 overflow-hidden bg-white shadow-2xl rounded-xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-white">➕ Add User</h3>
            <button type="button" class="cancel-add text-gray-400 transition hover:text-gray-600" aria-label="Close">✕</button>
        </div>
        <form action="{{ route('admin.user.register') }}" method="POST" class="p-6 space-y-5">
            @csrf
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" name="name" required class="w-full px-4 py-2 transition border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" name="email" required class="w-full px-4 py-2 transition border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">User Type</label>
                <select name="user_type" required class="w-full px-4 py-2 transition border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <option value="" disabled selected>-- Select User Type --</option>
                    <option value="Admin">Admin</option>
                    <option value="Instructor">Instructor</option>
                    <option value="Student">Student</option>
                </select>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Contact Number</label>
                <input type="text" name="contact_number" class="w-full px-4 py-2 transition border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-2 transition border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" name="password_confirmation" required class="w-full px-4 py-2 transition border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
            </div>
            <div class="flex justify-end pt-4 space-x-3 border-t border-gray-200">
                <button type="button" id="cancel-add" class="px-4 py-2 text-gray-600 transition bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                <button type="submit" class="px-5 py-2 text-white transition bg-primary-500 rounded-lg shadow-sm hover:bg-primary-600">Add User</button>
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
                <input type="text" name="name" id="edit-name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="edit-email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">User Type</label>
                <select name="user_type" id="edit-user-type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                    <option value="Admin">Admin</option>
                    <option value="Instructor">Instructor</option>
                    <option value="Student">Student</option>
                </select>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Contact Number</label>
                <input type="text" name="contact_number" id="edit-contact" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Password (leave blank to keep current)</label>
                <input type="password" name="password" id="edit-password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Confirm Password</label>
                <input type="password" name="password_confirmation" id="edit-password-confirmation" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
            </div>
            <div class="flex justify-end pt-4 space-x-3 border-t">
                <button type="button" id="cancel-edit" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="px-4 py-2 text-white bg-primary-500 rounded-lg hover:bg-primary-600">Save Changes</button>
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
            <p class="text-gray-600">Are you sure you want to delete <span id="delete-item-name" class="font-semibold"></span>? This action cannot be undone.</p>
        </div>
        <form id="delete-form" method="POST" action="">
            @csrf
            @method('DELETE')
        </form>
        <div class="flex justify-end px-6 py-4 space-x-3 border-t border-gray-200">
            <button type="button" id="cancel-delete" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
            <button type="button" id="confirm-delete" class="px-4 py-2 text-white bg-danger-500 rounded-lg hover:bg-danger-600">Delete</button>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
    try {
        let table = (window.initAppTable ? window.initAppTable('#users-table', {
            language: { search: "", searchPlaceholder: "Search users..." }
        }) : $('#users-table').DataTable({
            responsive: true, autoWidth: false, pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            language: { search: "", searchPlaceholder: "Search users..." }
        }));
    } catch(e) { console.error('DataTable init failed (users-table)', e); }

    $(document).on('click', '#open-add-modal', function() { $('#add-modal').removeClass('hidden'); });
    $(document).on('click', '#open-add-sched-modal', function () { $('#add-sched-modal').removeClass('hidden'); });
    $(document).on('click', '.cancel-sched', function () { $('#add-sched-modal').addClass('hidden'); });
    $(document).on('click', '#cancel-add, #cancel-edit, #cancel-delete, .cancel-add', function() {
        $('#add-modal, #edit-modal, #delete-modal').addClass('hidden');
    });
    $('#users-table').on('click', '.edit-btn', function() {
        $('#edit-id').val($(this).data('id'));
        $('#edit-name').val($(this).data('name'));
        $('#edit-email').val($(this).data('email'));
        const userType = $(this).data('user-type') || $(this).data('user_type') || $(this).attr('data-user-type');
        $('#edit-user-type').val(userType);
        $('#edit-contact').val($(this).data('contact'));
        $('#edit-modal').removeClass('hidden');
    });
    $('#users-table').on('click', '.delete-btn', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        let name = $(this).data('name');
        $('#delete-item-name').text(name);
        $('#delete-form').attr('action', '/admin/users/' + id);
        $('#delete-modal').removeClass('hidden');
    });
    $(document).on('click', '#confirm-delete', function() { $('#delete-form').submit(); });
    $(document).on('click', '#add-modal, #edit-modal, #delete-modal, #add-sched-modal', function(e) {
        if (e.target === this) $(this).addClass('hidden');
    });
});
</script>
@endsection
