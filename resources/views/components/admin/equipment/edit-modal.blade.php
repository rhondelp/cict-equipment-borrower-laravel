<!-- Edit Equipment Modal — z-[60] -->
<div id="edit-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60] hidden">
    <div class="w-full max-w-md mx-4 modal-card animate-fade-in" style="color: #f1f5f9;">
        <div class="modal-header">
            <h3 class="flex items-center gap-2" style="color: white !important;">
                <i class="text-primary-300 fas fa-edit text-sm"></i> Edit Equipment
            </h3>
            <button type="button" class="modal-close cancel-edit" id="cancel-edit-x" aria-label="Close">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <form id="edit-form" action="{{ route('admin.equipment.update') }}" method="POST">
            @csrf
            <input type="hidden" id="edit-id" name="id">

            <div class="modal-body space-y-4">
                <div class="ds-field">
                    <label for="edit-name">Equipment Name</label>
                    <input type="text" id="edit-name" name="equipment_name" required placeholder="Enter equipment name">
                </div>

                <div class="ds-field">
                    <label for="edit-description">Description</label>
                    <textarea id="edit-description" name="description" rows="3" placeholder="Describe the equipment..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ds-field">
                        <label for="edit-quantity">Total Quantity</label>
                        <input type="number" id="edit-quantity" name="quantity" required min="1" placeholder="0" class="tabular-nums">
                    </div>

                    <div class="ds-field">
                        <label for="edit-available">Available Quantity</label>
                        <input type="number" id="edit-available" name="available_quantity" required min="0" placeholder="0" class="tabular-nums">
                    </div>
                </div>

                <div class="ds-field">
                    <label for="edit-status">Status</label>
                    <select id="edit-status" name="status">
                        <option value="Available">Available</option>
                        <option value="Unavailable">Unavailable</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="cancel-edit" class="btn-ds-secondary cancel-edit">Cancel</button>
                <button type="submit" id="save-edit" class="btn-ds-primary">
                    <i class="fas fa-save text-xs mr-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>