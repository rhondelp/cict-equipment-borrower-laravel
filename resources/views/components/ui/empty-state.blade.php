{{-- Shared empty state — flat light theme.

     Usage: <x-ui.empty-state icon="fa-inbox" title="No return logs yet"
                              message="When borrowers return equipment, the logs appear here." />

            <x-ui.empty-state icon="fa-clipboard-list" title="No requests yet" message="…">
                <x-slot:action><button class="btn-primary">Request item</button></x-slot:action>
            </x-ui.empty-state>

     The seven empty states across the app had drifted onto three different type
     scales — admin/logs and admin/notification still used text-sm/text-xs from
     before the scale was bumped, while the dashboards used text-lg/text-base —
     and two different icon treatments. This is the one both now share: the
     framed icon tile the borrower dashboard introduced, which reads as a
     deliberate state rather than a missing glyph. --}}
@props([
    'icon'    => 'fa-inbox',
    'title'   => '',
    'message' => '',
])

<div {{ $attributes->merge(['class' => 'px-6 py-14 text-center']) }}>
    <span class="grid w-16 h-16 mx-auto mb-4 border rounded-2xl bg-neutral-50 border-neutral-200 place-items-center">
        <i class="text-2xl fas {{ $icon }} text-neutral-400" aria-hidden="true"></i>
    </span>

    @if($title)
        <p class="text-lg font-semibold text-balance text-neutral-800">{{ $title }}</p>
    @endif

    @if($message)
        {{-- max-w-sm keeps the sentence near a readable measure instead of
             running the full width of a table card. --}}
        <p class="max-w-sm mx-auto mt-1 text-base text-pretty text-neutral-600">{{ $message }}</p>
    @endif

    @isset($action)
        <div class="flex justify-center mt-5">
            {{ $action }}
        </div>
    @endisset
</div>
