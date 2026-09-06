@extends('components.default')
@section('title', 'Users - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

<div class="page-bg min-h-screen md:ml-64">
    <x-ui.page-header eyebrow="Users" title="Manage accounts & schedules">
        <x-slot:actions>
            <button id="open-add-sched-modal" type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-white text-neutral-700 border border-neutral-300 hover:bg-neutral-50">
                <i class="fas fa-calendar-check text-xs"></i> Add Schedule
            </button>
            <button id="open-add-modal" type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-primary-600 text-white hover:bg-primary-700">
                <i class="fas fa-user-plus text-xs"></i> Add User
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <main class="p-4 sm:p-6 space-y-5 max-w-content mx-auto">
        <x-ui.table-card>
            @if($users->isEmpty())
                <div class="py-16 text-center">
                    <i class="fas fa-users text-4xl text-neutral-300 mb-3 block"></i>
                    <p class="text-sm font-medium text-neutral-700">No users yet</p>
                    <p class="text-xs text-neutral-500 mt-1">Click "Add User" to create one.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table id="users-table" class="w-full display nowrap text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-neutral-500 bg-neutral-50">
                                <th class="text-left px-4 py-3 font-semibold">Name</th>
                                <th class="text-left px-4 py-3 font-semibold">Email</th>
                                <th class="text-left px-4 py-3 font-semibold">User type</th>
                                <th class="text-left px-4 py-3 font-semibold">Contact</th>
                                <th class="text-left px-4 py-3 font-semibold">Class schedule</th>
                                <th class="text-left px-4 py-3 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @foreach ($users as $user)
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-4 py-3 font-medium text-neutral-900">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-neutral-700">{{ $user->email }}</td>
                                    <td class="px-4 py-3"><x-ui.badge :status="$user->user_type" variant="neutral" /></td>
                                    <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ $user->contact_number ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($user->classSchedules->count() > 0)
                                            <ul class="text-sm text-neutral-700 space-y-1">
                                                @foreach ($user->classSchedules as $sched)
                                                    <li class="leading-relaxed">
                                                        {{ $sched->subject_code }} - {{ $sched->subject_name }}
                                                        ({{ $sched->schedule_time }}) - Room: {{ $sched->room }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-neutral-500 text-sm">No schedules</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5">
                                            <button type="button"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-md bg-neutral-100 text-neutral-700 border border-neutral-200 hover:bg-neutral-200 edit-btn"
                                                    data-id="{{ $user->id }}" data-name="{{ $user->name }}" data-email="{{ $user->email }}"
                                                    data-user-type="{{ $user->user_type }}" data-contact="{{ $user->contact_number }}">
                                                <i class="fas fa-edit text-[11px]"></i> Edit
                                            </button>
                                            <button type="button"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-md bg-danger-50 text-danger-700 border border-danger-100 hover:bg-danger-100 delete-btn"
                                                    data-id="{{ $user->id }}" data-name="{{ $user->name }}">
                                                <i class="fas fa-trash text-[11px]"></i>
                                            </button>
                                        </div>
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

{{-- Add Schedule Modal --}}
<div id="add-sched-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-neutral-900/50 p-4">
    <div class="w-full max-w-lg bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="text-base font-semibold text-neutral-900">Add Class Schedule</h3>
            <button type="button" class="w-8 h-8 grid place-items-center rounded-md text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 cancel-sched" aria-label="Close">
                <i class="text-xs fas fa-times"></i>
            </button>
        </div>
        <form action="{{ route('admin.add-sched') }}" method="POST" class="flex flex-col flex-1 min-h-0">
            @csrf
            <div class="px-6 py-5 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Instructor</label>
                    <select name="user_id" required
                            class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                        @foreach ($instructors as $inst)
                            <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Year Level</label>
                    <input type="text" name="year_level" required
                           class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Block Name</label>
                    <input type="text" name="block_name" required
                           class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Subject Code</label>
                    <input type="text" name="subject_code" required
                           class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Subject Name</label>
                    <input type="text" name="subject_name" required
                           class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Schedule Time</label>
                    <input type="text" name="schedule_time" required placeholder="e.g., Mon/Wed 8:00 AM - 10:00 AM"
                           class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Room</label>
                    <input type="text" name="room" required
                           class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md bg-white text-neutral-700 border border-neutral-300 hover:bg-neutral-50 cancel-sched">Cancel</button>
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md bg-primary-600 text-white hover:bg-primary-700">Add Schedule</button>
            </div>
        </form>
    </div>
</div>

{{-- Add User Modal --}}
<div id="add-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-neutral-900/50 p-4">
    <div class="w-full max-w-lg bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="text-base font-semibold text-neutral-900">Add User</h3>
            <button type="button" class="w-8 h-8 grid place-items-center rounded-md text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 cancel-add" aria-label="Close">
                <i class="text-xs fas fa-times"></i>
            </button>
        </div>
        <form action="{{ route('admin.user.register') }}" method="POST" class="flex flex-col flex-1 min-h-0">
            @csrf
            <div class="px-6 py-5 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Full Name</label>
                    <input type="text" name="name" required
                           class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Email Address</label>
                    <input type="email" name="email" required
                           class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">User Type</label>
                    <select name="user_type" required
                            class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                        <option value="" disabled selected>-- Select User Type --</option>
                        <option value="Admin">Admin</option>
                        <option value="Instructor">Instructor</option>
                        <option value="Student">Student</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Contact Number</label>
                    <input type="text" name="contact_number"
                           class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-neutral-700">Password</label>
                        <input type="password" name="password" required
                               class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-700">Confirm Password</label>
                        <input type="password" name="password_confirmation" required
                               class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" id="cancel-add" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md bg-white text-neutral-700 border border-neutral-300 hover:bg-neutral-50 cancel-add">Cancel</button>
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md bg-primary-600 text-white hover:bg-primary-700">Add User</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit User Modal --}}
<div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-neutral-900/50 p-4">
    <div class="w-full max-w-md bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="text-base font-semibold text-neutral-900">Edit User</h3>
            <button type="button" class="w-8 h-8 grid place-items-center rounded-md text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 cancel-edit" aria-label="Close">
                <i class="text-xs fas fa-times"></i>
            </button>
        </div>
        <form id="edit-form" action="{{ route('admin.users.update') }}" method="POST" class="flex flex-col flex-1 min-h-0">
            @csrf
            <input type="hidden" name="id" id="edit-id">
            <div class="px-6 py-5 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Name</label>
                    <input type="text" name="name" id="edit-name"
                           class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Email</label>
                    <input type="email" name="email" id="edit-email"
                           class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">User Type</label>
                    <select name="user_type" id="edit-user-type"
                            class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                        <option value="Admin">Admin</option>
                        <option value="Instructor">Instructor</option>
                        <option value="Student">Student</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Contact Number</label>
                    <input type="text" name="contact_number" id="edit-contact"
                           class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Password (leave blank to keep current)</label>
                    <input type="password" name="password" id="edit-password"
                           class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="edit-password-confirmation"
                           class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" id="cancel-edit" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md bg-white text-neutral-700 border border-neutral-300 hover:bg-neutral-50 cancel-edit">Cancel</button>
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md bg-primary-600 text-white hover:bg-primary-700">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-neutral-900/50 p-4">
    <div class="w-full max-w-md bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="flex items-center gap-2 text-base font-semibold text-neutral-900">
                <i class="text-sm text-danger-600 fas fa-exclamation-triangle"></i> Delete User
            </h3>
            <button type="button" class="w-8 h-8 grid place-items-center rounded-md text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" id="cancel-delete" aria-label="Close">
                <i class="text-xs fas fa-times"></i>
            </button>
        </div>
        <form id="delete-form" method="POST" action="" class="flex flex-col flex-1">
            @csrf
            @method('DELETE')
            <div class="px-6 py-5 space-y-3">
                <p class="text-sm text-neutral-700">
                    Are you sure you want to delete <span id="delete-item-name" class="font-semibold text-neutral-900"></span>?
                </p>
                <p class="text-xs text-danger-600 flex items-start gap-1.5 leading-relaxed">
                    <i class="fas fa-info-circle mt-0.5"></i> This action cannot be undone.
                </p>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md bg-white text-neutral-700 border border-neutral-300 hover:bg-neutral-50" id="cancel-delete-btn">Cancel</button>
                <button type="button" id="confirm-delete" class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-danger-600 text-white hover:bg-danger-700">
                    <i class="text-xs fas fa-trash-alt"></i> Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableEl = document.getElementById('users-table');
    if (tableEl && window.initAppTable) {
        try {
            window.initAppTable('#users-table', {
                language: { search: '', searchPlaceholder: 'Search users...' }
            });
        } catch (e) { console.error('DataTable init failed (users-table)', e); }
    }

    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            const d = editBtn.dataset;
            const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
            set('edit-id', d.id);
            set('edit-name', d.name);
            set('edit-email', d.email);
            set('edit-user-type', d.userType || d.user_type);
            set('edit-contact', d.contact);
            const m = document.getElementById('edit-modal');
            if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const d = deleteBtn.dataset;
            const nameEl = document.getElementById('delete-item-name');
            const form = document.getElementById('delete-form');
            if (nameEl) nameEl.textContent = d.name;
            if (form) form.action = '/admin/users/' + d.id;
            const m = document.getElementById('delete-modal');
            if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
        }

        if (e.target.closest('#open-add-modal')) {
            const m = document.getElementById('add-modal');
            if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
        }
        if (e.target.closest('#open-add-sched-modal')) {
            const m = document.getElementById('add-sched-modal');
            if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
        }
        if (e.target.closest('.cancel-sched') || e.target.closest('#cancel-sched')) {
            const m = document.getElementById('add-sched-modal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
        }
        if (e.target.closest('.cancel-add') || e.target.closest('#cancel-add')) {
            const m = document.getElementById('add-modal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
        }
        if (e.target.closest('.cancel-edit') || e.target.closest('#cancel-edit')) {
            const m = document.getElementById('edit-modal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
        }
        if (e.target.closest('#cancel-delete') || e.target.closest('#cancel-delete-btn')) {
            const m = document.getElementById('delete-modal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
        }
    });

    // Confirm delete -> showConfirm() then submit
    const confirmDelete = document.getElementById('confirm-delete');
    if (confirmDelete) {
        confirmDelete.addEventListener('click', function () {
            const form = document.getElementById('delete-form');
            if (!form) return;
            if (window.showConfirm) {
                window.showConfirm({
                    title: 'Delete this user?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    confirmText: 'Yes, delete'
                }).then(function (r) {
                    if (r.isConfirmed) form.submit();
                });
            } else {
                form.submit();
            }
        });
    }

    ['add-modal', 'edit-modal', 'delete-modal', 'add-sched-modal'].forEach(function (id) {
        const m = document.getElementById(id);
        if (m) m.addEventListener('click', function (e) { if (e.target === m) { m.classList.add('hidden'); m.classList.remove('flex'); } });
    });
});
</script>
@endsection
