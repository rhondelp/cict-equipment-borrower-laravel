{{-- Add / edit an account — one modal for both.

     The role select drops "Admin" on the add form. AuthenticateUser::register
     caps user_type at Instructor/Student and rejects anything else, so the
     option could only ever produce a validation error; an Admin account is a
     deliberate database action with no web path, and the form now says that
     rather than pretending otherwise. --}}
<div id="user-modal" data-modal
     class="fixed inset-0 z-modal items-center justify-center hidden p-4 overflow-y-auto bg-neutral-900/50"
     role="dialog" aria-modal="true" aria-labelledby="user-modal-title">
    <div class="w-full max-w-lg my-auto bg-white border border-neutral-200 rounded-xl shadow-pop">
        <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4">
            <div class="min-w-0">
                <h2 id="user-modal-title" data-user-title class="text-lg font-semibold text-neutral-900">Add account</h2>
                <p class="mt-1 text-sm text-neutral-600">Borrower accounts can request equipment as soon as they exist.</p>
            </div>
            <button type="button" data-modal-close="user-modal" aria-label="Close"
                    class="grid w-10 h-10 border rounded-md shrink-0 place-items-center border-neutral-200 text-neutral-600 hover:bg-neutral-50">
                <i class="text-base fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <form id="user-form" method="POST" action="{{ route('admin.user.register') }}">
            @csrf
            <input type="hidden" name="id" id="user-id">

            <div class="px-6 pb-5 space-y-4">
                <div>
                    <label for="user-name" class="block text-base font-medium text-neutral-800">Full name</label>
                    <input type="text" id="user-name" name="name" required maxlength="255" data-autofocus
                           class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="user-email" class="block text-base font-medium text-neutral-800">Email address</label>
                        <input type="email" id="user-email" name="email" required maxlength="255"
                               class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                    <div>
                        <label for="user-contact" class="flex items-baseline gap-2 text-base font-medium text-neutral-800">
                            Contact <span class="text-sm font-normal text-neutral-600">optional</span>
                        </label>
                        <input type="text" id="user-contact" name="contact_number" maxlength="15"
                               class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                </div>

                <div>
                    <label for="user-type" class="block text-base font-medium text-neutral-800">Role</label>
                    <select id="user-type" name="user_type" required
                            class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                        <option value="" disabled selected>Select a role</option>
                        <option value="Instructor">Instructor</option>
                        <option value="Student">Student</option>
                        <option value="Admin" hidden disabled>Admin</option>
                    </select>
                    {{-- Everyone shares one email domain, so nothing derives a
                         role: changing one on an existing account is a
                         recorded decision. The server requires this reason
                         whenever the role differs from the stored one, so it is
                         not a field anyone can quietly skip. --}}
                    <p class="mt-2 text-sm text-neutral-600" data-role-derived></p>
                    <div class="mt-2 hidden" data-role-override-box>
                        <label for="role-override-reason" class="block text-base font-medium text-neutral-800">
                            Why this role change
                        </label>
                        <input type="text" id="role-override-reason" name="role_override_reason" maxlength="500"
                               placeholder="Confirmed with the dean's office — teaches the lab sections"
                               class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                        <p class="mt-1 text-sm text-neutral-600">Recorded with your name and today&rsquo;s date.</p>
                    </div>
                    @error('role_override_reason')
                        <p class="mt-1 text-sm text-danger-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="user-password" class="flex flex-wrap items-baseline gap-2 text-base font-medium text-neutral-800">
                            Password <span data-password-note class="text-sm font-normal text-neutral-600">at least 4 characters</span>
                        </label>
                        <input type="password" id="user-password" name="password" autocomplete="new-password"
                               class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                    <div>
                        <label for="user-password-confirmation" class="block text-base font-medium text-neutral-800">Confirm password</label>
                        <input type="password" id="user-password-confirmation" name="password_confirmation" autocomplete="new-password"
                               class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <p data-user-hint class="text-sm min-w-0 text-neutral-600"></p>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" data-modal-close="user-modal"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                        Cancel
                    </button>
                    <button type="submit" data-user-submit
                            class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-md bg-primary-600 px-5 py-3 text-base font-semibold text-white hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-primary-600">
                        <i class="text-base fas fa-save" aria-hidden="true"></i>
                        <span data-user-submit-label>Create account</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const role = document.getElementById('user-type');
    const note = document.querySelector('[data-role-derived]');
    const box = document.querySelector('[data-role-override-box]');
    if (!role || !note || !box) return;

    // `data-current` is the stored role, set by the users page when it opens
    // the dialog for an existing account; empty on the add form. Mirrors
    // UserController::update, which requires a reason when the two differ.
    function sync() {
        const current = role.dataset.current || '';
        const changing = current !== '' && role.value !== '' && role.value !== current;

        note.textContent = changing ? 'Changing from ' + current + ' to ' + role.value + '.' : '';
        note.classList.toggle('hidden', !changing);
        note.classList.toggle('text-warning-700', changing);

        box.classList.toggle('hidden', !changing);
        const input = document.getElementById('role-override-reason');
        if (input) {
            input.required = changing;
            if (!changing) input.value = '';
        }
    }

    role.addEventListener('change', sync);
    // The users page fills the form programmatically and then fires this.
    role.addEventListener('role:reset', sync);
    sync();
});
</script>
