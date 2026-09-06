<!-- Return Log Modal — flat light theme -->
<div id="returnLogModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-neutral-900/50 p-4">
    <div class="w-full max-w-sm bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="flex items-center gap-2 text-base font-semibold text-neutral-900">
                <i class="text-sm text-primary-600 fas fa-undo"></i> Return Equipment
            </h3>
            <button type="button" class="w-8 h-8 grid place-items-center rounded-md text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" id="cancelReturn-x" aria-label="Close">
                <i class="text-xs fas fa-times"></i>
            </button>
        </div>

        <form id="returnLogForm" class="flex flex-col flex-1">
            <input type="hidden" id="return-transaction-id">

            <div class="px-6 py-5 space-y-4">
                <div>
                    <label for="return-condition" class="block text-sm font-medium text-neutral-700">Condition</label>
                    <select id="return-condition"
                            class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                        <option value="Good">Good</option>
                        <option value="Damaged">Damaged</option>
                        <option value="Needs Repair">Needs Repair</option>
                    </select>
                </div>

                <div>
                    <label for="return-remarks" class="block text-sm font-medium text-neutral-700">Remarks</label>
                    <textarea id="return-remarks" rows="3" placeholder="Optional remarks..."
                              class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" id="cancelReturn" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md bg-white text-neutral-700 border border-neutral-300 hover:bg-neutral-50">Cancel</button>
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-primary-600 text-white hover:bg-primary-700">
                    <i class="text-xs fas fa-check"></i> Confirm Return
                </button>
            </div>
        </form>
    </div>
</div>
