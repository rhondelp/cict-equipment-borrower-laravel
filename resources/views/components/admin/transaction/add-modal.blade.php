<!-- Add Transaction Modal -->
<div id="add-modal" class="fixed inset-0 z-[60] flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="modal-card max-w-2xl w-full mx-4 animate-fade-in">
        <!-- Header -->
        <div class="modal-header">
            <h3 class="flex items-center gap-2 text-white">
                <i class="text-primary-300 fas fa-exchange-alt text-sm"></i> Add Transaction
            </h3>
            <button type="button" class="modal-close cancel-add" aria-label="Close">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <form action="{{ route('admin.transaction.store') }}" method="POST">
            @csrf

            <div class="modal-body space-y-4 max-h-[70vh] overflow-y-auto pr-1">
                <!-- User Selection -->
                <div class="ds-field">
                    <label>Select User</label>
                    <select name="user_id" required>
                        <option value="" disabled selected>-- Select User --</option>
                        @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Equipment Selection -->
                <div class="ds-field">
                    <label>Select Equipment</label>
                    <select name="equipment[]" id="equipment-select" class="min-h-[120px]" multiple required>
                        @foreach ($equipment->where('status', 'Available')->where('available_quantity', '>', 0) as $eq)
                        <option value="{{ $eq->id }}">{{ $eq->id }} | {{ $eq->equipment_name }} (Available: {{ $eq->available_quantity }})</option>
                        @endforeach
                    </select>
                    <small class="text-xs mt-1 block" style="color:var(--text-muted)">
                        <i class="fas fa-info-circle mr-1"></i> Hold Ctrl (Windows) or Command (Mac) to select multiple items.
                    </small>
                </div>

                <!-- Dynamically Generated Quantity Fields -->
                <div id="equipment-quantities" class="space-y-4">
                    <!-- Quantity fields will appear here after selecting equipment -->
                </div>

                <!-- Borrow and Return Dates -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="ds-field">
                        <label>Borrow Date</label>
                        <input type="date" name="borrow_date" required>
                    </div>
                    <div class="ds-field">
                        <label>Return Date</label>
                        <input type="date" name="return_date" required>
                    </div>
                </div>

                <!-- Purpose of Borrowing -->
                <div class="ds-field">
                    <label>Purpose</label>
                    <input type="text" name="purpose" required placeholder="Reason for borrowing">
                </div>

                <!-- Status Selection -->
                <div class="ds-field">
                    <label>Transaction Status</label>
                    <select name="status" required>
                        <option value="Borrowed">Borrowed</option>
                        <option value="Returned">Returned</option>
                        <option value="Overdue">Overdue</option>
                    </select>
                </div>

                <!-- Remarks -->
                <div class="ds-field">
                    <label>Remarks (Optional)</label>
                    <textarea name="remarks" rows="2" placeholder="Optional notes..."></textarea>
                </div>

                <!-- Class Schedule (Optional) -->
                <div class="ds-field">
                    <label>Class Schedule (Optional)</label>
                    <select name="class_schedule_id">
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

            <!-- Actions -->
            <div class="modal-footer">
                <button type="button" id="cancel-add" class="btn-ds-secondary cancel-add">Cancel</button>
                <button type="submit" class="btn-ds-primary">
                    <i class="fas fa-save text-xs mr-1"></i> Create Transaction
                </button>
            </div>
        </form>
    </div>
</div>