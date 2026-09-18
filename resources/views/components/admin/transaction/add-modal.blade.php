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

                {{-- Searchable equipment checklist.
                     Field names are unchanged: each row posts equipment[] and
                     quantities[<id>]. The quantity input carries its real name but
                     stays disabled until the row is ticked, and disabled controls
                     are not submitted, so unticked rows post nothing at all. --}}
                <div>
                    <label for="equipment-search" class="block text-base font-medium text-neutral-800">Select Equipment</label>

                    <div class="relative mt-2">
                        <i class="fas fa-search absolute text-sm -translate-y-1/2 pointer-events-none left-4 top-1/2 text-neutral-500"></i>
                        <input type="text" id="equipment-search" autocomplete="off" placeholder="Search equipment by name..."
                               class="w-full py-3 pr-4 text-base border rounded-md pl-11 border-neutral-300 text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                    </div>

                    <div id="equipment-list" class="mt-2 overflow-y-auto border rounded-md max-h-72 border-neutral-300 divide-y divide-neutral-200">
                        @forelse ($equipment->where('status', 'Available')->where('available_quantity', '>', 0) as $eq)
                            <div class="p-4 equipment-option" data-name="{{ strtolower($eq->equipment_name) }}">
                                <label for="equipment-{{ $eq->id }}" class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="equipment[]" value="{{ $eq->id }}" id="equipment-{{ $eq->id }}"
                                           class="w-5 h-5 mt-1 rounded shrink-0 equipment-checkbox accent-primary-600"
                                           data-available="{{ $eq->available_quantity }}">
                                    <span class="flex-1 min-w-0">
                                        <span class="block text-base font-medium text-neutral-900">{{ $eq->equipment_name }}</span>
                                        <span class="block text-sm text-neutral-600">
                                            Available: <span class="font-semibold tabular-nums">{{ $eq->available_quantity }}</span>
                                        </span>
                                    </span>
                                </label>

                                <div class="hidden mt-3 equipment-qty-wrap pl-8">
                                    <label for="quantity-{{ $eq->id }}" class="block text-sm font-medium text-neutral-800">Quantity</label>
                                    <input type="number" id="quantity-{{ $eq->id }}" name="quantities[{{ $eq->id }}]"
                                           min="1" max="{{ $eq->available_quantity }}" value="1" required disabled
                                           class="w-full py-3 mt-1 text-base border rounded-md equipment-qty sm:w-40 px-4 border-neutral-300 text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none tabular-nums">
                                    <p class="hidden mt-2 text-sm font-medium equipment-qty-msg text-danger-700"></p>
                                </div>
                            </div>
                        @empty
                            <p class="p-4 text-base text-neutral-600">No equipment is currently available to borrow.</p>
                        @endforelse
                    </div>

                    <p id="equipment-no-match" class="hidden mt-2 text-sm text-neutral-600">No equipment matches your search.</p>
                    <p class="mt-2 text-sm text-neutral-600">
                        <i class="fas fa-info-circle"></i> Tick each item you need, then set its quantity.
                    </p>
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
