<!-- Edit Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center {{ ($editOpen ?? false) ? '' : 'hidden' }} bg-black/50">
    <div class="modal-fade mx-4 w-full max-w-lg rounded-lg bg-white shadow-lg">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
            <h2 class="text-base font-semibold text-gray-900">
                Edit Item Request
            </h2>
            <button type="button" data-dismiss="#edit-modal" class="text-gray-400 transition hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="edit-form" method="POST" action="{{ route('borrower.request.update') }}" class="space-y-4 p-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="id" id="edit-id">

            <!-- Equipment Name (readonly) -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Equipment</label>
                <p id="edit-equipment-name" class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900"></p>
            </div>

            <!-- Quantity -->
            <div>
                <label for="edit-quantity" class="mb-1 block text-sm font-medium text-gray-700">Quantity</label>
                <input type="number" name="quantity" id="edit-quantity" required value="{{ old('quantity') }}"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
            </div>

            <!-- Remarks -->
            <div>
                <label for="edit-remarks" class="mb-1 block text-sm font-medium text-gray-700">Remarks</label>
                <textarea name="remarks" id="edit-remarks" rows="3"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">{{ old('remarks') }}</textarea>
            </div>

            <!-- Footer -->
            <div class="flex justify-end space-x-3 border-t border-gray-200 pt-4">
                <button type="button" id="cancel-edit"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit"
                    class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
                    <i class="mr-1 fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
