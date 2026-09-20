{{-- Change a request that has not been decided yet.

     The equipment is fixed — changing what you asked for is a different
     request, not an edit of this one — so only the quantity and the reason are
     editable, and the quantity is capped at what is on the shelf right now.

     Only pending requests reach this form; ItemRequestController::update
     refuses to touch one that has already been approved or declined. --}}
<div id="edit-request-modal" data-modal
     class="fixed inset-0 z-modal items-center justify-center hidden p-4 overflow-y-auto bg-neutral-900/50"
     role="dialog" aria-modal="true" aria-labelledby="edit-request-title">
    <div class="w-full max-w-lg my-auto bg-white border border-neutral-200 rounded-xl shadow-pop">
        <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4">
            <div class="min-w-0">
                <h2 id="edit-request-title" class="text-lg font-semibold text-neutral-900">Change your request</h2>
                <p class="mt-1 text-sm text-neutral-600">It is still waiting for a decision, so you can still edit it.</p>
            </div>
            <button type="button" data-modal-close="edit-request-modal" aria-label="Close"
                    class="grid w-10 h-10 border rounded-md shrink-0 place-items-center border-neutral-200 text-neutral-600 hover:bg-neutral-50">
                <i class="text-base fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <form id="edit-request-form" method="POST" action="{{ route('borrower.request.update') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="id" id="edit-request-id">

            <div class="px-6 pb-5 space-y-4">
                <div>
                    <span class="block text-base font-medium text-neutral-800">Equipment</span>
                    <p id="edit-request-equipment"
                       class="px-4 py-3 mt-2 text-base font-medium border rounded-md bg-neutral-50 border-neutral-200 text-neutral-900"></p>
                </div>

                <div>
                    <label for="edit-request-quantity" class="block text-base font-medium text-neutral-800">
                        How many <span data-edit-request-max class="text-sm font-normal text-neutral-600"></span>
                    </label>
                    <input type="number" name="quantity" id="edit-request-quantity" min="1" required
                           class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base tabular-nums text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                </div>

                <div>
                    <label for="edit-request-remarks" class="block text-base font-medium text-neutral-800">What is it for?</label>
                    <textarea name="remarks" id="edit-request-remarks" rows="3" maxlength="1000"
                              class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30"></textarea>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <p data-edit-request-hint class="text-sm min-w-0 text-neutral-600"></p>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" data-modal-close="edit-request-modal"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                        Cancel
                    </button>
                    <button type="submit" data-edit-request-submit
                            class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-md bg-primary-600 px-5 py-3 text-base font-semibold text-white hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-primary-600">
                        <i class="text-base fas fa-save" aria-hidden="true"></i> Save changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
