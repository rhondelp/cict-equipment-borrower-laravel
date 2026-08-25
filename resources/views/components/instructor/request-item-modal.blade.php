<!-- Add Request Modal -->
<div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="w-full max-w-lg p-6 bg-white rounded-lg shadow-lg">
        <h2 class="mb-4 text-xl font-semibold">New Item Request</h2>

        <form id="add-form" method="POST" action="{{ route('borrower.request.store') }}">
            @csrf

            <!-- Equipment dropdown -->
            <div class="mb-4">
                <label for="equipment_id" class="block mb-1 text-sm font-medium text-gray-700">
                    Select Equipment
                </label>
                <select name="equipment_id" id="equipment_id" required
                    class="block w-full px-3 py-2 text-gray-700 transition duration-150 ease-in-out border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="" disabled selected>Select equipment</option>
                    @foreach ($equipments as $equipment)
                        <option value="{{ $equipment->id }}">{{ $equipment->equipment_name }} | Available: {{ $equipment->available_quantity }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Quantity -->
            <div class="mb-4">
                <label for="quantity" class="block mb-1 text-sm font-medium text-gray-700">
                    Quantity
                </label>
                <input type="number" name="quantity" id="quantity" min="1" required
                    class="block w-full px-3 py-2 text-gray-700 transition duration-150 ease-in-out border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>


            <!-- Remarks -->
            <div class="mb-4">
                <label for="remarks" class="block text-sm font-medium text-gray-700">Remarks (optional)</label>
                <textarea name="remarks" id="remarks" rows="3"
                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end space-x-2">
                <button type="button" id="cancel-add"
                    class="px-4 py-2 text-gray-800 bg-gray-300 rounded hover:bg-gray-400">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>
