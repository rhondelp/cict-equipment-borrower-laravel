<!-- Edit Transaction Modal — z-[60] above sidebar -->
<div id="edit-modal" class="fixed inset-0 z-[60] flex items-center justify-center hidden bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="modal-card max-w-2xl w-full mx-4 animate-fade-in">
        <!-- Header -->
        <div class="modal-header">
            <h3 class="flex items-center gap-2 text-white">
                <i class="text-primary-300 fas fa-edit text-sm"></i> Edit Transaction
            </h3>
            <button type="button" class="modal-close cancel-edit" id="cancel-edit-x" aria-label="Close">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <form id="edit-form" action="{{ route('admin.transaction.update') }}" method="POST">
            @csrf
            <input type="hidden" name="id" id="edit-id">

            <div class="modal-body space-y-4 max-h-[70vh] overflow-y-auto pr-1">
                <!-- User -->
                <div class="ds-field">
                    <label for="edit-user">User</label>
                    <select name="user_id" id="edit-user">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Equipment -->
                <div class="ds-field">
                    <label for="edit-equipment">Equipment</label>
                    <select name="equipment_id" id="edit-equipment">
                        @foreach ($equipment as $eq)
                            <option value="{{ $eq->id }}">{{ $eq->equipment_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="ds-field">
                        <label for="edit-borrow">Borrow Date</label>
                        <input type="date" name="borrow_date" id="edit-borrow">
                    </div>
                    <div class="ds-field">
                        <label for="edit-return">Return Date</label>
                        <input type="date" name="return_date" id="edit-return">
                    </div>
                </div>

                <!-- Quantity -->
                <div class="ds-field">
                    <label for="edit-quantity">Quantity</label>
                    <input type="number" name="quantity" id="edit-quantity" min="1" class="tabular-nums">
                </div>

                <!-- Purpose -->
                <div class="ds-field">
                    <label for="edit-purpose">Purpose</label>
                    <input type="text" name="purpose" id="edit-purpose" placeholder="Purpose of borrowing">
                </div>

                <!-- Status -->
                <div class="ds-field">
                    <label for="edit-status">Status</label>
                    <select name="status" id="edit-status">
                        <option value="Borrowed">Borrowed</option>
                        <option value="Returned">Returned</option>
                        <option value="Overdue">Overdue</option>
                    </select>
                </div>

                <!-- Remarks -->
                <div class="ds-field">
                    <label for="edit-remarks">Remarks</label>
                    <textarea name="remarks" id="edit-remarks" rows="2" placeholder="Optional remarks..."></textarea>
                </div>

                <!-- Class Schedule -->
                <div class="ds-field">
                    <label for="edit-class">Class Schedule (Optional)</label>
                    <select name="class_schedule_id" id="edit-class">
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

            <!-- Actions -->
            <div class="modal-footer">
                <button type="button" id="cancel-edit" class="btn-ds-secondary cancel-edit">Cancel</button>
                <button type="submit" class="btn-ds-primary">
                    <i class="fas fa-save text-xs mr-1"></i> Update Transaction
                </button>
            </div>
        </form>
    </div>
</div>