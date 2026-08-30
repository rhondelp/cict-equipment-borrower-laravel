<!-- Email Modal — z-[60] -->
<div id="emailModal" class="fixed inset-0 z-[60] flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg">

        <h2 class="mb-4 text-xl font-semibold">Send Email</h2>

        <!-- Email (disabled) -->
        <label class="block mb-1 text-sm font-medium">Recipient Email</label>
        <input type="email" id="modalEmail" disabled
            class="w-full px-3 py-2 mb-4 bg-gray-100 border rounded cursor-not-allowed">

        <!-- Email Type Select -->
        <label class="block mb-1 text-sm font-medium">Email Type</label>
        <select id="emailType" class="w-full px-3 py-2 mb-4 border rounded focus:ring-blue-500">
            <option value="template">Use Template</option>
            <option value="custom">Write Custom Message</option>
        </select>

        <!-- Custom Message -->
        <div id="customMessageBox" class="hidden">
            <label class="block mb-1 text-sm font-medium">Message</label>
            <textarea id="modalMessage" rows="4"
                class="w-full px-3 py-2 border rounded focus:ring-blue-500 focus:border-blue-500"
                placeholder="Type your message here..."></textarea>
        </div>

        <!-- Buttons -->
        <div class="flex justify-end mt-4 space-x-2">
            <button id="closeEmailModal" class="px-4 py-2 text-white bg-gray-400 rounded hover:bg-gray-500">
                Close
            </button>

            <button id="sendEmailConfirm" class="px-4 py-2 text-white bg-green-600 rounded hover:bg-green-700">
                Send Email
            </button>
        </div>
    </div>
</div>
