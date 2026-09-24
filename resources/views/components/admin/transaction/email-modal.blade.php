{{-- Nudge a borrower about a loan. The template body is composed server-side
     from the loan itself (see BorrowTransactionController::reminderBody), so an
     overdue loan gets the overdue wording without anyone choosing it. --}}
<div id="emailModal" data-modal
     class="fixed inset-0 z-modal items-center justify-center hidden p-4 overflow-y-auto bg-neutral-900/50"
     role="dialog" aria-modal="true" aria-labelledby="email-modal-title">
    <div class="w-full max-w-md my-auto bg-white border border-neutral-200 rounded-xl shadow-pop">
        <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4">
            <div class="min-w-0">
                <h2 id="email-modal-title" class="text-lg font-semibold text-neutral-900">Email the borrower</h2>
                {{-- Filled from the row's own data attributes, never from
                     whatever the list happens to be showing: a dialog opened
                     out of a filtered list has no "current row" to derive from,
                     and that is how these end up reading "undefined". --}}
                <p class="mt-1 text-sm text-neutral-600" data-email-summary>Sent from the equipment office address.</p>
                <p class="mt-1 text-sm text-neutral-500" data-email-history hidden></p>
            </div>
            <button type="button" data-modal-close="emailModal" aria-label="Close"
                    class="grid w-10 h-10 border rounded-md shrink-0 place-items-center border-neutral-200 text-neutral-600 hover:bg-neutral-50">
                <i class="text-base fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <div class="px-6 pb-5 space-y-4">
            <div>
                <label for="modalEmail" class="block text-base font-medium text-neutral-800">To</label>
                <input type="email" id="modalEmail" disabled
                       class="w-full px-4 py-3 mt-2 text-base border rounded-md cursor-not-allowed border-neutral-200 bg-neutral-50 text-neutral-700">
            </div>

            <div>
                <label for="emailType" class="block text-base font-medium text-neutral-800">Message</label>
                <select id="emailType"
                        class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    <option value="template">Return reminder (written for this loan)</option>
                    <option value="custom">Write my own</option>
                </select>
            </div>

            <div id="customMessageBox" class="hidden">
                <label for="modalMessage" class="block text-base font-medium text-neutral-800">Your message</label>
                <textarea id="modalMessage" rows="4" placeholder="Type your message here..."
                          class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
            <button type="button" data-modal-close="emailModal"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                Cancel
            </button>
            <button type="button" id="sendEmailConfirm"
                    class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-md bg-primary-600 px-5 py-3 text-base font-semibold text-white hover:bg-primary-700">
                <i class="text-base fas fa-paper-plane" aria-hidden="true"></i> Send email
            </button>
        </div>
    </div>
</div>
