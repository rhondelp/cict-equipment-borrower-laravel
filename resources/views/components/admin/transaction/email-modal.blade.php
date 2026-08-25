<!-- Email Modal -->
<div id="emailModal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50">
    <div class="modal-fade mx-4 w-full max-w-md rounded-lg bg-white shadow-lg">
        <!-- Header -->
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-base font-semibold text-gray-900">Send Email</h2>
        </div>

        <div class="space-y-4 p-6">
            <!-- Email (disabled) -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Recipient Email</label>
                <input type="email" id="modalEmail" disabled
                    class="w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-500">
            </div>

            <!-- Email Type Select -->
            <div>
                <label for="emailType" class="mb-1 block text-sm font-medium text-gray-700">Email Type</label>
                <select id="emailType"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="template">Use Template</option>
                    <option value="custom">Write Custom Message</option>
                </select>
            </div>

            <!-- Custom Message -->
            <div id="customMessageBox" class="hidden">
                <label for="modalMessage" class="mb-1 block text-sm font-medium text-gray-700">Message</label>
                <textarea id="modalMessage" rows="4"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                    placeholder="Type your message here..."></textarea>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end space-x-3 border-t border-gray-200 pt-4">
                <button id="closeEmailModal"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">
                    Close
                </button>

                <button id="sendEmailConfirm"
                    class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                    Send Email
                </button>
            </div>
        </div>
    </div>
</div>
