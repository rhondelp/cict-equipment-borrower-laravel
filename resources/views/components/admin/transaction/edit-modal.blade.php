<!-- Edit Transaction Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="w-full max-w-2xl p-6 bg-white shadow-2xl rounded-xl animate-fadeIn">
        <!-- Title -->
        <h2 class="flex items-center mb-6 text-2xl font-semibold text-gray-900">
            <i class="mr-2 text-blue-600 fas fa-edit"></i> Edit Transaction
        </h2>

        <form id="edit-form" action="{{ route('admin.transaction.update') }}" method="POST" class="space-y-5">
            @csrf
            <input type="hidden" name="id" id="edit-id">

            <!-- User -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">User</label>
                <select name="user_id" id="edit-user"
                    class="w-full px-3 py-2 transition border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Equipment -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Equipment</label>
                <select name="equipment_id" id="edit-equipment"
                    class="w-full px-3 py-2 transition border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @foreach ($equipment as $eq)
                        <option value="{{ $eq->id }}">{{ $eq->equipment_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Borrow Date</label>
                    <input type="date" name="borrow_date" id="edit-borrow"
                        class="w-full px-3 py-2 transition border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Return Date</label>
                    <input type="date" name="return_date" id="edit-return"
                        class="w-full px-3 py-2 transition border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Quantity -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Quantity</label>
                <input type="number" name="quantity" id="edit-quantity" min="1"
                    class="w-full px-3 py-2 transition border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Purpose -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Purpose</label>
                <input type="text" name="purpose" id="edit-purpose"
                    class="w-full px-3 py-2 transition border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Status -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="edit-status"
                    class="w-full px-3 py-2 transition border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="Borrowed">Borrowed</option>
                    <option value="Returned">Returned</option>
                    <option value="Overdue">Overdue</option>
                </select>
            </div>

            <!-- Remarks -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Remarks</label>
                <textarea name="remarks" id="edit-remarks" rows="2"
                    class="w-full px-3 py-2 transition border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <!-- Class Schedule -->
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Class Schedule (Optional)</label>
                <select name="class_schedule_id" id="edit-class"
                    class="w-full px-3 py-2 transition border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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

            <!-- Actions -->
            <div class="flex justify-end mt-6 space-x-3">
                <button type="button" id="cancel-edit"
                    class="px-4 py-2 text-gray-700 transition bg-gray-200 rounded-lg hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit"
                    class="px-5 py-2 text-white transition bg-blue-600 rounded-lg shadow hover:bg-blue-700">
                    <i class="mr-1 fas fa-save"></i> Update
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Smooth fade-in for modal */
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.animate-fadeIn {
    animation: fadeIn 0.2s ease-out;
}
</style>
