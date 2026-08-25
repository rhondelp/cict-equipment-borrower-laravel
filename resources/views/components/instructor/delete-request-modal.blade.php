<!-- Delete Modal -->
<div id="delete-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg">
        <h2 class="mb-4 text-xl font-semibold">Delete Item Request</h2>
        <form id="delete-form" method="POST">
            @csrf
            @method('DELETE')
            <p class="mb-4">Are you sure you want to delete <strong id="delete-item-name" class="text-red-600"></strong>?</p>
            <div class="flex justify-end space-x-2">
                <button type="button" id="cancel-delete"
                        class="px-4 py-2 text-gray-800 bg-gray-300 rounded hover:bg-gray-400">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-white bg-red-600 rounded hover:bg-red-700">
                    Confirm Delete
                </button>
            </div>
        </form>
    </div>
</div>
