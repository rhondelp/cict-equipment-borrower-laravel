{{-- Edit an open loan.

     Two things changed here. The status select is gone — status is derived from
     the due date and from whether the equipment has come back, so there is no
     longer a control that can set it to something the data contradicts. And the
     quantity is capped at what the shelf can actually cover: the units already
     on this loan, plus whatever is still available of the item selected.

     Only open loans reach this form. A returned or voided loan is the audit
     trail, and BorrowTransactionController::update refuses to edit one. --}}
<div id="edit-loan-modal" data-modal
     class="fixed inset-0 z-modal items-center justify-center hidden p-4 overflow-y-auto bg-neutral-900/50"
     role="dialog" aria-modal="true" aria-labelledby="edit-loan-title">
    <div class="w-full max-w-xl my-auto bg-white border border-neutral-200 rounded-xl shadow-pop">
        <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4">
            <div class="min-w-0">
                <h2 id="edit-loan-title" class="text-lg font-semibold text-neutral-900">Edit loan</h2>
                <p data-edit-summary class="mt-1 text-sm text-neutral-600"></p>
            </div>
            <button type="button" data-modal-close="edit-loan-modal" aria-label="Close"
                    class="grid w-10 h-10 border rounded-md shrink-0 place-items-center border-neutral-200 text-neutral-600 hover:bg-neutral-50">
                <i class="text-base fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <form id="edit-loan-form" method="POST" action="{{ route('admin.transaction.update') }}">
            @csrf
            <input type="hidden" name="id" id="edit-loan-id">

            <div class="px-6 pb-5 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="edit-loan-user" class="block text-base font-medium text-neutral-800">Borrower</label>
                        <select name="user_id" id="edit-loan-user" required
                                class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="edit-loan-equipment" class="block text-base font-medium text-neutral-800">Equipment</label>
                        <select name="equipment_id" id="edit-loan-equipment" required
                                class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                            @foreach ($equipment as $item)
                                <option value="{{ $item->id }}" data-available="{{ $item->available_quantity }}">
                                    {{ $item->equipment_name }} · {{ $item->available_quantity }} free
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label for="edit-loan-borrow" class="block text-base font-medium text-neutral-800">Taken out</label>
                        <input type="date" name="borrow_date" id="edit-loan-borrow" required
                               class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                    <div>
                        <label for="edit-loan-return" class="block text-base font-medium text-neutral-800">Due back</label>
                        <input type="date" name="return_date" id="edit-loan-return" required
                               class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                    <div>
                        <label for="edit-loan-quantity" class="block text-base font-medium text-neutral-800">
                            Units <span data-edit-quantity-max class="text-sm font-normal text-neutral-600"></span>
                        </label>
                        <input type="number" name="quantity" id="edit-loan-quantity" min="1" required
                               class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base tabular-nums text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                </div>

                <div>
                    <label for="edit-loan-purpose" class="block text-base font-medium text-neutral-800">Purpose</label>
                    <input type="text" name="purpose" id="edit-loan-purpose" required maxlength="255"
                           class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                </div>

                <div>
                    <label for="edit-loan-class" class="flex items-baseline gap-2 text-base font-medium text-neutral-800">
                        Class schedule <span class="text-sm font-normal text-neutral-600">optional</span>
                    </label>
                    <select name="class_schedule_id" id="edit-loan-class"
                            class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                        <option value="">Not tied to a class</option>
                        {{-- Same display-only grouping as the new-loan modal. --}}
                        @foreach ($classSchedules->groupBy(fn ($s) => $s->instructor?->name ?? 'No Instructor')->sortKeys() as $instructorName => $instructorSchedules)
                            <optgroup label="{{ $instructorName }}">
                                @foreach ($instructorSchedules as $schedule)
                                    <option value="{{ $schedule->id }}">{{ $schedule->subject_code }} · {{ $schedule->schedule_time }} · {{ $schedule->room }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="edit-loan-remarks" class="flex items-baseline gap-2 text-base font-medium text-neutral-800">
                        Remarks <span class="text-sm font-normal text-neutral-600">optional</span>
                    </label>
                    <textarea name="remarks" id="edit-loan-remarks" rows="2"
                              class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30"></textarea>
                </div>

                <p data-edit-preview class="flex items-center gap-2.5 rounded-lg bg-neutral-50 px-4 py-3 text-sm text-neutral-700">
                    <span data-edit-preview-dot class="w-2 h-2 rounded-full shrink-0 bg-success-600" aria-hidden="true"></span>
                    <span data-edit-preview-text></span>
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <p data-edit-hint class="text-sm min-w-0 text-neutral-600"></p>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" data-modal-close="edit-loan-modal"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                        Cancel
                    </button>
                    <button type="submit" data-edit-submit
                            class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-md bg-primary-600 px-5 py-3 text-base font-semibold text-white hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-primary-600">
                        <i class="text-base fas fa-save" aria-hidden="true"></i> Save changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
