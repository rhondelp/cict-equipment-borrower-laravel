{{-- Log maintenance or a repair against one item.

     Record-only: it writes one activity log entry (maintenance_logged or
     repair_logged) and changes nothing else, so there is no stock preview
     here. The header sits outside the form, like every modal in the app; the
     page script fills the item's name into it and points the form at the
     item's route before opening.

     Text runs a size larger than the other admin modals: this is filled in by
     whoever did the work, often standing at the shelf. --}}
<div id="maintenance-modal" data-modal
     class="fixed inset-0 z-modal items-center justify-center hidden p-4 overflow-y-auto bg-neutral-900/50"
     role="dialog" aria-modal="true" aria-labelledby="maintenance-modal-title">
    <div class="w-full max-w-lg my-auto bg-white border border-neutral-200 rounded-xl shadow-pop">
        <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4">
            <div class="min-w-0">
                <h2 id="maintenance-modal-title" class="text-xl font-semibold text-neutral-900">Log maintenance or repair</h2>
                <p class="mt-1 text-base text-neutral-700 text-pretty">
                    For <span data-maintenance-item class="font-semibold text-neutral-900">this item</span>.
                    It goes in the activity log; stock and availability do not change.
                </p>
            </div>
            <button type="button" data-modal-close="maintenance-modal" aria-label="Close"
                    class="grid w-11 h-11 border rounded-md shrink-0 place-items-center border-neutral-200 text-neutral-600 hover:bg-neutral-50">
                <i class="text-base fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <form id="maintenance-form" method="POST" action="">
            @csrf

            <div class="px-6 pb-5 space-y-5">
                <fieldset>
                    <legend class="block text-base font-medium text-neutral-800">What kind of work</legend>
                    <div class="grid gap-2 mt-2 sm:grid-cols-2">
                        @foreach([
                            'maintenance' => ['fa-screwdriver-wrench', 'Maintenance', 'Routine care: cleaning, updates, a check-up'],
                            'repair' => ['fa-hammer', 'Repair', 'Something was broken and has been fixed'],
                        ] as $kind => [$icon, $label, $hint])
                            <label class="flex items-start gap-3 px-4 py-3 border rounded-lg cursor-pointer border-neutral-300 hover:border-primary-300 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50">
                                <input type="radio" name="kind" value="{{ $kind }}" data-maintenance-kind required
                                       aria-describedby="maintenance-kind-hint-{{ $kind }}"
                                       @checked($kind === 'maintenance')
                                       class="w-5 h-5 mt-0.5 shrink-0 accent-primary-600">
                                <span class="min-w-0">
                                    <span class="flex items-center gap-2 text-base font-semibold text-neutral-900">
                                        <i class="w-4 text-sm text-center fas {{ $icon }} text-neutral-600" aria-hidden="true"></i>
                                        {{ $label }}
                                    </span>
                                    <span id="maintenance-kind-hint-{{ $kind }}" class="block mt-0.5 text-base text-neutral-700 text-pretty">{{ $hint }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div>
                    <label for="maintenance-summary" class="block text-base font-medium text-neutral-800">What was done</label>
                    <input type="text" id="maintenance-summary" name="summary" required maxlength="200" data-autofocus
                           placeholder="e.g. Replaced the lamp and cleaned the filter"
                           class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    <p class="mt-2 text-base text-neutral-600">One line, up to 200 characters. This is what the report shows.</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="maintenance-date" class="block text-base font-medium text-neutral-800">Date done</label>
                        {{-- No future dates: the server refuses them too. --}}
                        <input type="date" id="maintenance-date" name="performed_on" required
                               max="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}"
                               class="mt-2 w-full min-h-[48px] rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                    <div>
                        <label for="maintenance-cost" class="flex items-baseline gap-2 text-base font-medium text-neutral-800">
                            Cost (₱) <span class="text-base font-normal text-neutral-600">optional</span>
                        </label>
                        <input type="number" id="maintenance-cost" name="cost" min="0" step="0.01" inputmode="decimal"
                               placeholder="0.00"
                               class="mt-2 w-full min-h-[48px] rounded-md border border-neutral-300 px-4 py-3 text-base tabular-nums text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                </div>

                <div>
                    <label for="maintenance-notes" class="flex items-baseline gap-2 text-base font-medium text-neutral-800">
                        Notes <span class="text-base font-normal text-neutral-600">optional</span>
                    </label>
                    <textarea id="maintenance-notes" name="notes" rows="3" maxlength="1000"
                              placeholder="Parts used, who did it, anything to watch for next time"
                              class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30"></textarea>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <button type="button" data-modal-close="maintenance-modal"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                    Cancel
                </button>
                <button type="submit"
                        class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-md bg-primary-600 px-5 py-3 text-base font-semibold text-white hover:bg-primary-700">
                    <i class="text-base fas fa-save" aria-hidden="true"></i>
                    Save record
                </button>
            </div>
        </form>
    </div>
</div>
