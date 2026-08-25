<!-- Edit Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="w-full max-w-lg p-6 mx-4 bg-white shadow-2xl rounded-2xl animate-fadeIn">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-2 mb-4 border-b">
            <h2 class="flex items-center text-xl font-semibold text-gray-800">
                <i class="mr-2 text-blue-600 fas fa-edit"></i> Edit Item Request
            </h2>
            <button id="cancel-edit" class="text-gray-500 hover:text-gray-700">
                <i class="text-lg fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="edit-form" method="POST" action="{{ route('borrower.request.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="id" id="edit-id">

            <!-- Equipment Name (readonly) -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Equipment</label>
                <p id="edit-equipment-name" class="px-3 py-2 mt-1 font-medium text-gray-900 bg-gray-100 rounded-lg"></p>
            </div>

            <!-- Quantity -->
            <div>
                <label for="edit-quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                <input type="number" name="quantity" id="edit-quantity" required
                    class="block w-full px-3 py-2 mt-1 transition border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>


            <!-- Remarks -->
            <div>
                <label for="edit-remarks" class="block text-sm font-medium text-gray-700">Remarks</label>
                <textarea name="remarks" id="edit-remarks" rows="3"
                    class="block w-full px-3 py-2 mt-1 transition border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <!-- Footer -->
            <div class="flex justify-end pt-3 space-x-3 border-t">
                <button type="button" id="cancel-edit"
                    class="px-4 py-2 text-sm font-medium text-gray-700 transition bg-gray-200 rounded-lg hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white transition bg-blue-600 rounded-lg shadow hover:bg-blue-700">
                    <i class="mr-1 fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Simple fade-in animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out;
    }
</style>
