@extends('components.default')
@section('title', 'Users - CICT Equipment Borrower System')
@section('content')
@include('components.admin.navbar')

<div class="min-h-[100dvh] page-bg md:ml-64">

    {{-- Essential for keyboard users: the sidebar is six links deep, so without
         this every page begins with six tab stops before the content. --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>
    <x-ui.page-header eyebrow="Users" title="Manage accounts & schedules">
        <x-slot:actions>
            <button id="open-add-sched-modal" type="button"
                    class="inline-flex items-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold bg-white border rounded-md text-neutral-700 border-neutral-300 hover:bg-neutral-50">
                <i class="text-base fas fa-calendar-check"></i> Add Schedule
            </button>
            <button id="open-add-modal" type="button"
                    class="inline-flex items-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold text-white rounded-md bg-primary-600 hover:bg-primary-700">
                <i class="text-base fas fa-user-plus"></i> Add User
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <main id="main-content" class="p-4 mx-auto space-y-5 sm:p-6 max-w-content">
        <x-ui.table-card>
            @if($users->isEmpty())
                <div class="py-16 text-center">
                    <i class="block mb-3 text-4xl fas fa-users text-neutral-300"></i>
                    <p class="text-lg font-semibold text-neutral-700">No users yet</p>
                    <p class="mt-1 text-base text-neutral-600">Click "Add User" to create one.</p>
                </div>
            @else
                <div class="p-4 overflow-x-auto">
                    <table id="users-table" class="w-full text-sm display nowrap">
                        <thead>
                            <tr class="text-sm tracking-wider uppercase text-neutral-600 bg-neutral-50">
                                <th class="px-4 py-3 font-semibold text-left">Name</th>
                                <th class="px-4 py-3 font-semibold text-left">Email</th>
                                <th class="px-4 py-3 font-semibold text-left">User type</th>
                                <th class="px-4 py-3 font-semibold text-left">Contact</th>
                                <th class="px-4 py-3 font-semibold text-left">Class schedule</th>
                                <th class="px-4 py-3 font-semibold text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200">
                            @foreach ($users as $user)
                                <tr class="hover:bg-neutral-50">
                                    <td class="px-4 py-3 font-medium text-neutral-900">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-neutral-700">{{ $user->email }}</td>
                                    <td class="px-4 py-3"><x-ui.badge :status="$user->user_type" variant="neutral" /></td>
                                    <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ $user->contact_number ?? '—' }}</td>
                                    {{-- Compact summary: stays on one line no matter how many
                                         schedules a user has. The per-schedule actions live in
                                         the Manage modal, not in this cell. --}}
                                    <td class="px-4 py-3">
                                        @php $schedCount = $user->classSchedules->count(); @endphp
                                        @if ($schedCount === 0)
                                            <span class="text-sm text-neutral-600">No schedules</span>
                                        @else
                                            <div class="flex items-center gap-2 whitespace-nowrap">
                                                <x-ui.badge :status="$schedCount . ' schedule' . ($schedCount === 1 ? '' : 's')" variant="neutral" />
                                                <button type="button"
                                                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-md bg-neutral-100 text-neutral-700 border border-neutral-200 hover:bg-neutral-200 manage-sched-btn"
                                                        data-user-id="{{ $user->id }}"
                                                        data-user-name="{{ $user->name }}">
                                                    <i class="fas fa-calendar-check text-sm"></i> Manage
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5">
                                            <button type="button"
                                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-md bg-neutral-100 text-neutral-700 border border-neutral-200 hover:bg-neutral-200 edit-btn"
                                                    data-id="{{ $user->id }}" data-name="{{ $user->name }}" data-email="{{ $user->email }}"
                                                    data-user-type="{{ $user->user_type }}" data-contact="{{ $user->contact_number }}">
                                                <i class="fas fa-edit text-sm"></i> Edit
                                            </button>
                                            <button type="button"
                                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-md bg-danger-50 text-danger-700 border border-danger-100 hover:bg-danger-100 delete-btn"
                                                    data-id="{{ $user->id }}" data-name="{{ $user->name }}">
                                                <i class="fas fa-trash text-sm"></i>
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
<div id="add-sched-modal" class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-neutral-900/50">
    <div class="w-full max-w-lg bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="text-base font-semibold text-neutral-900">Add Class Schedule</h3>
            <button type="button" class="grid w-8 h-8 rounded-md place-items-center text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 cancel-sched" aria-label="Close">
                <i class="text-base fas fa-times"></i>
            </button>
        </div>
        <form action="{{ route('admin.add-sched') }}" method="POST" class="flex flex-col flex-1 min-h-0">
            @csrf
            <div class="flex-1 px-6 py-5 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-base font-medium text-neutral-800">Instructor</label>
                    <select name="user_id" required
                            class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                        @foreach ($instructors as $inst)
                            <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-base font-medium text-neutral-800">Year Level</label>
                    <input type="text" name="year_level" required
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div>
                    <label class="block text-base font-medium text-neutral-800">Block Name</label>
                    <input type="text" name="block_name" required
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div>
                    <label class="block text-base font-medium text-neutral-800">Subject Code</label>
                    <input type="text" name="subject_code" required
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div>
                    <label class="block text-base font-medium text-neutral-800">Subject Name</label>
                    <input type="text" name="subject_name" required
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div>
                    <label class="block text-base font-medium text-neutral-800">Schedule Time</label>
                    <input type="text" name="schedule_time" required placeholder="e.g., Mon/Wed 8:00 AM - 10:00 AM"
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div>
                    <label class="block text-base font-medium text-neutral-800">Room</label>
                    <input type="text" name="room" required
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 text-base font-semibold bg-white border rounded-md text-neutral-700 border-neutral-300 hover:bg-neutral-50 cancel-sched">Cancel</button>
                <button type="submit" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 text-base font-semibold text-white rounded-md bg-primary-600 hover:bg-primary-700">Add Schedule</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Schedule Modal --}}
{{-- Manage Schedules Modal — one shared shell; each user's rows are rendered
     once into a hidden group and revealed by the Manage button. --}}
<div id="manage-sched-modal" class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-neutral-900/50">
    <div class="w-full max-w-2xl bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col max-h-[90vh]">
        <div class="flex items-start justify-between gap-3 px-6 py-4 border-b border-neutral-200">
            <div class="min-w-0">
                <h3 class="flex items-center gap-2 text-lg font-semibold text-neutral-900">
                    <i class="text-base text-primary-600 fas fa-calendar-check"></i> Class Schedules
                </h3>
                <p id="manage-sched-user" class="mt-1 text-base truncate text-neutral-600"></p>
            </div>
            <button type="button" class="grid w-10 h-10 rounded-md shrink-0 place-items-center text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 cancel-manage-sched" aria-label="Close">
                <i class="text-base fas fa-times"></i>
            </button>
        </div>

        <div class="flex-1 px-6 py-5 overflow-y-auto">
            @foreach ($users as $user)
                @if ($user->classSchedules->count() > 0)
                    <div class="hidden space-y-3 manage-sched-group" data-user-id="{{ $user->id }}">
                        @foreach ($user->classSchedules as $sched)
                            <div class="flex items-start justify-between gap-3 p-4 border rounded-lg border-neutral-200 bg-neutral-50">
                                <div class="min-w-0 space-y-1">
                                    <p class="text-base font-semibold text-neutral-900">
                                        {{ $sched->subject_code }} — {{ $sched->subject_name }}
                                    </p>
                                    <p class="text-sm text-neutral-600">
                                        {{ $sched->year_level }} · Block {{ $sched->block_name }} · Room {{ $sched->room }}
                                    </p>
                                    <p class="text-sm text-neutral-600">
                                        <i class="fas fa-clock"></i> {{ $sched->schedule_time }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button"
                                            class="grid w-10 h-10 border rounded-md place-items-center bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-100 sched-edit-btn"
                                            title="Edit schedule" aria-label="Edit schedule"
                                            data-id="{{ $sched->id }}"
                                            data-user-id="{{ $sched->user_id }}"
                                            data-user-name="{{ $user->name }}"
                                            data-year-level="{{ $sched->year_level }}"
                                            data-block-name="{{ $sched->block_name }}"
                                            data-subject-code="{{ $sched->subject_code }}"
                                            data-subject-name="{{ $sched->subject_name }}"
                                            data-schedule-time="{{ $sched->schedule_time }}"
                                            data-room="{{ $sched->room }}">
                                        <i class="text-base fas fa-pen"></i>
                                    </button>
                                    <button type="button"
                                            class="grid w-10 h-10 border rounded-md place-items-center bg-danger-50 text-danger-700 border-danger-200 hover:bg-danger-100 sched-delete-btn"
                                            title="Delete schedule" aria-label="Delete schedule"
                                            data-id="{{ $sched->id }}"
                                            data-label="{{ $sched->subject_code }} - {{ $sched->subject_name }}">
                                        <i class="text-base fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>

        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
            <button type="button" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 text-base font-semibold bg-white border rounded-md text-neutral-700 border-neutral-300 hover:bg-neutral-50 cancel-manage-sched">Close</button>
        </div>
    </div>
</div>

{{-- Edit Schedule Modal — sits above the Manage modal, which stays open behind it. --}}
<div id="edit-sched-modal" class="fixed inset-0 z-[60] items-center justify-center hidden p-4 bg-neutral-900/50">
    <div class="w-full max-w-lg bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-neutral-900">
                <i class="text-base text-primary-600 fas fa-calendar-check"></i> Edit Class Schedule
            </h3>
            <button type="button" class="grid w-10 h-10 rounded-md place-items-center text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 cancel-edit-sched" aria-label="Close">
                <i class="text-base fas fa-times"></i>
            </button>
        </div>
        <form action="{{ route('admin.sched.update') }}" method="POST" class="flex flex-col flex-1 min-h-0">
            @csrf
            <input type="hidden" name="id" id="edit-sched-id">
            <div class="flex-1 px-6 py-5 space-y-4 overflow-y-auto">
                <div>
                    <label for="edit-sched-user" class="block text-base font-medium text-neutral-800">Instructor</label>
                    <select name="user_id" id="edit-sched-user" required
                            class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                        @foreach ($instructors as $inst)
                            <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="edit-sched-year" class="block text-base font-medium text-neutral-800">Year Level</label>
                    <input type="text" name="year_level" id="edit-sched-year" required
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div>
                    <label for="edit-sched-block" class="block text-base font-medium text-neutral-800">Block Name</label>
                    <input type="text" name="block_name" id="edit-sched-block" required
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div>
                    <label for="edit-sched-code" class="block text-base font-medium text-neutral-800">Subject Code</label>
                    <input type="text" name="subject_code" id="edit-sched-code" required
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div>
                    <label for="edit-sched-name" class="block text-base font-medium text-neutral-800">Subject Name</label>
                    <input type="text" name="subject_name" id="edit-sched-name" required
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div>
                    <label for="edit-sched-time" class="block text-base font-medium text-neutral-800">Schedule Time</label>
                    <input type="text" name="schedule_time" id="edit-sched-time" required placeholder="e.g., Mon/Wed 8:00 AM - 10:00 AM"
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div>
                    <label for="edit-sched-room" class="block text-base font-medium text-neutral-800">Room</label>
                    <input type="text" name="room" id="edit-sched-room" required
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 text-base font-semibold bg-white border rounded-md text-neutral-700 border-neutral-300 hover:bg-neutral-50 cancel-edit-sched">Cancel</button>
                <button type="submit" class="inline-flex items-center justify-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold text-white rounded-md bg-primary-600 hover:bg-primary-700">
                    <i class="text-base fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Delete Schedule — submitted by the SweetAlert2 confirm, no separate modal markup --}}
<form id="delete-sched-form" method="POST" action="" class="hidden">
    @csrf
    @method('DELETE')
</form>

{{-- Add User Modal --}}
<div id="add-modal" class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-neutral-900/50">
    <div class="w-full max-w-lg bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="text-base font-semibold text-neutral-900">Add User</h3>
            <button type="button" class="grid w-8 h-8 rounded-md place-items-center text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 cancel-add" aria-label="Close">
                <i class="text-base fas fa-times"></i>
            </button>
        </div>
        <form action="{{ route('admin.user.register') }}" method="POST" class="flex flex-col flex-1 min-h-0">
            @csrf
            <div class="flex-1 px-6 py-5 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-base font-medium text-neutral-800">Full Name</label>
                    <input type="text" name="name" required
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div>
                    <label class="block text-base font-medium text-neutral-800">Email Address</label>
                    <input type="email" name="email" required
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div>
                    <label class="block text-base font-medium text-neutral-800">User Type</label>
                    <select name="user_type" required
                            class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                        <option value="" disabled selected>-- Select User Type --</option>
                        <option value="Admin">Admin</option>
                        <option value="Instructor">Instructor</option>
                        <option value="Student">Student</option>
                    </select>
                </div>
                <div>
                    <label class="block text-base font-medium text-neutral-800">Contact Number</label>
                    <input type="text" name="contact_number"
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-base font-medium text-neutral-800">Password</label>
                        <input type="password" name="password" required
                               class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-base font-medium text-neutral-800">Confirm Password</label>
                        <input type="password" name="password_confirmation" required
                               class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" id="cancel-add" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 text-base font-semibold bg-white border rounded-md text-neutral-700 border-neutral-300 hover:bg-neutral-50 cancel-add">Cancel</button>
                <button type="submit" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 text-base font-semibold text-white rounded-md bg-primary-600 hover:bg-primary-700">Add User</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit User Modal --}}
<div id="edit-modal" class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-neutral-900/50">
    <div class="w-full max-w-md bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="text-base font-semibold text-neutral-900">Edit User</h3>
            <button type="button" class="grid w-8 h-8 rounded-md place-items-center text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 cancel-edit" aria-label="Close">
                <i class="text-base fas fa-times"></i>
            </button>
        </div>
        <form id="edit-form" action="{{ route('admin.users.update') }}" method="POST" class="flex flex-col flex-1 min-h-0">
            @csrf
            <input type="hidden" name="id" id="edit-id">
            <div class="flex-1 px-6 py-5 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-base font-medium text-neutral-800">Name</label>
                    <input type="text" name="name" id="edit-name"
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div>
                    <label class="block text-base font-medium text-neutral-800">Email</label>
                    <input type="email" name="email" id="edit-email"
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div>
                    <label class="block text-base font-medium text-neutral-800">User Type</label>
                    <select name="user_type" id="edit-user-type"
                            class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                        <option value="Admin">Admin</option>
                        <option value="Instructor">Instructor</option>
                        <option value="Student">Student</option>
                    </select>
                </div>
                <div>
                    <label class="block text-base font-medium text-neutral-800">Contact Number</label>
                    <input type="text" name="contact_number" id="edit-contact"
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div>
                    <label class="block text-base font-medium text-neutral-800">Password (leave blank to keep current)</label>
                    <input type="password" name="password" id="edit-password"
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
                <div>
                    <label class="block text-base font-medium text-neutral-800">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="edit-password-confirmation"
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" id="cancel-edit" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 text-base font-semibold bg-white border rounded-md text-neutral-700 border-neutral-300 hover:bg-neutral-50 cancel-edit">Cancel</button>
                <button type="submit" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 text-base font-semibold text-white rounded-md bg-primary-600 hover:bg-primary-700">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div id="delete-modal" class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-neutral-900/50">
    <div class="flex flex-col w-full max-w-md bg-white border border-neutral-200 rounded-xl shadow-flat">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-neutral-900">
                <i class="text-base text-danger-600 fas fa-exclamation-triangle"></i> Delete User
            </h3>
            <button type="button" class="grid w-8 h-8 rounded-md place-items-center text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" id="cancel-delete" aria-label="Close">
                <i class="text-base fas fa-times"></i>
            </button>
        </div>
        <form id="delete-form" method="POST" action="" class="flex flex-col flex-1">
            @csrf
            @method('DELETE')
            <div class="px-6 py-5 space-y-3">
                <p class="text-base text-neutral-800">
                    Are you sure you want to delete <span id="delete-item-name" class="font-semibold text-neutral-900"></span>?
                </p>
                <p class="text-base text-danger-700 bg-danger-50 border border-danger-200 rounded-md px-4 py-3 flex items-start gap-2 leading-relaxed">
                    <i class="fas fa-info-circle mt-0.5"></i> This action cannot be undone.
                </p>
            </div>
            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 text-base font-semibold bg-white border rounded-md text-neutral-700 border-neutral-300 hover:bg-neutral-50" id="cancel-delete-btn">Cancel</button>
                <button type="button" id="confirm-delete" class="inline-flex items-center justify-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold text-white rounded-md bg-danger-600 hover:bg-danger-700">
                    <i class="text-base fas fa-trash-alt"></i> Delete
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

        // Class schedule — open the per-user Manage modal
        const manageBtn = e.target.closest('.manage-sched-btn');
        if (manageBtn) {
            const d = manageBtn.dataset;
            const title = document.getElementById('manage-sched-user');
            if (title) title.textContent = d.userName || '';
            document.querySelectorAll('.manage-sched-group').forEach(function (g) {
                g.classList.toggle('hidden', g.dataset.userId !== d.userId);
            });
            const m = document.getElementById('manage-sched-modal');
            if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
        }

        if (e.target.closest('.cancel-manage-sched')) {
            const m = document.getElementById('manage-sched-modal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
        }

        // Class schedule — edit
        const schedEditBtn = e.target.closest('.sched-edit-btn');
        if (schedEditBtn) {
            const d = schedEditBtn.dataset;
            const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
            set('edit-sched-id', d.id);
            set('edit-sched-year', d.yearLevel);
            set('edit-sched-block', d.blockName);
            set('edit-sched-code', d.subjectCode);
            set('edit-sched-name', d.subjectName);
            set('edit-sched-time', d.scheduleTime);
            set('edit-sched-room', d.room);

            // The select only lists current Instructors. If this schedule belongs
            // to someone no longer typed as one, add them back so that saving
            // cannot silently reassign the schedule to the first instructor.
            const schedUser = document.getElementById('edit-sched-user');
            if (schedUser) {
                schedUser.value = d.userId;
                if (schedUser.value !== String(d.userId)) {
                    const opt = document.createElement('option');
                    opt.value = d.userId;
                    opt.textContent = d.userName || ('User #' + d.userId);
                    schedUser.prepend(opt);
                    schedUser.value = d.userId;
                }
            }

            const m = document.getElementById('edit-sched-modal');
            if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
        }

        // Class schedule — delete, via the shared SweetAlert2 confirm helper
        const schedDeleteBtn = e.target.closest('.sched-delete-btn');
        if (schedDeleteBtn) {
            const d = schedDeleteBtn.dataset;
            const form = document.getElementById('delete-sched-form');
            if (form) {
                form.action = '/admin/users/sched/' + d.id;
                if (window.showConfirm) {
                    window.showConfirm({
                        title: 'Delete this schedule?',
                        text: (d.label || 'This schedule') + ' will be removed. This action cannot be undone.',
                        icon: 'warning',
                        confirmText: 'Yes, delete'
                    }).then(function (r) { if (r.isConfirmed) form.submit(); });
                } else {
                    form.submit();
                }
            }
        }

        if (e.target.closest('.cancel-edit-sched')) {
            const m = document.getElementById('edit-sched-modal');
            if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
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

    ['add-modal', 'edit-modal', 'delete-modal', 'add-sched-modal', 'manage-sched-modal', 'edit-sched-modal'].forEach(function (id) {
        const m = document.getElementById(id);
        if (m) m.addEventListener('click', function (e) { if (e.target === m) { m.classList.add('hidden'); m.classList.remove('flex'); } });
    });
});
</script>
@endsection
