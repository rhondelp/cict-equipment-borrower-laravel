<!-- Delete Transaction Modal — z-[60] -->
<div id="delete-modal" class="fixed inset-0 z-[60] flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="w-full max-w-md mx-4 modal-card animate-fade-in" style="color: #f1f5f9;">
        <div class="modal-header">
            <h3 class="flex items-center gap-2" style="color: white !important;">
                <i class="text-red-400 fas fa-exclamation-triangle text-sm"></i> Delete Transaction
            </h3>
            <button type="button" class="modal-close" id="cancel-delete-x" aria-label="Close" onclick="document.getElementById('delete-modal').classList.add('hidden')">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <div class="modal-body space-y-3">
            <p class="text-neutral-300">
                Are you sure you want to delete transaction <span id="delete-item-name" class="font-semibold text-white"></span>?
            </p>
            <p class="text-xs text-red-400/80 leading-relaxed">
                <i class="fas fa-info-circle mr-1"></i> This action is irreversible and will permanently delete the transaction record.
            </p>
        </div>

        <form id="delete-form" method="POST">
            @csrf
            @method('DELETE')
            
            <div class="modal-footer">
                <button type="button" id="cancel-delete" class="btn-ds-secondary">Cancel</button>
                <button type="submit" class="btn-ds-danger">
                    <i class="fas fa-trash-alt text-xs mr-1"></i> Delete
                </button>
            </div>
        </form>
    </div>
</div>