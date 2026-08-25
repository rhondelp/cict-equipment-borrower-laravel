{{-- Single source of truth for status colors. Usage: <x-ui.status-badge :status="$tx->status" /> --}}
@props(['status'])

@php
    $map = [
        'Pending'      => 'bg-yellow-100 text-yellow-700 ring-yellow-600/20',
        'Approved'     => 'bg-green-100 text-green-700 ring-green-600/20',
        'Declined'     => 'bg-red-100 text-red-700 ring-red-600/20',
        'Borrowed'     => 'bg-blue-100 text-blue-700 ring-blue-600/20',
        'Returned'     => 'bg-green-100 text-green-700 ring-green-600/20',
        'Overdue'      => 'bg-red-100 text-red-700 ring-red-600/20',
        'Available'    => 'bg-green-100 text-green-700 ring-green-600/20',
        'Unavailable'  => 'bg-gray-100 text-gray-600 ring-gray-500/20',
        'Good'         => 'bg-green-100 text-green-700 ring-green-600/20',
        'Damaged'      => 'bg-red-100 text-red-700 ring-red-600/20',
        'Needs Repair' => 'bg-orange-100 text-orange-700 ring-orange-600/20',
    ];
    $classes = $map[$status] ?? 'bg-gray-100 text-gray-600 ring-gray-500/20';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset '.$classes]) }}>
    {{ ucfirst(trim($status)) }}
</span>
