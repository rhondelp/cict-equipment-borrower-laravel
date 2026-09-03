<!-- Add Equipment Modal — z-[60] above sidebar -->
<div id="add-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60] hidden">
    <div class="modal-card max-w-md w-full mx-4 animate-fade-in">
        <div class="modal-header">
            <h3 class="flex items-center gap-2">
                <i class="text-primary-300 fas fa-tools text-sm"></i> Add Equipment
            </h3>
            <button type="button" class="modal-close cancel-add" aria-label="Close">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <form id="add-form" action="{{ route('admin.equipment.store') }}" method="POST">
            @csrf

            <div class="modal-body space-y-4">
                <div class="ds-field">
                    <label for="add-name">Equipment Name</label>
                    <input type="text" id="add-name" name="equipment_name" required placeholder="Enter equipment name">
                </div>

                <div class="ds-field">
                    <label for="add-description">Description</label>
                    <textarea id="add-description" name="description" rows="3" placeholder="Describe the equipment..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="ds-field">
                        <label for="add-quantity">Total Quantity</label>
                        <input type="number" id="add-quantity" name="quantity" required min="1" placeholder="0" class="tabular-nums">
                    </div>

                    <div class="ds-field">
                        <label for="add-available">Available Quantity</label>
                        <input type="number" id="add-available" name="available_quantity" required min="0" placeholder="0" class="tabular-nums">
                    </div>
                </div>

                <div class="ds-field">
                    <label for="add-status">Status</label>
                    <select id="add-status" name="status">
                        <option value="Available">Available</option>
                        <option value="Unavailable">Unavailable</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="cancel-add" class="btn-ds-secondary cancel-add">Cancel</button>
                <button type="submit" class="btn-ds-primary">
                    <i class="fas fa-plus text-xs mr-1"></i> Add Equipment
                </button>
            </div>
        </form>
    </div>
</div>
