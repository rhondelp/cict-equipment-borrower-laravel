{{-- Empty state. Usage: <x-ui.empty-state icon="fa-clipboard-list" title="No pending requests" hint="Requests appear here once students submit them." /> --}}
@props(['icon' => 'fa-inbox', 'title', 'hint' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-14 text-center']) }}>
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
        <i class="fas {{ $icon }} text-lg text-gray-400"></i>
    </div>
    <h3 class="mt-4 text-sm font-semibold text-gray-900">{{ $title }}</h3>
    @if ($hint)
        <p class="mt-1 max-w-xs text-sm text-gray-500">{{ $hint }}</p>
    @endif
    @if (isset($slot) && trim($slot) !== '')
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
