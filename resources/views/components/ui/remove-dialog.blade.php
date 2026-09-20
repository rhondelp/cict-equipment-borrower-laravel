{{-- The one destructive confirm, shared by equipment, users and loans.

     Every remove in this app now goes through it, and it always shows the same
     four things:

       1. what is about to happen, in a sentence
       2. the consequences as data — units out, records that reference the row
       3. a non-destructive default on the right, which is where the eye lands
       4. a hard delete only when nothing references the row; otherwise the
          reason it is refused, in its place

     One instance per page. The trigger buttons carry the row's figures in data
     attributes and resources/js/ui.js fills the shell — see initRemoveDialogs.

     Usage:
        <x-ui.remove-dialog
            id="remove-dialog"
            safe-label="Retire item"
            safe-icon="fa-box-archive"
            :facts="['Units owned', 'Out with borrowers', 'Loans in the history log']" />

     A safe action that needs a reason passes one through the `safeFields` slot
     with `data-requires-reason`; the submit stays disabled, and the hint says
     why, until the field is filled. --}}
@props([
    'id' => 'remove-dialog',
    'safeLabel' => 'Archive',
    'safeIcon' => 'fa-box-archive',
    'cancelLabel' => 'Keep it',
    'deleteLabel' => 'Delete permanently',
    'facts' => [],
    'readyHint' => '',
    'blockedHint' => '',
])

<div id="{{ $id }}" data-modal
     class="fixed inset-0 z-modal items-center justify-center hidden p-4 overflow-y-auto bg-neutral-900/50"
     role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
    <div class="w-full max-w-lg my-auto bg-white border border-neutral-200 rounded-xl shadow-pop">

        <div class="px-6 py-5 space-y-4">
            <h2 id="{{ $id }}-title" data-remove-title class="text-lg font-semibold text-neutral-900"></h2>
            <p data-remove-body class="text-base leading-relaxed text-neutral-600 text-pretty"></p>

            {{-- The consequences, as figures rather than as an adjective.
                 "This action cannot be undone" told an admin nothing they could
                 weigh; "2 units out with borrowers" tells them everything. --}}
            <dl class="overflow-hidden border divide-y rounded-lg border-neutral-200 divide-neutral-200">
                @foreach(['a', 'b', 'c'] as $index => $key)
                    @if(isset($facts[$index]))
                        <div data-fact="{{ $key }}" class="flex items-center justify-between gap-4 px-4 py-2.5 text-base">
                            <dt class="text-neutral-600">{{ $facts[$index] }}</dt>
                            <dd data-fact-value class="font-semibold tabular-nums text-neutral-900"></dd>
                        </div>
                    @endif
                @endforeach
            </dl>

            @isset($safeFields)
                <div class="space-y-2">{{ $safeFields }}</div>
            @endisset

            @if($readyHint || $blockedHint)
                <p data-remove-safe-hint
                   data-ready="{{ $readyHint }}"
                   data-blocked="{{ $blockedHint }}"
                   class="text-sm text-neutral-600">{{ $readyHint }}</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-t border-neutral-200 bg-neutral-50">
            <div class="min-w-0">
                {{-- Hidden whenever history references the row: the server would
                     refuse it, and a button that is always refused is worse than
                     no button at all. --}}
                <form data-remove-delete-form method="POST" action="" hidden>
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="py-1 text-sm font-semibold underline rounded text-danger-700 underline-offset-4 hover:text-danger-800">
                        {{ $deleteLabel }}
                    </button>
                </form>
                <p data-remove-blocked class="text-sm text-neutral-600 text-pretty" hidden></p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button type="button" data-modal-close="{{ $id }}"
                        class="inline-flex items-center justify-center min-h-[44px] rounded-md border border-neutral-300 bg-white px-5 py-3 text-base font-semibold text-neutral-700 hover:bg-neutral-50">
                    {{ $cancelLabel }}
                </button>
                <form data-remove-safe-form method="POST" action="">
                    @csrf
                    {{-- Label and icon are overridable per row: the same dialog
                         reads "Retire item" for a live item and "Restore item"
                         for one already retired. --}}
                    <button type="submit" data-autofocus
                            class="inline-flex items-center justify-center gap-2 min-h-[44px] rounded-md bg-neutral-800 px-5 py-3 text-base font-semibold text-white hover:bg-neutral-900 disabled:hover:bg-neutral-800">
                        <i data-remove-safe-icon class="fas {{ $safeIcon }} text-base" aria-hidden="true"></i>
                        <span data-remove-safe-label>{{ $safeLabel }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
