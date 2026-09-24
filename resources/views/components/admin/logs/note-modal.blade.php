{{-- A correction. The entry itself is never edited — this appends a note with
     its own author and timestamp, so the original reading survives beside it. --}}
<div id="note-modal" data-modal
     class="fixed inset-0 z-modal items-center justify-center hidden p-4 overflow-y-auto bg-neutral-900/50"
     role="dialog" aria-modal="true" aria-labelledby="note-modal-title">
    <div class="w-full max-w-md my-auto bg-white border border-neutral-200 rounded-xl shadow-pop">
        <form method="POST" data-note-form action="{{ route('admin.logs.note', ['id' => 0]) }}">
            @csrf
            <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4">
                <div class="min-w-0">
                    <h2 id="note-modal-title" class="text-lg font-semibold text-neutral-900">Add a correction</h2>
                    <p class="mt-1 text-sm text-neutral-600 text-pretty" data-note-summary>
                        The original entry is not changed.
                    </p>
                </div>
                <button type="button" data-modal-close="note-modal" aria-label="Close"
                        class="grid w-10 h-10 border rounded-md shrink-0 place-items-center border-neutral-200 text-neutral-600 hover:bg-neutral-50">
                    <i class="text-base fas fa-times" aria-hidden="true"></i>
                </button>
            </div>

            <div class="px-6 pb-5">
                <label for="note-body" class="block text-base font-medium text-neutral-800">Correction</label>
                <p class="mt-1 text-sm text-neutral-600">
                    This is added to the entry with your name and today&rsquo;s date. Nothing already recorded is overwritten.
                </p>
                <textarea name="body" id="note-body" rows="4" required minlength="3" maxlength="1000"
                          placeholder="The scratch was already present at handover — noted on the borrow slip."
                          class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30"></textarea>
                @error('body')
                    <p class="mt-1 text-sm text-danger-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" data-modal-close="note-modal"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                    Cancel
                </button>
                <button type="submit"
                        class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-md bg-primary-600 px-5 py-3 text-base font-semibold text-white hover:bg-primary-700">
                    <i class="text-base fas fa-plus" aria-hidden="true"></i> Append note
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Both dialogs point their form at the row that opened them. The action is
    // rebuilt from a template URL rather than assembled by hand, so a route
    // change cannot silently break the post target.
    const RESOLVE_URL = @json(route('admin.logs.resolve', ['id' => 0]));
    const NOTE_URL = @json(route('admin.logs.note', ['id' => 0]));

    function wire(triggerAttr, modalId, formAttr, summaryAttr, template) {
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[' + triggerAttr + ']');
            if (!trigger) return;

            const form = document.querySelector('[' + formAttr + ']');
            const summary = document.querySelector('[' + summaryAttr + ']');
            if (!form || !trigger.dataset.id) return;

            form.action = template.replace(/\/0$/, '/' + trigger.dataset.id)
                                  .replace(/\/0\//, '/' + trigger.dataset.id + '/');
            // Falls back to the dialog's own sentence rather than to
            // `undefined` when a row does not carry a summary.
            if (summary && trigger.dataset.summary) summary.textContent = trigger.dataset.summary;

            window.appUI.openModal(modalId);
        });
    }

    wire('data-resolve-trigger', 'resolve-modal', 'data-resolve-form', 'data-resolve-summary', RESOLVE_URL);
    wire('data-note-trigger', 'note-modal', 'data-note-form', 'data-note-summary', NOTE_URL);
});
</script>
