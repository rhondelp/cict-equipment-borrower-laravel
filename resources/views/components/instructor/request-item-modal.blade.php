{{-- Ask for equipment.

     The quantity is capped at what is actually on the shelf for the item
     selected: the stepper stops there, the field's max is that number, and
     ItemRequestController::store refuses anything larger. Picking an item with
     nothing left is not possible — those rows are disabled and say why. --}}
<div id="request-modal" data-modal
     class="fixed inset-0 z-modal items-center justify-center hidden p-4 overflow-y-auto bg-neutral-900/50"
     role="dialog" aria-modal="true" aria-labelledby="request-modal-title">
    <div class="w-full max-w-lg my-auto bg-white border border-neutral-200 rounded-xl shadow-pop">
        <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4">
            <div class="min-w-0">
                <h2 id="request-modal-title" class="text-lg font-semibold text-neutral-900">Request equipment</h2>
                <p class="mt-1 text-sm text-neutral-600 text-pretty">
                    An administrator reviews this before anything is set aside for you.
                </p>
            </div>
            <button type="button" data-modal-close="request-modal" aria-label="Close"
                    class="grid w-10 h-10 border rounded-md shrink-0 place-items-center border-neutral-200 text-neutral-600 hover:bg-neutral-50">
                <i class="text-base fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <form id="request-form" method="POST" action="{{ route('borrower.request.store') }}">
            @csrf

            <div class="px-6 pb-5 space-y-4">
                <div>
                    <span class="block text-base font-medium text-neutral-800">What do you need?</span>
                    <div class="flex flex-col gap-2 mt-2 overflow-y-auto max-h-56">
                        @forelse ($equipments as $item)
                            @php $none = $item->available_quantity < 1; @endphp
                            <label class="flex items-center gap-3 rounded-lg border px-3 py-2.5 transition
                                          {{ $none ? 'cursor-not-allowed border-neutral-200 bg-neutral-50' : 'cursor-pointer border-neutral-300 hover:border-primary-300 has-[:checked]:border-primary-400 has-[:checked]:bg-primary-50' }}">
                                <input type="radio" name="equipment_id" value="{{ $item->id }}" required @disabled($none)
                                       data-available="{{ $item->available_quantity }}"
                                       class="w-4 h-4 shrink-0 accent-primary-600 request-equipment">
                                <span class="flex-1 min-w-0 text-base {{ $none ? 'text-neutral-500' : 'text-neutral-900' }}">
                                    {{ $item->equipment_name }}
                                </span>
                                <span class="text-sm shrink-0 {{ $none ? 'text-danger-700' : 'text-neutral-600' }}">
                                    {{ $none ? 'None left' : $item->available_quantity.' of '.$item->quantity.' free' }}
                                </span>
                            </label>
                        @empty
                            <p class="px-3 py-4 text-base text-neutral-600">There is no equipment on the shelf right now.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <label for="request-quantity" class="block text-base font-medium text-neutral-800">
                        How many <span data-request-max class="text-sm font-normal text-neutral-600"></span>
                    </label>
                    <div class="flex items-center gap-2 mt-2">
                        <button type="button" data-request-step="-1" aria-label="Fewer"
                                class="grid w-12 border rounded-md h-12 shrink-0 place-items-center border-neutral-300 text-neutral-700 hover:bg-neutral-50">
                            <i class="fas fa-minus" aria-hidden="true"></i>
                        </button>
                        <input type="number" id="request-quantity" name="quantity" min="1" value="1" required
                               class="h-12 w-full min-w-0 rounded-md border border-neutral-300 px-4 text-center text-base tabular-nums text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                        <button type="button" data-request-step="1" aria-label="More"
                                class="grid w-12 border rounded-md h-12 shrink-0 place-items-center border-neutral-300 text-neutral-700 hover:bg-neutral-50">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="request-remarks" class="block text-base font-medium text-neutral-800">What is it for?</label>
                    <textarea id="request-remarks" name="remarks" rows="3" maxlength="1000"
                              placeholder="e.g. Capstone presentation dry run in AVR 2"
                              class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30"></textarea>
                </div>

                <p data-request-preview class="flex items-center gap-2.5 rounded-lg bg-neutral-50 px-4 py-3 text-sm text-neutral-700">
                    <span data-request-preview-dot class="w-2 h-2 rounded-full shrink-0 bg-neutral-400" aria-hidden="true"></span>
                    <span data-request-preview-text>Pick an item from the list</span>
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <p data-request-hint class="text-sm min-w-0 text-neutral-600"></p>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" data-modal-close="request-modal"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                        Cancel
                    </button>
                    <button type="submit" data-request-submit
                            class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-md bg-primary-600 px-5 py-3 text-base font-semibold text-white hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-primary-600">
                        <i class="text-base fas fa-paper-plane" aria-hidden="true"></i> Send request
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
