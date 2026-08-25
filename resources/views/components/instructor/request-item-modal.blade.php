<!-- Add Request Modal -->
<div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50">
    <div class="modal-fade mx-4 w-full max-w-lg rounded-lg bg-white shadow-lg">
        <!-- Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-base font-semibold text-gray-900">New Item Request</h2>
        </div>

        <form id="add-form" method="POST" action="{{ route('borrower.request.store') }}" class="space-y-4 p-6">
            @csrf

            <!-- Equipment dropdown -->
            <div>
                <label for="equipment_id" class="mb-1 block text-sm font-medium text-gray-700">
                    Select Equipment
                </label>
                <select name="equipment_id" id="equipment_id" required
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="" disabled selected>Select equipment</option>
                    @foreach ($equipments as $equipment)
                        <option value="{{ $equipment->id }}">{{ $equipment->equipment_name }} | Available: {{ $equipment->available_quantity }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Quantity -->
            <div>
                <label for="quantity" class="mb-1 block text-sm font-medium text-gray-700">
                    Quantity
                </label>
                <input type="number" name="quantity" id="quantity" min="1" required
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <!-- Remarks -->
            <div>
                <label for="remarks" class="block text-sm font-medium text-gray-700">Remarks (optional)</label>
                <textarea name="remarks" id="remarks" rows="3"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"></textarea>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end space-x-3 pt-2">
                <button type="button" id="cancel-add"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>
