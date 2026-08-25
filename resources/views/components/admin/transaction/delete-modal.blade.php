<!-- Delete Transaction Modal -->
<div id="delete-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg">
        <h2 class="mb-4 text-lg font-semibold text-gray-800">Delete Transaction</h2>
        <p class="text-gray-600">Are you sure you want to delete <span id="delete-item-name" class="font-semibold"></span>?</p>

        <form id="delete-form" method="POST" class="flex justify-end mt-4 space-x-2">
            @csrf
            @method('DELETE')
            <button type="button" id="cancel-delete" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
            <button type="submit" class="px-4 py-2 text-white bg-red-600 rounded hover:bg-red-700">Delete</button>
        </form>
    </div>
</div>
