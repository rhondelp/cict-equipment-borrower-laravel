<!-- Edit Transaction Modal — flat light theme -->
<div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-neutral-900/50 p-4">
    <div class="w-full max-w-xl bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="flex items-center gap-2 text-base font-semibold text-neutral-900">
                <i class="text-sm text-primary-600 fas fa-edit"></i> Edit Transaction
            </h3>
            <button type="button" class="w-8 h-8 grid place-items-center rounded-md text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 cancel-edit" id="cancel-edit-x" aria-label="Close">
                <i class="text-xs fas fa-times"></i>
            </button>
        </div>

        <form id="edit-form" action="{{ route('admin.transaction.update') }}" method="POST" class="flex flex-col flex-1 min-h-0">
            @csrf
            <input type="hidden" name="id" id="edit-id">

            <div class="px-6 py-5 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label for="edit-user" class="block text-sm font-medium text-neutral-700">User</label>
                    <select name="user_id" id="edit-user"
                            class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="edit-equipment" class="block text-sm font-medium text-neutral-700">Equipment</label>
                    <select name="equipment_id" id="edit-equipment"
                            class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                        @foreach ($equipment as $eq)
                            <option value="{{ $eq->id }}">{{ $eq->equipment_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="edit-borrow" class="block text-sm font-medium text-neutral-700">Borrow Date</label>
                        <input type="date" name="borrow_date" id="edit-borrow"
                               class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                    </div>
                    <div>
                        <label for="edit-return" class="block text-sm font-medium text-neutral-700">Return Date</label>
                        <input type="date" name="return_date" id="edit-return"
                               class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label for="edit-quantity" class="block text-sm font-medium text-neutral-700">Quantity</label>
                    <input type="number" name="quantity" id="edit-quantity" min="1"
                           class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none tabular-nums">
                </div>

                <div>
                    <label for="edit-purpose" class="block text-sm font-medium text-neutral-700">Purpose</label>
                    <input type="text" name="purpose" id="edit-purpose" placeholder="Purpose of borrowing"
                           class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                </div>

                <div>
                    <label for="edit-status" class="block text-sm font-medium text-neutral-700">Status</label>
                    <select name="status" id="edit-status"
                            class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                        <option value="Borrowed">Borrowed</option>
                        <option value="Returned">Returned</option>
                        <option value="Overdue">Overdue</option>
                    </select>
                </div>

                <div>
                    <label for="edit-remarks" class="block text-sm font-medium text-neutral-700">Remarks</label>
                    <textarea name="remarks" id="edit-remarks" rows="2" placeholder="Optional remarks..."
                              class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"></textarea>
                </div>

                <div>
                    <label for="edit-class" class="block text-sm font-medium text-neutral-700">Class Schedule (Optional)</label>
                    <select name="class_schedule_id" id="edit-class"
                            class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                        <option value="">-- None --</option>
                        @foreach ($classSchedules as $schedule)
                            <option value="{{ $schedule->id }}">
                                {{ $schedule->schedule_time }}
                                - {{ $schedule->instructor?->name ?? 'No Instructor' }}
                                - {{ $schedule->room }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" id="cancel-edit" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md bg-white text-neutral-700 border border-neutral-300 hover:bg-neutral-50 cancel-edit">Cancel</button>
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-primary-600 text-white hover:bg-primary-700">
                    <i class="text-xs fas fa-save"></i> Update Transaction
                </button>
            </div>
        </form>
    </div>
</div>
