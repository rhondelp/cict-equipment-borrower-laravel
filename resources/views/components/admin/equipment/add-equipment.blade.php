<!-- Add Equipment Modal — flat light theme -->
<div id="add-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-neutral-900/50 p-4">
    <div class="w-full max-w-md bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-neutral-900">
                <i class="text-base text-primary-600 fas fa-tools"></i> Add Equipment
            </h3>
            <button type="button" class="w-10 h-10 grid place-items-center rounded-md text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900 cancel-add" aria-label="Close">
                <i class="text-base fas fa-times"></i>
            </button>
        </div>

        <form id="add-form" action="{{ route('admin.equipment.store') }}" method="POST" class="flex flex-col flex-1 min-h-0">
            @csrf

            <div class="px-6 py-5 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label for="add-name" class="block text-base font-medium text-neutral-800">Equipment Name</label>
                    <input type="text" id="add-name" name="equipment_name" required placeholder="Enter equipment name"
                           class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                </div>

                <div>
                    <label for="add-description" class="block text-base font-medium text-neutral-800">Description</label>
                    <textarea id="add-description" name="description" rows="3" placeholder="Describe the equipment..."
                              class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="add-quantity" class="block text-base font-medium text-neutral-800">Total Quantity</label>
                        <input type="number" id="add-quantity" name="quantity" required min="1" placeholder="0"
                               class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none tabular-nums">
                    </div>
                    <div>
                        <label for="add-available" class="block text-base font-medium text-neutral-800">Available Quantity</label>
                        <input type="number" id="add-available" name="available_quantity" required min="0" placeholder="0"
                               class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none tabular-nums">
                    </div>
                </div>

                <div>
                    <label for="add-status" class="block text-base font-medium text-neutral-800">Status</label>
                    <select id="add-status" name="status"
                            class="mt-2 w-full px-4 py-3 border border-neutral-300 rounded-md text-base text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30 focus:outline-none">
                        <option value="Available">Available</option>
                        <option value="Unavailable">Unavailable</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" id="cancel-add" class="inline-flex items-center justify-center min-h-[44px] px-5 py-3 text-base font-semibold rounded-md bg-white text-neutral-700 border border-neutral-300 hover:bg-neutral-50 cancel-add">Cancel</button>
                <button type="submit" class="inline-flex items-center justify-center gap-2 min-h-[44px] px-5 py-3 text-base font-semibold rounded-md bg-primary-600 text-white hover:bg-primary-700">
                    <i class="text-base fas fa-plus"></i> Add Equipment
                </button>
            </div>
        </form>
    </div>
</div>
