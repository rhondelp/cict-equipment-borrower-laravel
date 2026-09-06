{{-- Shared page header — flat light theme.
     Usage: <x-ui.page-header eyebrow="Equipment" title="Manage inventory" />
            <x-ui.page-header eyebrow="..." title="...">
                <x-slot:actions><button>...</button></x-slot:actions>
            </x-ui.page-header> --}}
@props(['eyebrow' => '', 'title' => ''])

<header class="sticky top-0 z-30 bg-white border-b border-neutral-200">
    <div class="flex items-center justify-between px-4 sm:px-6 py-4 gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <button id="menu-toggle" class="md:hidden shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-md text-neutral-600 hover:bg-neutral-100" aria-label="Open menu">
                <i class="text-base fas fa-bars"></i>
            </button>
            <div class="min-w-0">
                @if($eyebrow)
                    <p class="text-xs font-medium uppercase tracking-wider text-neutral-500 truncate">{{ $eyebrow }}</p>
                @endif
                @if($title)
                    <h1 class="text-lg sm:text-xl font-semibold tracking-tight text-neutral-900 truncate">{{ $title }}</h1>
                @endif
                @if(trim($slot) !== '')
                    <div class="mt-1 text-sm text-neutral-600">{{ $slot }}</div>
                @endif
            </div>
        </div>
        @if(isset($actions))
            <div class="flex items-center gap-2 shrink-0">
                {{ $actions }}
            </div>
        @endif
    </div>
</header>
