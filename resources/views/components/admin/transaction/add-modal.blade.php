<!-- Add Transaction Modal -->
<div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50">
    <div class="modal-fade mx-4 w-full max-w-2xl rounded-lg bg-white shadow-lg">

        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
            <h2 class="text-base font-semibold text-gray-900">
                Add Transaction
            </h2>
            <button type="button" id="cancel-add" class="text-gray-400 transition hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('admin.transaction.store') }}" method="POST" class="space-y-5 p-6">
            @csrf

            <!-- User Selection -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Select User</label>
                <select name="user_id" required
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="" disabled selected>-- Select User --</option>
                    @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Equipment Selection -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Select Equipment</label>
                <select name="equipment[]" id="equipment-select" multiple required
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="" disabled selected>-- Select Equipment --</option>
                    @foreach ($equipment->where('status', 'Available')->where('available_quantity', '>', 0) as $eq)
                    <option value="{{ $eq->id }}">{{ $eq->id }} | {{ $eq->equipment_name }}</option>
                    @endforeach
                </select>
                <small class="text-xs text-gray-500">Hold Ctrl or Command to select multiple items.</small>
            </div>

            <!-- Dynamically Generated Quantity Fields -->
            <div id="equipment-quantities" class="space-y-4">
                <!-- Quantity fields will appear here after selecting equipment -->
            </div>

            <!-- Borrow and Return Dates -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Borrow Date</label>
                    <input type="date" name="borrow_date" required
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Return Date</label>
                    <input type="date" name="return_date" required
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
            </div>

            <!-- Purpose of Borrowing -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Purpose</label>
                <input type="text" name="purpose" required
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <!-- Status Selection -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Transaction Status</label>
                <select name="status" required
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="Borrowed">Borrowed</option>
                    <option value="Returned">Returned</option>
                    <option value="Overdue">Overdue</option>
                </select>
            </div>

            <!-- Remarks -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Remarks (Optional)</label>
                <textarea name="remarks" rows="2"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"></textarea>
            </div>

            <!-- Class Schedule (Optional) -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Class Schedule (Optional)</label>
                <select name="class_schedule_id"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
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

            <!-- Actions -->
            <div class="flex justify-end space-x-3 border-t border-gray-200 pt-4">
                <button type="button" id="cancel-add"
                    class="rounded-lg bg-gray-100 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit"
                    class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    <i class="mr-1 fas fa-save"></i> Create Transaction
                </button>
            </div>
        </form>
    </div>
</div>
