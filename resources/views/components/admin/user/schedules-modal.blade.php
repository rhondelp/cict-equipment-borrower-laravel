{{-- Class schedules: a per-person list, and the add/edit form that sits above
     it. Both live here so the users page keeps one schedule story rather than
     three scattered modals.

     Deleting a schedule is destructive in a quiet way: borrow_transactions
     keeps its rows (the FK is set null) but loses which class each loan was
     for. So the delete states the count and ClassScheduleController refuses it
     outright while any loan points here. --}}

{{-- Per-person list --}}
<div id="schedules-modal" data-modal
     class="fixed inset-0 z-modal items-center justify-center hidden p-4 overflow-y-auto bg-neutral-900/50"
     role="dialog" aria-modal="true" aria-labelledby="schedules-title">
    <div class="w-full max-w-2xl my-auto bg-white border border-neutral-200 rounded-xl shadow-pop">
        <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4">
            <div class="min-w-0">
                <h2 id="schedules-title" class="text-lg font-semibold text-neutral-900">Class schedules</h2>
                <p id="schedules-for" class="mt-1 text-sm truncate text-neutral-600"></p>
            </div>
            <button type="button" data-modal-close="schedules-modal" aria-label="Close"
                    class="grid w-10 h-10 border rounded-md shrink-0 place-items-center border-neutral-200 text-neutral-600 hover:bg-neutral-50">
                <i class="text-base fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <div class="px-6 pb-5 space-y-3 max-h-[60vh] overflow-y-auto">
            @foreach ($users as $user)
                @if ($user->classSchedules->isNotEmpty())
                    <div data-schedule-group="{{ $user->id }}" hidden class="space-y-3">
                        @foreach ($user->classSchedules as $schedule)
                            @php $loanCount = (int) $schedule->borrow_transactions_count; @endphp
                            <div class="flex items-start justify-between gap-3 p-4 border rounded-lg border-neutral-200 bg-neutral-50">
                                <div class="min-w-0 space-y-1">
                                    <p class="text-base font-semibold text-neutral-900">
                                        {{ $schedule->subject_code }} — {{ $schedule->subject_name }}
                                    </p>
                                    <p class="text-sm text-neutral-600">
                                        {{ $schedule->year_level }} · Block {{ $schedule->block_name }} · {{ $schedule->room }}
                                    </p>
                                    <p class="text-sm text-neutral-600">
                                        <i class="fas fa-clock" aria-hidden="true"></i> {{ $schedule->schedule_time }}
                                        @if($loanCount > 0)
                                            · {{ $loanCount }} {{ str('loan')->plural($loanCount) }} recorded
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button" title="Edit schedule" aria-label="Edit schedule"
                                            class="grid w-10 h-10 bg-white border rounded-md place-items-center text-neutral-700 border-neutral-300 hover:border-primary-300 hover:text-primary-700"
                                            data-schedule-edit
                                            data-id="{{ $schedule->id }}"
                                            data-user-id="{{ $schedule->user_id }}"
                                            data-user-name="{{ $user->name }}"
                                            data-year-level="{{ $schedule->year_level }}"
                                            data-block-name="{{ $schedule->block_name }}"
                                            data-subject-code="{{ $schedule->subject_code }}"
                                            data-subject-name="{{ $schedule->subject_name }}"
                                            data-schedule-time="{{ $schedule->schedule_time }}"
                                            data-room="{{ $schedule->room }}">
                                        <i class="text-base fas fa-pen" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" title="Delete schedule" aria-label="Delete schedule"
                                            class="grid w-10 h-10 bg-white border rounded-md place-items-center text-neutral-600 border-neutral-300 hover:border-danger-300 hover:bg-danger-50 hover:text-danger-700"
                                            data-schedule-delete
                                            data-label="{{ $schedule->subject_code }}"
                                            data-loans="{{ $loanCount }}"
                                            data-url="{{ route('admin.sched.destroy', $schedule->id) }}">
                                        <i class="text-base fas fa-trash" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>

        <div class="flex items-center justify-between gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
            <button type="button" data-modal-open="schedule-modal" data-schedule-add
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-md border border-neutral-300 bg-white px-4 py-2 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                <i class="text-base fas fa-plus" aria-hidden="true"></i> Add schedule
            </button>
            <button type="button" data-modal-close="schedules-modal"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                Close
            </button>
        </div>
    </div>
</div>

{{-- Add / edit form. z-[60] so it sits above the list, which stays open behind it. --}}
<div id="schedule-modal" data-modal
     class="fixed inset-0 z-toast items-center justify-center hidden p-4 overflow-y-auto bg-neutral-900/50"
     role="dialog" aria-modal="true" aria-labelledby="schedule-modal-title">
    <div class="w-full max-w-lg my-auto bg-white border border-neutral-200 rounded-xl shadow-pop">
        <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4">
            <h2 id="schedule-modal-title" data-schedule-title class="text-lg font-semibold text-neutral-900">Add class schedule</h2>
            <button type="button" data-modal-close="schedule-modal" aria-label="Close"
                    class="grid w-10 h-10 border rounded-md shrink-0 place-items-center border-neutral-200 text-neutral-600 hover:bg-neutral-50">
                <i class="text-base fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        {{-- One form, two targets: the page script points it at the add route
             or the update route as the modal is opened. --}}
        <form id="schedule-form" method="POST" action="{{ route('admin.add-sched') }}"
              data-add-url="{{ route('admin.add-sched') }}"
              data-update-url="{{ route('admin.sched.update') }}">
            @csrf
            <input type="hidden" name="id" id="schedule-id">

            <div class="px-6 pb-5 space-y-4">
                <div>
                    <label for="schedule-user" class="block text-base font-medium text-neutral-800">Instructor</label>
                    <select name="user_id" id="schedule-user" required data-autofocus
                            class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                        @forelse ($instructors as $instructor)
                            <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                        @empty
                            <option value="" disabled selected>No instructor accounts yet</option>
                        @endforelse
                    </select>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="schedule-code" class="block text-base font-medium text-neutral-800">Subject code</label>
                        <input type="text" name="subject_code" id="schedule-code" required maxlength="255" placeholder="e.g. CC102"
                               class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                    <div>
                        <label for="schedule-name" class="block text-base font-medium text-neutral-800">Subject name</label>
                        <input type="text" name="subject_name" id="schedule-name" required maxlength="255" placeholder="e.g. Data Structures"
                               class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                    <div>
                        <label for="schedule-year" class="block text-base font-medium text-neutral-800">Year level</label>
                        <input type="text" name="year_level" id="schedule-year" required maxlength="255" placeholder="e.g. 2nd Year"
                               class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                    <div>
                        <label for="schedule-block" class="block text-base font-medium text-neutral-800">Block</label>
                        <input type="text" name="block_name" id="schedule-block" required maxlength="255" placeholder="e.g. D"
                               class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                    <div>
                        <label for="schedule-time" class="block text-base font-medium text-neutral-800">Meets</label>
                        <input type="text" name="schedule_time" id="schedule-time" required maxlength="255"
                               placeholder="e.g. MWF 8:00 AM – 10:00 AM"
                               class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                    <div>
                        <label for="schedule-room" class="block text-base font-medium text-neutral-800">Room</label>
                        <input type="text" name="room" id="schedule-room" required maxlength="255" placeholder="e.g. Laboratory 2"
                               class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" data-modal-close="schedule-modal"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                    Cancel
                </button>
                <button type="submit"
                        class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-md bg-primary-600 px-5 py-3 text-base font-semibold text-white hover:bg-primary-700">
                    <i class="text-base fas fa-save" aria-hidden="true"></i> Save schedule
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Submitted by the confirm on the users page; no markup of its own. --}}
<form id="schedule-delete-form" method="POST" action="" class="hidden">
    @csrf
    @method('DELETE')
</form>
