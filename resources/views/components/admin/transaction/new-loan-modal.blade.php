{{-- Record a new loan.

     The status dropdown that used to sit at the bottom of this form is gone.
     This form does one thing — hand equipment over — so the loan is created
     out; whether it later reads Out or Overdue is a question the due date
     answers, every time the row renders.

     Quantities are capped at what is actually on the shelf: the stepper will
     not go past it, the field's max is the same number, and
     BorrowTransactionController::store re-checks under a row lock. --}}
<div id="new-loan-modal" data-modal
     class="fixed inset-0 z-modal items-center justify-center hidden p-4 overflow-y-auto bg-neutral-900/50"
     role="dialog" aria-modal="true" aria-labelledby="new-loan-title">
    <div class="w-full max-w-xl my-auto bg-white border border-neutral-200 rounded-xl shadow-pop">
        <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4">
            <div class="min-w-0">
                <h2 id="new-loan-title" class="text-lg font-semibold text-neutral-900">New loan</h2>
                <p class="mt-1 text-sm text-neutral-600 text-pretty">
                    Units come off the shelf the moment you save.
                </p>
            </div>
            <button type="button" data-modal-close="new-loan-modal" aria-label="Close"
                    class="grid w-10 h-10 border rounded-md shrink-0 place-items-center border-neutral-200 text-neutral-600 hover:bg-neutral-50">
                <i class="text-base fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <form id="new-loan-form" method="POST" action="{{ route('admin.transaction.store') }}">
            @csrf

            <div class="px-6 pb-5 space-y-4">
                <div>
                    <label for="loan-user" class="block text-base font-medium text-neutral-800">Borrower</label>
                    <select id="loan-user" name="user_id" required data-autofocus
                            class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                        <option value="" disabled selected>Select who is borrowing</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} · {{ $user->user_type }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="loan-equipment-search" class="block text-base font-medium text-neutral-800">Equipment</label>

                    <div class="relative mt-2">
                        <i class="absolute text-sm -translate-y-1/2 pointer-events-none fas fa-search left-4 top-1/2 text-neutral-500" aria-hidden="true"></i>
                        <input type="search" id="loan-equipment-search" autocomplete="off" placeholder="Search equipment by name"
                               class="w-full py-3 pr-4 text-base border rounded-md pl-11 border-neutral-300 text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>

                    <div id="loan-equipment-list" class="mt-2 overflow-y-auto border divide-y rounded-md max-h-72 border-neutral-300 divide-neutral-200">
                        @forelse ($equipment as $item)
                            @php $none = $item->available_quantity < 1; @endphp
                            <div class="p-4 equipment-option {{ $none ? 'bg-neutral-50' : '' }}"
                                 data-name="{{ strtolower($item->equipment_name) }}">
                                <label for="loan-equipment-{{ $item->id }}"
                                       class="flex items-start gap-3 {{ $none ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                    <input type="checkbox" name="equipment[]" value="{{ $item->id }}"
                                           id="loan-equipment-{{ $item->id }}" @disabled($none)
                                           class="w-5 h-5 mt-1 rounded shrink-0 equipment-checkbox accent-primary-600"
                                           data-available="{{ $item->available_quantity }}">
                                    <span class="flex-1 min-w-0">
                                        <span class="block text-base font-medium {{ $none ? 'text-neutral-500' : 'text-neutral-900' }}">{{ $item->equipment_name }}</span>
                                        <span class="block text-sm {{ $none ? 'text-danger-700' : 'text-neutral-600' }}">
                                            @if($none)
                                                None available — nothing to hand over
                                            @else
                                                <span class="font-semibold tabular-nums">{{ $item->available_quantity }}</span>
                                                of {{ $item->quantity }} available
                                            @endif
                                        </span>
                                    </span>
                                </label>

                                <div class="hidden pl-8 mt-3 equipment-qty-wrap">
                                    <label for="loan-quantity-{{ $item->id }}" class="block text-sm font-medium text-neutral-800">
                                        How many <span class="text-neutral-600">(max {{ $item->available_quantity }})</span>
                                    </label>
                                    <input type="number" id="loan-quantity-{{ $item->id }}" name="quantities[{{ $item->id }}]"
                                           min="1" max="{{ $item->available_quantity }}" value="1" required disabled
                                           class="mt-1 w-full rounded-md border border-neutral-300 px-4 py-3 text-base tabular-nums text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 equipment-qty sm:w-40">
                                    <p class="hidden mt-2 text-sm font-medium equipment-qty-msg text-danger-700"></p>
                                </div>
                            </div>
                        @empty
                            <p class="p-4 text-base text-neutral-600">No lendable equipment in the inventory.</p>
                        @endforelse
                    </div>

                    <p id="loan-equipment-no-match" class="hidden mt-2 text-sm text-neutral-600">No equipment matches your search.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="loan-borrow-date" class="block text-base font-medium text-neutral-800">Taken out</label>
                        <input type="date" id="loan-borrow-date" name="borrow_date" required
                               value="{{ now()->toDateString() }}"
                               class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                    <div>
                        <label for="loan-return-date" class="block text-base font-medium text-neutral-800">Due back</label>
                        <input type="date" id="loan-return-date" name="return_date" required
                               value="{{ now()->addDays(7)->toDateString() }}"
                               class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    </div>
                </div>

                <div>
                    <label for="loan-purpose" class="block text-base font-medium text-neutral-800">Purpose</label>
                    <input type="text" id="loan-purpose" name="purpose" required maxlength="255"
                           placeholder="What it is being used for"
                           class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                </div>

                <div>
                    <label for="loan-class-schedule" class="flex items-baseline gap-2 text-base font-medium text-neutral-800">
                        Class schedule <span class="text-sm font-normal text-neutral-600">optional</span>
                    </label>
                    <select id="loan-class-schedule" name="class_schedule_id"
                            class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                        <option value="" selected>Not tied to a class</option>
                        {{-- Display-only grouping of the same records: one optgroup per
                             instructor, so the name is not repeated on every line. --}}
                        @foreach ($classSchedules->groupBy(fn ($s) => $s->instructor?->name ?? 'No Instructor')->sortKeys() as $instructorName => $instructorSchedules)
                            <optgroup label="{{ $instructorName }}">
                                @foreach ($instructorSchedules as $schedule)
                                    <option value="{{ $schedule->id }}">{{ $schedule->subject_code }} · {{ $schedule->schedule_time }} · {{ $schedule->room }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="loan-remarks" class="flex items-baseline gap-2 text-base font-medium text-neutral-800">
                        Remarks <span class="text-sm font-normal text-neutral-600">optional</span>
                    </label>
                    <textarea id="loan-remarks" name="remarks" rows="2" placeholder="Anything worth noting on handover"
                              class="mt-2 w-full rounded-md border border-neutral-300 px-4 py-3 text-base text-neutral-900 placeholder:text-neutral-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30"></textarea>
                </div>

                <p data-loan-preview class="flex items-center gap-2.5 rounded-lg bg-neutral-50 px-4 py-3 text-sm text-neutral-700">
                    <span data-loan-preview-dot class="w-2 h-2 rounded-full shrink-0 bg-neutral-400" aria-hidden="true"></span>
                    <span data-loan-preview-text>Tick the items being handed over</span>
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
                <p data-loan-hint class="text-sm min-w-0 text-neutral-600"></p>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" data-modal-close="new-loan-modal"
                            class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                        Cancel
                    </button>
                    <button type="submit" data-loan-submit
                            class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-md bg-primary-600 px-5 py-3 text-base font-semibold text-white hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-primary-600">
                        <i class="text-base fas fa-save" aria-hidden="true"></i> Record loan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
