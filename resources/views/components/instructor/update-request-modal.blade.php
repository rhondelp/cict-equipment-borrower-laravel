<!-- Edit Modal — z-[60] -->
<div id="edit-modal" class="fixed inset-0 z-[60] flex items-center justify-center hidden bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="modal-card max-w-xl w-full mx-4 animate-fade-in" style="color: #f1f5f9;">
        <!-- Modal Header -->
        <div class="modal-header">
            <h3 class="flex items-center gap-2" style="color: white !important;">
                <i class="text-primary-300 fas fa-edit text-sm"></i> Edit Item Request
            </h3>
            <button type="button" class="modal-close cancel-edit" aria-label="Close">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <form id="edit-form" method="POST" action="{{ route('borrower.request.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="id" id="edit-id">

            <!-- Equipment Name (readonly) -->
            <div class="ds-field">
                <label>Equipment</label>
                <p id="edit-equipment-name" class="px-3 py-2 mt-1 bg-gray-100 rounded-l rounded-lg text-sm font-medium text-gray-900"></p>
            </div>

            <!-- Quantity -->
            <div class="ds-field">
                <label for="edit-quantity">Quantity</label>
                <input type="number" name="quantity" id="edit-quantity" required class="tabular-nums">
            </div>


            <!-- Remarks -->
            <div class="ds-field">
                <label for="edit-remarks">Remarks</label>
                <textarea name="remarks" id="edit-remarks" rows="3" class="min-h-[60px]"></textarea>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" id="cancel-edit" class="btn-ds-secondary cancel-edit">Cancel</button>
                <button type="submit" class="btn-ds-primary">
                    <i class="fas fa-save text-xs mr-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Simple fade-in animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out;
    }
</style>