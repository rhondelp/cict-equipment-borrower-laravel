
    <!-- Edit Equipment Modal -->
    <div id="edit-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Edit Equipment</h3>
            </div>

            <form id="edit-form" action="{{ route('admin.equipment.update') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <input type="hidden" id="edit-id" name="id">

                <div>
                    <label for="edit-name" class="block text-sm font-medium text-gray-700 mb-1">Equipment Name</label>
                    <input type="text" id="edit-name" name="equipment_name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="edit-description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="edit-description" name="description" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit-quantity" class="block text-sm font-medium text-gray-700 mb-1">Total
                            Quantity</label>
                        <input type="number" id="edit-quantity" name="quantity"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="edit-available" class="block text-sm font-medium text-gray-700 mb-1">Available
                            Quantity</label>
                        <input type="number" id="edit-available" name="available_quantity"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label for="edit-status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="edit-status" name="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="Available">Available</option>
                        <option value="Unavailable">Unavailable</option>
                    </select>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button type="button" id="cancel-edit"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium">Cancel</button>
                    <button type="submit" id="save-edit"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium transition-colors duration-200">Save
                        Changes</button>
                </div>
            </form>


        </div>
    </div>
