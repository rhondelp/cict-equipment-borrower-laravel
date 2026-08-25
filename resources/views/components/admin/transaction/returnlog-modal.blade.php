<!-- Return Log Modal -->
<div id="returnLogModal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50">
    <div class="modal-fade mx-4 w-full max-w-sm rounded-lg bg-white shadow-lg">
        <!-- Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-base font-semibold text-gray-900">Return Equipment</h2>
        </div>

        <form id="returnLogForm" class="space-y-4 p-6">
            <input type="hidden" id="return-transaction-id">

            <!-- Condition -->
            <div>
                <label for="return-condition" class="mb-1 block text-sm font-medium text-gray-700">Condition</label>
                <select id="return-condition"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                    <option value="Good">Good</option>
                    <option value="Damaged">Damaged</option>
                    <option value="Needs Repair">Needs Repair</option>
                </select>
            </div>

            <!-- Remarks -->
            <div>
                <label for="return-remarks" class="mb-1 block text-sm font-medium text-gray-700">Remarks</label>
                <textarea id="return-remarks" rows="2"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20"
                    placeholder="Optional remarks..."></textarea>
            </div>

            <div class="flex justify-end space-x-3 border-t border-gray-200 pt-4">
                <button type="button" id="cancelReturn"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">Cancel</button>
                <button type="submit"
                    class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">Confirm</button>
            </div>
        </form>
    </div>
</div>
