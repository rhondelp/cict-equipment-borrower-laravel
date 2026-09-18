<!-- Add Transaction Modal — flat light theme -->
<div id="add-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-neutral-900/50 p-4">
    <div class="w-full max-w-xl bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-neutral-900">
                <i class="text-base text-primary-600 fas fa-exchange-alt"></i> Add Transaction
            </h3>
            <button type="button" class="w-10 h-10 grid place-items-center rounded-md text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 cancel-add" aria-label="Close">
                <i class="text-base fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('admin.transaction.store') }}" method="POST" class="flex flex-col flex-1 min-h-0">
            @csrf

            <div class="px-6 py-5 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label for="add-user" class="block text-base font-medium text-neutral-800">Select User</label>
                    <select id="add-user" name="user_id" required
                            class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                        <option value="" disabled selected>-- Select User --</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="equipment-select" class="block text-base font-medium text-neutral-800">Select Equipment</label>
                    <select name="equipment[]" id="equipment-select" multiple required
                            class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none min-h-[120px]">
                        @foreach ($equipment->where('status', 'Available')->where('available_quantity', '>', 0) as $eq)
                            <option value="{{ $eq->id }}">{{ $eq->id }} | {{ $eq->equipment_name }} (Available: {{ $eq->available_quantity }})</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-sm text-neutral-600">
                        <i class="fas fa-info-circle"></i> Hold Ctrl (Windows) or Command (Mac) to select multiple items.
                    </p>
                </div>

                <div id="equipment-quantities" class="space-y-4">
                    {{-- Quantity fields appear here after selecting equipment --}}
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="add-borrow-date" class="block text-base font-medium text-neutral-800">Borrow Date</label>
                        <input type="date" id="add-borrow-date" name="borrow_date" required
                               class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                    </div>
                    <div>
                        <label for="add-return-date" class="block text-base font-medium text-neutral-800">Return Date</label>
                        <input type="date" id="add-return-date" name="return_date" required
                               class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label for="add-purpose" class="block text-base font-medium text-neutral-800">Purpose</label>
                    <input type="text" id="add-purpose" name="purpose" required placeholder="Reason for borrowing"
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>

                <div>
                    <label for="add-status" class="block text-base font-medium text-neutral-800">Transaction Status</label>
                    <select id="add-status" name="status" required
                            class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                        <option value="Borrowed">Borrowed</option>
                        <option value="Returned">Returned</option>
                        <option value="Overdue">Overdue</option>
                    </select>
                </div>

                <div>
                    <label for="add-remarks" class="block text-base font-medium text-neutral-800">Remarks (Optional)</label>
                    <textarea id="add-remarks" name="remarks" rows="2" placeholder="Optional notes..."
                              class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none"></textarea>
                </div>

                <div>
                    <label for="add-class-schedule" class="block text-base font-medium text-neutral-800">Class Schedule (Optional)</label>
                    <select id="add-class-schedule" name="class_schedule_id"
                            class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                        <option value="" selected>-- None --</option>
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
                <button type="button" id="cancel-add" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 text-base font-semibold rounded-md bg-white text-neutral-700 border border-neutral-300 hover:bg-neutral-50 cancel-add">Cancel</button>
                <button type="submit" class="inline-flex items-center justify-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold rounded-md bg-primary-600 text-white hover:bg-primary-700">
                    <i class="text-base fas fa-save"></i> Create Transaction
                </button>
            </div>
        </form>
    </div>
</div>
