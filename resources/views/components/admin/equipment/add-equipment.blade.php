<!-- Add Equipment Modal -->
<div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50">
    <div class="modal-fade mx-4 w-full max-w-md rounded-lg bg-white shadow-lg">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-base font-semibold text-gray-900">Add Equipment</h3>
        </div>

        <form id="add-form" action="{{ route('admin.equipment.store') }}" method="POST" class="space-y-4 p-6">
            @csrf

            <div>
                <label for="add-name" class="mb-1 block text-sm font-medium text-gray-700">Equipment Name</label>
                <input type="text" id="add-name" name="equipment_name" required
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <div>
                <label for="add-description" class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                <textarea id="add-description" name="description" rows="3"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="add-quantity" class="mb-1 block text-sm font-medium text-gray-700">Total Quantity</label>
                    <input type="number" id="add-quantity" name="quantity" required min="1"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>

                <div>
                    <label for="add-available" class="mb-1 block text-sm font-medium text-gray-700">Available Quantity</label>
                    <input type="number" id="add-available" name="available_quantity" required min="0"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
            </div>

            <div>
                <label for="add-status" class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                <select id="add-status" name="status"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="Available">Available</option>
                    <option value="Unavailable">Unavailable</option>
                </select>
            </div>

            <div class="flex justify-end space-x-3 border-t border-gray-200 pt-4">
                <button type="button" id="cancel-add"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Cancel</button>
                <button type="submit"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700">Add
                    Equipment</button>
            </div>
        </form>
    </div>
</div>
