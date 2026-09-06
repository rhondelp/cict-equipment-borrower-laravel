{{-- Shared status badge — flat light theme.
     Usage: <x-ui.badge status="Available" variant="success" />
            <x-ui.badge :status="$item->status" variant="success" />
     Variants map to Tailwind color tokens (success / warning / danger / neutral). --}}
@props(['status' => '', 'variant' => 'neutral'])

@php
    // Normalize variant: accept legacy color names
    $v = strtolower($variant);
    $map = [
        'success'    => 'bg-success-50 text-success-700 border-success-200',
        'available'  => 'bg-success-50 text-success-700 border-success-200',
        'approved'   => 'bg-success-50 text-success-700 border-success-200',
        'returned'   => 'bg-success-50 text-success-700 border-success-200',
        'good'       => 'bg-success-50 text-success-700 border-success-200',
        'warning'    => 'bg-warning-50 text-warning-700 border-warning-200',
        'pending'    => 'bg-warning-50 text-warning-700 border-warning-200',
        'borrowed'   => 'bg-warning-50 text-warning-700 border-warning-200',
        'danger'     => 'bg-danger-50 text-danger-700 border-danger-200',
        'unavailable'=> 'bg-danger-50 text-danger-700 border-danger-200',
        'declined'   => 'bg-danger-50 text-danger-700 border-danger-200',
        'overdue'    => 'bg-danger-50 text-danger-700 border-danger-200',
        'neutral'    => 'bg-neutral-100 text-neutral-700 border-neutral-200',
        'default'    => 'bg-neutral-100 text-neutral-700 border-neutral-200',
    ];
    $variantClass = $map[$v] ?? $map['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-medium border $variantClass"]) }}>
    {{ $status }}
</span>
