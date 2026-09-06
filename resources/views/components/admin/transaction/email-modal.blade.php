<!-- Email Modal — flat light theme -->
<div id="emailModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-neutral-900/50 p-4">
    <div class="w-full max-w-md bg-white border border-neutral-200 rounded-xl shadow-flat flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
            <h3 class="flex items-center gap-2 text-base font-semibold text-neutral-900">
                <i class="text-sm text-primary-600 fas fa-paper-plane"></i> Send Email Notification
            </h3>
            <button type="button" class="w-8 h-8 grid place-items-center rounded-md text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" id="closeEmailModal-x" aria-label="Close">
                <i class="text-xs fas fa-times"></i>
            </button>
        </div>

        <div class="px-6 py-5 space-y-4 overflow-y-auto flex-1">
            <div>
                <label for="modalEmail" class="block text-sm font-medium text-neutral-700">Recipient Email</label>
                <input type="email" id="modalEmail" disabled
                       class="mt-1.5 w-full px-3 py-2 border border-neutral-200 rounded-md text-sm text-neutral-500 bg-neutral-50 cursor-not-allowed">
            </div>

            <div>
                <label for="emailType" class="block text-sm font-medium text-neutral-700">Email Type</label>
                <select id="emailType"
                        class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none">
                    <option value="template">Use Template</option>
                    <option value="custom">Write Custom Message</option>
                </select>
            </div>

            <div id="customMessageBox" class="hidden">
                <label for="modalMessage" class="block text-sm font-medium text-neutral-700">Message</label>
                <textarea id="modalMessage" rows="4" placeholder="Type your message here..."
                          class="mt-1.5 w-full px-3 py-2 border border-neutral-300 rounded-md text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 focus:outline-none"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
            <button type="button" id="closeEmailModal" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md bg-white text-neutral-700 border border-neutral-300 hover:bg-neutral-50">Close</button>
            <button type="button" id="sendEmailConfirm" class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-primary-600 text-white hover:bg-primary-700">
                <i class="text-xs fas fa-envelope"></i> Send Email
            </button>
        </div>
    </div>
</div>
