<!-- Return Log Modal — z-[60] -->
<div id="returnLogModal" class="fixed inset-0 z-[60] flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="modal-card max-w-sm w-full mx-4 animate-fade-in">
        <!-- Header -->
        <div class="modal-header">
            <h3 class="flex items-center gap-2">
                <i class="text-primary-300 fas fa-undo text-sm"></i> Return Equipment
            </h3>
            <button type="button" class="modal-close" id="cancelReturn-x" aria-label="Close" onclick="document.getElementById('returnLogModal').classList.add('hidden')">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <form id="returnLogForm">
            <input type="hidden" id="return-transaction-id">

            <div class="modal-body space-y-4">
                <!-- Condition -->
                <div class="ds-field">
                    <label for="return-condition">Condition</label>
                    <select id="return-condition">
                        <option value="Good">Good</option>
                        <option value="Damaged">Damaged</option>
                        <option value="Needs Repair">Needs Repair</option>
                    </select>
                </div>

                <!-- Remarks -->
                <div class="ds-field">
                    <label for="return-remarks">Remarks</label>
                    <textarea id="return-remarks" rows="3" placeholder="Optional remarks..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="cancelReturn" class="btn-ds-secondary">Cancel</button>
                <button type="submit" class="btn-ds-primary">
                    <i class="fas fa-check text-xs mr-1"></i> Confirm Return
                </button>
            </div>
        </form>
    </div>
</div>
