<!-- Return Log Modal — flat light theme -->
<div id="returnLogModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-neutral-900/50 p-4">
    <div class="w-full max-w-sm bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-neutral-900">
                <i class="text-base text-primary-600 fas fa-undo"></i> Return Equipment
            </h3>
            <button type="button" class="w-10 h-10 grid place-items-center rounded-md text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900" id="cancelReturn-x" aria-label="Close">
                <i class="text-base fas fa-times"></i>
            </button>
        </div>

        <form id="returnLogForm" class="flex flex-col flex-1">
            <input type="hidden" id="return-transaction-id">

            <div class="px-6 py-5 space-y-4">
                <div>
                    <label for="return-condition" class="block text-base font-medium text-neutral-800">Condition</label>
                    <select id="return-condition"
                            class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                        <option value="Good">Good</option>
                        <option value="Damaged">Damaged</option>
                        <option value="Needs Repair">Needs Repair</option>
                    </select>
                </div>

                <div>
                    <label for="return-remarks" class="block text-base font-medium text-neutral-800">Remarks</label>
                    <textarea id="return-remarks" rows="3" placeholder="Optional remarks..."
                              class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" id="cancelReturn" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 text-base font-semibold rounded-md bg-white text-neutral-700 border border-neutral-300 hover:bg-neutral-50">Cancel</button>
                <button type="submit" class="inline-flex items-center justify-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold rounded-md bg-primary-600 text-white hover:bg-primary-700">
                    <i class="text-base fas fa-check"></i> Confirm Return
                </button>
            </div>
        </form>
    </div>
</div>
