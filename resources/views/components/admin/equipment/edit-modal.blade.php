<!-- Edit Equipment Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50">
    <div class="modal-fade mx-4 w-full max-w-md rounded-lg bg-white shadow-lg">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-base font-semibold text-gray-900">Edit Equipment</h3>
        </div>

        <form id="edit-form" action="{{ route('admin.equipment.update') }}" method="POST" class="space-y-4 p-6">
            @csrf
            <input type="hidden" id="edit-id" name="id">

            <div>
                <label for="edit-name" class="mb-1 block text-sm font-medium text-gray-700">Equipment Name</label>
                <input type="text" id="edit-name" name="equipment_name"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            </div>

            <div>
                <label for="edit-description" class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                <textarea id="edit-description" name="description" rows="3"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="edit-quantity" class="mb-1 block text-sm font-medium text-gray-700">Total Quantity</label>
                    <input type="number" id="edit-quantity" name="quantity"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                </div>

                <div>
                    <label for="edit-available" class="mb-1 block text-sm font-medium text-gray-700">Available Quantity</label>
                    <input type="number" id="edit-available" name="available_quantity"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                </div>
            </div>

            <div>
                <label for="edit-status" class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                <select id="edit-status" name="status"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                    <option value="Available">Available</option>
                    <option value="Unavailable">Unavailable</option>
                </select>
            </div>

            <div class="flex justify-end space-x-3 border-t border-gray-200 pt-4">
                <button type="button" id="cancel-edit"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Cancel</button>
                <button type="submit" id="save-edit"
                    class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-dark">Save
                    Changes</button>
            </div>
        </form>
    </div>
</div>
