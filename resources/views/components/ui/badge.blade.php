{{-- Shared status badge — single token system (success / warning / danger / neutral)
     Usage: <x-ui.badge status="Available" variant="success" />
            <x-ui.badge :status="$item->status" variant="success" />
     Variants map to tailwind.config.js color tokens.
--}}
@props(['status' => '', 'variant' => 'neutral'])

@php
    // Normalize variant: accept legacy color names
    $v = strtolower($variant);
    $map = [
        'success' => 'badge--success',
        'available' => 'badge--success',
        'approved' => 'badge--success',
        'returned' => 'badge--success',
        'good' => 'badge--success',
        'warning' => 'badge--warning',
        'pending' => 'badge--warning',
        'borrowed' => 'badge--warning',
        'danger' => 'badge--danger',
        'unavailable' => 'badge--danger',
        'declined' => 'badge--danger',
        'overdue' => 'badge--danger',
        'neutral' => 'badge--neutral',
        'default' => 'badge--neutral',
    ];
    $variantClass = $map[$v] ?? 'badge--neutral';
@endphp

<span {{ $attributes->merge(['class' => "badge $variantClass"]) }}>{{ $status }}</span>
