{{-- Add / edit equipment — one modal for both, because they ask for the same
     three things.

     Two fields that used to be here are gone:

       Available quantity  was a number an admin typed in that was supposed to
                           agree with the loans. Now it is total − units out,
                           computed on save.
       Status              was a dropdown that could say "Available" while the
                           shelf was empty. Now it follows from the stock.

     What is left validates against the loans as you type: the total cannot go
     below the units currently with borrowers, and the submit says so rather
     than failing after the round trip. --}}
<div id="equipment-modal" data-modal
     class="fixed inset-0 z-modal items-center justify-center hidden p-4 overflow-y-auto bg-neutral-900/50"
     role="dialog" aria-modal="true" aria-labelledby="equipment-modal-title">
    <div class="w-full max-w-lg my-auto bg-white border border-neutral-200 rounded-xl shadow-pop">
        <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4">
            <div class="min-w-0">
                <h2 id="equipment-modal-title" data-equipment-title class="text-lg font-semibold text-neutral-900">Add equipment</h2>
                <p data-equipment-subtitle class="mt-1 text-sm text-neutral-600 text-pretty">
                    New items start fully available. Availability updates itself as things are lent out.
                </p>
            </div>
            <button type="button" data-modal-close="equipment-modal" aria-label="Close"
                    class="grid w-10 h-10 border rounded-md shrink-0 place-items-center border-neutral-200 text-neutral-600 hover:bg-neutral-50">
                <i class="text-base fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <form id="equipment-form" method="POST" action="{{ route('admin.equipment.store') }}">
            @csrf
            <input type="hidden" name="id" id="equipment-id">

            <div class="px-6 pb-5 space-y-4">
                <div>
                    <label for="equipment-name" class="block text-base font-medium text-neutral-800">Equipment name</label>
                    <input type="text" id="equipment-name" name="equipment_name" required maxlength="255" data-autofocus
                           placeholder="e.g. Projector (Epson)"
                           class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    <p data-error-for="equipment-name" class="mt-2 text-sm font-medium text-danger-700" hidden></p>
                </div>

                <div>
                    <label for="equipment-description" class="flex items-baseline gap-2 text-base font-medium text-neutral-800">
                        Description <span class="text-sm font-normal text-neutral-600">optional</span>
                    </label>
                    <textarea id="equipment-description" name="description" rows="2" maxlength="500"
                              placeholder="What it is and what it is used for"
                              class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30"></textarea>
                </div>

                <div>
                    <label for="equipment-quantity" data-quantity-label class="block text-base font-medium text-neutral-800">Units to add</label>
                    <div class="flex items-center gap-2 mt-2">
                        <button type="button" data-step="-1" aria-label="Fewer units"
                                class="grid w-12 border rounded-md h-12 shrink-0 place-items-center border-neutral-300 text-neutral-700 hover:bg-neutral-50">
                            <i class="fas fa-minus" aria-hidden="true"></i>
                        </button>
                        <input type="number" id="equipment-quantity" name="quantity" min="1" value="1" required
                               class="h-12 w-full min-w-0 rounded-md border border-neutral-300 px-4 text-center text-base tabular-nums text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                        <button type="button" data-step="1" aria-label="More units"
                                class="grid w-12 border rounded-md h-12 shrink-0 place-items-center border-neutral-300 text-neutral-700 hover:bg-neutral-50">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                {{-- What the row will read after saving, written out in the same
                     words the table uses. It turns red the moment the total
                     drops below the units that are physically elsewhere. --}}
                <p data-equipment-preview
                   class="flex items-center gap-2.5 rounded-lg bg-neutral-50 px-4 py-3 text-sm text-neutral-700">
                    <span data-equipment-preview-dot class="w-2 h-2 rounded-full shrink-0 bg-success-600" aria-hidden="true"></span>
                    <span data-equipment-preview-text>Will be listed as 1 of 1 available</span>
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <p data-equipment-hint class="text-sm min-w-0 text-neutral-600"></p>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" data-modal-close="equipment-modal"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                        Cancel
                    </button>
                    <button type="submit" data-equipment-submit
                            class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-md bg-primary-600 px-5 py-3 text-base font-semibold text-white hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-primary-600">
                        <i class="text-base fas fa-save" aria-hidden="true"></i>
                        <span data-equipment-submit-label>Add to inventory</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
