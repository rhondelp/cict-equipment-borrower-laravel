    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-50">
        <div class="w-full max-w-md mx-4 bg-white shadow-2xl rounded-xl">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Delete Equipment</h3>
            </div>

            <div class="p-6">
                <p class="text-gray-600">Are you sure you want to delete <span id="delete-item-name"
                        class="font-semibold"></span>? This action cannot be undone.</p>
            </div>
            <form id="delete-form" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="flex justify-end px-6 py-4 space-x-3 border-t border-gray-200">
                    <button type="button" id="cancel-delete"
                        class="px-4 py-2 font-medium text-gray-600 hover:text-gray-800">Cancel</button>
                    <button type="button" id="confirm-delete"
                        class="px-4 py-2 font-medium text-white transition-colors duration-200 bg-red-500 rounded-lg hover:bg-red-600">Delete</button>
                </div>
            </form>
        </div>
    </div>
