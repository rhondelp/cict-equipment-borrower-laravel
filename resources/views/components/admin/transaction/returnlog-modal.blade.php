<!-- Return Log Modal -->
<div id="returnLogModal" class="fixed inset-0 flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="p-6 bg-white rounded-lg shadow-lg w-96">
        <h2 class="mb-4 text-lg font-bold">Return Equipment</h2>

        <form id="returnLogForm">
            <input type="hidden" id="return-transaction-id">

            <!-- Condition -->
            <label class="block mb-1 text-sm font-medium text-gray-700">Condition</label>
            <select id="return-condition" class="w-full p-2 mb-4 border rounded">
                <option value="Good">Good</option>
                <option value="Damaged">Damaged</option>
                <option value="Needs Repair">Needs Repair</option>
            </select>

            <!-- Remarks -->
            <label class="block mb-1 text-sm font-medium text-gray-700">Remarks</label>
            <textarea id="return-remarks" class="w-full p-2 mb-4 border rounded"
                placeholder="Optional remarks..."></textarea>

            <div class="flex justify-end space-x-2">
                <button type="button" id="cancelReturn" class="px-3 py-1 bg-gray-300 rounded">Cancel</button>
                <button type="submit" class="px-3 py-1 text-white bg-gray-900 rounded">Confirm</button>
            </div>
        </form>
    </div>
</div>
