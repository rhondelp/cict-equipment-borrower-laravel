<!-- Delete Modal -->
<div id="delete-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50">
    <div class="modal-fade mx-4 w-full max-w-md rounded-lg bg-white shadow-lg">
        <!-- Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-base font-semibold text-gray-900">Delete Item Request</h2>
        </div>

        <form id="delete-form" method="POST" class="p-6">
            @csrf
            @method('DELETE')
            <p class="text-sm text-gray-600">Are you sure you want to delete <strong id="delete-item-name" class="text-red-600"></strong>? This action cannot be undone.</p>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="cancel-delete"
                        class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    Confirm Delete
                </button>
            </div>
        </form>
    </div>
</div>
