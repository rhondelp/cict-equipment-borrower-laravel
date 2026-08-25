<!-- Edit Transaction Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50">
    <div class="modal-fade mx-4 w-full max-w-2xl rounded-lg bg-white shadow-lg">
        <!-- Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-base font-semibold text-gray-900">Edit Transaction</h2>
        </div>

        <form id="edit-form" action="{{ route('admin.transaction.update') }}" method="POST" class="space-y-5 p-6">
            @csrf
            <input type="hidden" name="id" id="edit-id">

            <!-- User -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">User</label>
                <select name="user_id" id="edit-user"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Equipment -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Equipment</label>
                <select name="equipment_id" id="edit-equipment"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @foreach ($equipment as $eq)
                        <option value="{{ $eq->id }}">{{ $eq->equipment_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Borrow Date</label>
                    <input type="date" name="borrow_date" id="edit-borrow"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Return Date</label>
                    <input type="date" name="return_date" id="edit-return"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
            </div>

            <!-- Quantity -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Quantity</label>
                <input type="number" name="quantity" id="edit-quantity" min="1"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <!-- Purpose -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Purpose</label>
                <input type="text" name="purpose" id="edit-purpose"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <!-- Status -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="edit-status"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="Borrowed">Borrowed</option>
                    <option value="Returned">Returned</option>
                    <option value="Overdue">Overdue</option>
                </select>
            </div>

            <!-- Remarks -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Remarks</label>
                <textarea name="remarks" id="edit-remarks" rows="2"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"></textarea>
            </div>

            <!-- Class Schedule -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Class Schedule (Optional)</label>
                <select name="class_schedule_id" id="edit-class"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
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
            <div class="flex justify-end space-x-3 border-t border-gray-200 pt-4">
                <button type="button" id="cancel-edit"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit"
                    class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    <i class="mr-1 fas fa-save"></i> Update
                </button>
            </div>
        </form>
    </div>
</div>
