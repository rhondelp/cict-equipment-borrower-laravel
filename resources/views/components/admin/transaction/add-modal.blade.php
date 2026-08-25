<!-- Add Transaction Modal -->
<div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="w-full max-w-2xl p-6 transition-all transform scale-95 bg-white shadow-2xl rounded-2xl hover:scale-100">

        <!-- Header -->
        <div class="flex items-center justify-between pb-3 mb-5 border-b">
            <h2 class="flex items-center text-xl font-bold text-gray-800">
                <i class="mr-2 text-blue-600 fas fa-exchange-alt"></i> Add Transaction
            </h2>
            <button type="button" id="cancel-add" class="text-lg font-bold text-gray-500 transition hover:text-red-600">
                ✕
            </button>
        </div>

        <form action="{{ route('admin.transaction.store') }}" method="POST" class="space-y-5">
            @csrf

            <!-- User Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Select User</label>
                <select name="user_id" required
                    class="w-full px-3 py-2 mt-1 transition border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    <option value="" disabled selected>-- Select User --</option>
                    @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Equipment Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Select Equipment</label>
                <select name="equipment[]" id="equipment-select" multiple required
                    class="w-full px-3 py-2 mt-1 transition border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    <option value="" disabled selected>-- Select Equipment --</option>
                    @foreach ($equipment->where('status', 'Available')->where('available_quantity', '>', 0) as $eq)
                    <option value="{{ $eq->id }}">{{ $eq->id }} | {{ $eq->equipment_name }}</option>
                    @endforeach
                </select>
                <small class="text-gray-600">Hold Ctrl or Command to select multiple items.</small>
            </div>

            <!-- Dynamically Generated Quantity Fields -->
            <div id="equipment-quantities" class="space-y-4">
                <!-- Quantity fields will appear here after selecting equipment -->
            </div>

            <!-- Borrow and Return Dates -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Borrow Date</label>
                    <input type="date" name="borrow_date" required
                        class="w-full px-3 py-2 mt-1 transition border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Return Date</label>
                    <input type="date" name="return_date" required
                        class="w-full px-3 py-2 mt-1 transition border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                </div>
            </div>

            <!-- Purpose of Borrowing -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Purpose</label>
                <input type="text" name="purpose" required
                    class="w-full px-3 py-2 mt-1 transition border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
            </div>

            <!-- Status Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Transaction Status</label>
                <select name="status" required
                    class="w-full px-3 py-2 mt-1 transition border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    <option value="Borrowed">Borrowed</option>
                    <option value="Returned">Returned</option>
                    <option value="Overdue">Overdue</option>
                </select>
            </div>

            <!-- Remarks -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Remarks (Optional)</label>
                <textarea name="remarks" rows="2"
                    class="w-full px-3 py-2 mt-1 transition border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"></textarea>
            </div>

            <!-- Class Schedule (Optional) -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Class Schedule (Optional)</label>
                <select name="class_schedule_id"
                    class="w-full px-3 py-2 mt-1 transition border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
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
            <div class="flex justify-end pt-4 mt-6 space-x-3 border-t">
                <button type="button" id="cancel-add"
                    class="px-5 py-2 text-gray-700 transition bg-gray-200 rounded-lg hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit"
                    class="px-5 py-2 text-white transition bg-blue-600 rounded-lg shadow-md hover:bg-blue-700">
                    <i class="mr-1 fas fa-save"></i> Create Transaction
                </button>
            </div>
        </form>
    </div>
</div>
