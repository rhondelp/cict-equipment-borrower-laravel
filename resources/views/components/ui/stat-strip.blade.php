@props(['stats' => []])

@php
    $tones = [
        'danger' => 'text-danger-700',
        'warning' => 'text-warning-700',
        'success' => 'text-success-700',
        'primary' => 'text-primary-700',
        'neutral' => 'text-neutral-900',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'overflow-hidden border rounded-xl border-neutral-200']) }}>
    <div class="grid gap-px bg-neutral-200 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($stats as $stat)
            @php
                $tone = $tones[$stat['tone'] ?? 'neutral'] ?? $tones['neutral'];
                $href = $stat['url'] ?? null;
                $chip = $stat['chip'] ?? null;
                $tag = $href ? 'a' : ($chip ? 'button' : 'div');
                $interactive = $href || $chip;
                if ($chip && (int) $stat['value'] === 0) {
                    $tag = 'div';
                    $interactive = false;
                    $chip = null;
                }
            @endphp
            <{{ $tag }} @if($href) href="{{ $href }}" @endif
               @if($chip)
                   type="button"
                   data-list-chip="{{ $chip }}"
                   data-list-target="{{ $stat['list'] ?? '' }}"
                   aria-label="Show only {{ strtolower($stat['label']) }} items"
               @endif
               class="flex flex-col gap-1 bg-white px-5 py-4 text-left {{ $interactive ? 'transition-colors hover:bg-neutral-50' : '' }}">
                <span class="flex items-center gap-1.5 text-sm font-medium text-neutral-600">
                    {{ $stat['label'] }}
                    @if($chip)
                        <i class="text-[11px] fas fa-filter text-neutral-400" aria-hidden="true"></i>
                    @endif
                </span>
                <span class="flex items-baseline gap-2">
                    <span class="text-3xl font-semibold tabular-nums {{ $tone }}">{{ $stat['value'] }}</span>
                    @if(! empty($stat['unit']))
                        <span class="text-sm text-neutral-600">{{ $stat['unit'] }}</span>
                    @endif
                </span>
                <span class="text-sm text-neutral-600">{{ $stat['sub'] ?? '' }}</span>
            </{{ $tag }}>
        @endforeach
    </div>
</div>
