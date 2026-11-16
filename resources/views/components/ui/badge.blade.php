@props(['status' => '', 'variant' => 'neutral'])

@php
    $v = strtolower($variant);
    $map = [
        'success'    => 'bg-success-100 text-success-800 border-success-300',
        'available'  => 'bg-success-100 text-success-800 border-success-300',
        'approved'   => 'bg-success-100 text-success-800 border-success-300',
        'returned'   => 'bg-success-100 text-success-800 border-success-300',
        'good'       => 'bg-success-100 text-success-800 border-success-300',
        'warning'    => 'bg-warning-100 text-warning-700 border-warning-300',
        'pending'    => 'bg-warning-100 text-warning-700 border-warning-300',
        'borrowed'   => 'bg-warning-100 text-warning-700 border-warning-300',
        'danger'     => 'bg-danger-100 text-danger-700 border-danger-300',
        'unavailable'=> 'bg-danger-100 text-danger-700 border-danger-300',
        'declined'   => 'bg-danger-100 text-danger-700 border-danger-300',
        'overdue'    => 'bg-danger-100 text-danger-700 border-danger-300',
        'primary'    => 'bg-primary-100 text-primary-800 border-primary-300',
        'neutral'    => 'bg-neutral-100 text-neutral-800 border-neutral-300',
        'default'    => 'bg-neutral-100 text-neutral-800 border-neutral-300',
    ];
    $variantClass = $map[$v] ?? $map['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-sm font-semibold border $variantClass"]) }}>
    {{ $status }}
</span>
