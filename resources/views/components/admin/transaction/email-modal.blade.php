<!-- Email Modal — z-[60] -->
<div id="emailModal" class="fixed inset-0 z-[60] flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="modal-card max-w-md w-full mx-4 animate-fade-in">
        <!-- Header -->
        <div class="modal-header">
            <h3 class="flex items-center gap-2">
                <i class="text-primary-300 fas fa-paper-plane text-sm"></i> Send Email Notification
            </h3>
            <button type="button" class="modal-close" id="closeEmailModal-x" aria-label="Close" onclick="document.getElementById('emailModal').classList.add('hidden')">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <div class="modal-body space-y-4">
            <!-- Email (disabled) -->
            <div class="ds-field">
                <label for="modalEmail">Recipient Email</label>
                <input type="email" id="modalEmail" disabled class="opacity-60 cursor-not-allowed">
            </div>

            <!-- Email Type Select -->
            <div class="ds-field">
                <label for="emailType">Email Type</label>
                <select id="emailType">
                    <option value="template">Use Template</option>
                    <option value="custom">Write Custom Message</option>
                </select>
            </div>

            <!-- Custom Message -->
            <div id="customMessageBox" class="ds-field hidden animate-fade-in">
                <label for="modalMessage">Message</label>
                <textarea id="modalMessage" rows="4" placeholder="Type your message here..."></textarea>
            </div>
        </div>

        <!-- Buttons -->
        <div class="modal-footer">
            <button type="button" id="closeEmailModal" class="btn-ds-secondary">Close</button>
            <button type="button" id="sendEmailConfirm" class="btn-ds-success">
                <i class="fas fa-envelope mr-1 text-xs"></i> Send Email
            </button>
        </div>
    </div>
</div>
