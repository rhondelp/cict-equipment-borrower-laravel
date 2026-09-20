{{-- Check a loan back in.

     This is what replaced the status dropdown in the table. An admin no longer
     picks "Returned" from a list of three words; they record the event — what
     came back and in what condition — and the status follows from that.

     Condition is a set of chips rather than a select because there are three
     options and two of them change what the form asks for next: anything other
     than Good needs a description, and the submit says so until it has one. --}}
<div id="checkin-modal" data-modal
     class="fixed inset-0 z-modal items-center justify-center hidden p-4 overflow-y-auto bg-neutral-900/50"
     role="dialog" aria-modal="true" aria-labelledby="checkin-title">
    <div class="w-full max-w-md my-auto bg-white border border-neutral-200 rounded-xl shadow-pop">
        <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4">
            <div class="min-w-0">
                <h2 id="checkin-title" class="text-lg font-semibold text-neutral-900">Check in equipment</h2>
                <p data-checkin-summary class="mt-1 text-sm font-medium text-neutral-800"></p>
                <p data-checkin-timing class="text-sm text-neutral-600"></p>
            </div>
            <button type="button" data-modal-close="checkin-modal" aria-label="Close"
                    class="grid w-10 h-10 border rounded-md shrink-0 place-items-center border-neutral-200 text-neutral-600 hover:bg-neutral-50">
                <i class="text-base fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <form id="checkin-form" method="POST" action="{{ route('admin.transaction.checkin') }}">
            @csrf
            <input type="hidden" name="id" id="checkin-id">
            <input type="hidden" name="condition" id="checkin-condition" value="Good">

            <div class="px-6 pb-5 space-y-4">
                <div>
                    <span class="block text-base font-medium text-neutral-800">Condition</span>
                    <div class="flex flex-wrap gap-2 mt-2" role="group" aria-label="Condition on return">
                        @foreach (['Good', 'Damaged', 'Missing parts'] as $condition)
                            <button type="button" data-condition="{{ $condition }}"
                                    aria-pressed="{{ $condition === 'Good' ? 'true' : 'false' }}"
                                    class="inline-flex min-h-[40px] items-center rounded-full border px-4 py-2 text-sm font-semibold transition
                                           {{ $condition === 'Good' ? 'border-primary-300 bg-primary-50 text-primary-700' : 'border-neutral-300 bg-white text-neutral-700' }}">
                                {{ $condition }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label for="checkin-remarks" class="flex items-baseline gap-2 text-base font-medium text-neutral-800">
                        Remarks <span data-checkin-remarks-note class="text-sm font-normal text-neutral-600">optional</span>
                    </label>
                    <textarea id="checkin-remarks" name="remarks" rows="3" maxlength="255"
                              placeholder="What came back, and in what state"
                              class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30"></textarea>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <p data-checkin-hint class="text-sm min-w-0 text-neutral-600">Writes a dated entry in Return Logs</p>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" data-modal-close="checkin-modal"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                        Cancel
                    </button>
                    <button type="submit" data-checkin-submit
                            class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-md bg-success-700 px-5 py-3 text-base font-semibold text-white hover:bg-success-800 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-success-700">
                        <i class="text-base fas fa-check" aria-hidden="true"></i> Confirm return
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
