{{-- Record what was done about an incident. This is the one field written to a
     return log after the fact, and it is an outcome attached to the record
     rather than a change to what the record says. --}}
<div id="resolve-modal" data-modal
     class="fixed inset-0 z-modal items-center justify-center hidden p-4 overflow-y-auto bg-neutral-900/50"
     role="dialog" aria-modal="true" aria-labelledby="resolve-modal-title">
    <div class="w-full max-w-md my-auto bg-white border border-neutral-200 rounded-xl shadow-pop">
        <form method="POST" data-resolve-form action="{{ route('admin.logs.resolve', ['id' => 0]) }}">
            @csrf
            <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4">
                <div class="min-w-0">
                    <h2 id="resolve-modal-title" class="text-lg font-semibold text-neutral-900">Record the outcome</h2>
                    {{-- Read from the row that opened this, with a fallback
                         sentence: a dialog opened out of a filtered list has no
                         "current row" to derive from. --}}
                    <p class="mt-1 text-sm text-neutral-600 text-pretty" data-resolve-summary>
                        What was done about this return.
                    </p>
                </div>
                <button type="button" data-modal-close="resolve-modal" aria-label="Close"
                        class="grid w-10 h-10 border rounded-md shrink-0 place-items-center border-neutral-200 text-neutral-600 hover:bg-neutral-50">
                    <i class="text-base fas fa-times" aria-hidden="true"></i>
                </button>
            </div>

            <div class="px-6 pb-5">
                <label for="resolution" class="block text-base font-medium text-neutral-800">What happened</label>
                <p class="mt-1 text-sm text-neutral-600">Repaired, replaced, charged, written off — whatever the office actually did.</p>
                <textarea name="resolution" id="resolution" rows="4" required minlength="5" maxlength="1000"
                          placeholder="Replaced the lens cap from spares; no charge."
                          class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30"></textarea>
                @error('resolution')
                    <p class="mt-1 text-sm text-danger-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" data-modal-close="resolve-modal"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                    Cancel
                </button>
                <button type="submit"
                        class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-md bg-primary-600 px-5 py-3 text-base font-semibold text-white hover:bg-primary-700">
                    <i class="text-base fas fa-clipboard-check" aria-hidden="true"></i> Record outcome
                </button>
            </div>
        </form>
    </div>
</div>
