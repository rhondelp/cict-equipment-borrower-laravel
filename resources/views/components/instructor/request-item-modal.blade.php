<!-- Add Request Modal — flat light theme -->
<div id="add-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-neutral-900/50 p-4">
    <div class="w-full max-w-lg bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-neutral-900">
                <i class="text-base text-primary-600 fas fa-plus"></i> New Item Request
            </h3>
            <button type="button" class="w-10 h-10 grid place-items-center rounded-md text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 cancel-add" aria-label="Close">
                <i class="text-base fas fa-times"></i>
            </button>
        </div>

        <form id="add-form" method="POST" action="{{ route('borrower.request.store') }}" class="flex flex-col flex-1">
            @csrf

            <div class="px-6 py-5 space-y-4">
                <div>
                    <label for="add-equipment" class="block text-base font-medium text-neutral-800">Select Equipment</label>
                    <select id="add-equipment" name="equipment_id" required
                            class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none min-h-[120px]">
                        <option value="" disabled selected>Select equipment</option>
                        @foreach ($equipments as $equipment)
                            <option value="{{ $equipment->id }}">{{ $equipment->equipment_name }} | Available: {{ $equipment->available_quantity }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="add-quantity" class="block text-base font-medium text-neutral-800">Quantity</label>
                    <input type="number" id="add-quantity" name="quantity" min="1" required
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none tabular-nums">
                </div>

                <div>
                    <label for="add-remarks" class="block text-base font-medium text-neutral-800">Remarks (optional)</label>
                    <textarea id="add-remarks" name="remarks" rows="3"
                              class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none min-h-[60px]"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" id="cancel-add" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 text-base font-semibold rounded-md bg-white text-neutral-700 border border-neutral-300 hover:bg-neutral-50 cancel-add">Cancel</button>
                <button type="submit" class="inline-flex items-center justify-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold rounded-md bg-primary-600 text-white hover:bg-primary-700">
                    <i class="text-base fas fa-save"></i> Submit Request
                </button>
            </div>
        </form>
    </div>
</div>
