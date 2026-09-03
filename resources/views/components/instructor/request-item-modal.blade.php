<!-- Add Request Modal — z-[60] -->
<div id="add-modal" class="fixed inset-0 z-[60] flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="modal-card max-w-xl w-full mx-4 animate-fade-in">
        <!-- Modal Header -->
        <div class="modal-header">
            <h3 class="flex items-center gap-2 text-white">
                <i class="text-primary-300 fas fa-plus text-sm"></i> New Item Request
            </h3>
            <button type="button" class="modal-close cancel-add" aria-label="Close">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <form id="add-form" method="POST" action="{{ route('borrower.request.store') }}">
            @csrf

            <div class="modal-body space-y-4">
                <!-- Equipment dropdown -->
                <div class="ds-field">
                    <label>Select Equipment</label>
                    <select name="equipment_id" required class="min-h-[120px]">
                        <option value="" disabled selected>Select equipment</option>
                        @foreach ($equipments as $equipment)
                            <option value="{{ $equipment->id }}">{{ $equipment->equipment_name }} | Available: {{ $equipment->available_quantity }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Quantity -->
                <div class="ds-field">
                    <label>Quantity</label>
                    <input type="number" name="quantity" min="1" required class="tabular-nums">
                </div>

                <!-- Remarks -->
                <div class="ds-field">
                    <label>Remarks (optional)</label>
                    <textarea name="remarks" rows="3" class="min-h-[60px]"></textarea>
                </div>
            </div>

            <!-- Buttons -->
            <div class="modal-footer">
                <button type="button" id="cancel-add" class="btn-ds-secondary cancel-add">Cancel</button>
                <button type="submit" class="btn-ds-primary">
                    <i class="fas fa-save text-xs mr-1"></i> Submit Request
                </button>
            </div>
        </form>
    </div>
</div>