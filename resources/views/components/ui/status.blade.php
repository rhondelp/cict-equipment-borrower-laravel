@props(['label' => '', 'tone' => 'neutral'])

@php
    $tones = [
        'success' => 'text-success-700',
        'danger' => 'text-danger-700',
        'warning' => 'text-warning-700',
        'primary' => 'text-primary-700',
        'neutral' => 'text-neutral-600',
    ];
    $dots = [
        'success' => 'bg-success-600',
        'danger' => 'bg-danger-600',
        'warning' => 'bg-warning-500',
        'primary' => 'bg-primary-600',
        'neutral' => 'bg-neutral-400',
    ];
    $toneClass = $tones[$tone] ?? $tones['neutral'];
    $dotClass = $dots[$tone] ?? $dots['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-2 text-sm font-semibold whitespace-nowrap $toneClass"]) }}>
    <span class="w-2 h-2 rounded-full shrink-0 {{ $dotClass }}" aria-hidden="true"></span>
    {{ $label }}
</span>
