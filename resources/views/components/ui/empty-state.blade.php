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
        <p class="max-w-sm mx-auto mt-1 text-base text-pretty text-neutral-600">{{ $message }}</p>
    @endif

    @isset($action)
        <div class="flex justify-center mt-5">
            {{ $action }}
        </div>
    @endisset
</div>
