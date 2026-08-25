<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50">
    <div class="modal-fade mx-4 w-full max-w-md rounded-lg bg-white shadow-lg">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-base font-semibold text-gray-900">Delete Equipment</h3>
        </div>

        <div class="p-6 pb-0">
            <p class="text-sm text-gray-600">Are you sure you want to delete <span id="delete-item-name"
                    class="font-semibold"></span>? This action cannot be undone.</p>
        </div>
        <form id="delete-form" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="flex justify-end space-x-3 p-6">
                <button type="button" id="cancel-delete"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Cancel</button>
                <button type="button" id="confirm-delete"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-red-700">Delete</button>
            </div>
        </form>
    </div>
</div>
