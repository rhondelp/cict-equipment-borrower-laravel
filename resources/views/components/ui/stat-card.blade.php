{{-- Dashboard stat card. Optional href makes the whole card clickable. --}}
@props(['label', 'value', 'icon', 'href' => null])

@php
    $base = 'flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-colors';
@endphp

@if ($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $base.' hover:border-brand/40']) }}>
@else
<div {{ $attributes->merge(['class' => $base]) }}>
@endif
    <div>
        <p class="text-xs font-medium text-gray-500">{{ $label }}</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $value }}</p>
    </div>
    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-light">
        <i class="fas {{ $icon }} text-lg text-brand"></i>
    </div>
@if ($href)
</a>
@else
</div>
@endif
