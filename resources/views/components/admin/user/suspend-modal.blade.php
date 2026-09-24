{{-- Suspend borrowing. Not the same thing as deactivating: the account keeps
     working and stops being able to request equipment, which is the sanction
     the terms of service actually describe. The reason is required because the
     borrower is shown it, and because it is the only record of the decision. --}}
<div id="suspend-modal" data-modal
     class="fixed inset-0 z-modal items-center justify-center hidden p-4 overflow-y-auto bg-neutral-900/50"
     role="dialog" aria-modal="true" aria-labelledby="suspend-modal-title">
    <div class="w-full max-w-md my-auto bg-white border border-neutral-200 rounded-xl shadow-pop">
        <form method="POST" data-suspend-form action="{{ route('admin.users.suspend', ['id' => 0]) }}">
            @csrf
            <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4">
                <div class="min-w-0">
                    <h2 id="suspend-modal-title" class="text-lg font-semibold text-neutral-900">Suspend borrowing</h2>
                    {{-- Read from the row that opened this, with a fallback
                         sentence rather than `undefined`. --}}
                    <p class="mt-1 text-sm text-neutral-600 text-pretty" data-suspend-summary>
                        This account keeps working and stops being able to borrow.
                    </p>
                </div>
                <button type="button" data-modal-close="suspend-modal" aria-label="Close"
                        class="grid w-10 h-10 border rounded-md shrink-0 place-items-center border-neutral-200 text-neutral-600 hover:bg-neutral-50">
                    <i class="text-base fas fa-times" aria-hidden="true"></i>
                </button>
            </div>

            <div class="px-6 pb-5 space-y-3">
                <div class="flex items-start gap-2.5 rounded-lg bg-neutral-50 px-3 py-2.5 text-sm text-neutral-700 text-pretty">
                    <i class="fa-solid fa-circle-info mt-0.5 text-neutral-500" aria-hidden="true"></i>
                    <span>
                        They can still sign in, see their dashboard and return what they are holding.
                        Only new requests are blocked. To stop sign-in entirely, use Remove &rarr; Deactivate instead.
                    </span>
                </div>

                <div>
                    <label for="suspend-reason" class="block text-base font-medium text-neutral-800">Why</label>
                    <p class="mt-1 text-sm text-neutral-600">The borrower is shown this, so write it to them.</p>
                    <textarea name="reason" id="suspend-reason" rows="3" required minlength="5" maxlength="500"
                              placeholder="Two items from last term never came back."
                              class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30"></textarea>
                    @error('reason')
                        <p class="mt-1 text-sm text-danger-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" data-modal-close="suspend-modal"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                    Cancel
                </button>
                <button type="submit"
                        class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-md bg-warning-600 px-5 py-3 text-base font-semibold text-white hover:bg-warning-700">
                    <i class="text-base fas fa-ban" aria-hidden="true"></i> Suspend borrowing
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const SUSPEND_URL = @json(route('admin.users.suspend', ['id' => 0]));

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-suspend-trigger]');
        if (!trigger || !trigger.dataset.id) return;

        const form = document.querySelector('[data-suspend-form]');
        const summary = document.querySelector('[data-suspend-summary]');
        if (!form) return;

        form.action = SUSPEND_URL.replace(/\/0\//, '/' + trigger.dataset.id + '/');
        if (summary && trigger.dataset.summary) summary.textContent = trigger.dataset.summary;

        window.appUI.openModal('suspend-modal');
    });
});
</script>
