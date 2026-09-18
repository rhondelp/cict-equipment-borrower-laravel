{{-- Shared page header — flat light theme.
     Usage: <x-ui.page-header eyebrow="Equipment" title="Manage inventory" />
            <x-ui.page-header eyebrow="..." title="...">
                <x-slot:actions><button>...</button></x-slot:actions>
            </x-ui.page-header> --}}
@props(['eyebrow' => '', 'title' => ''])

<header class="sticky top-0 z-30 bg-white border-b border-neutral-200">
    <div class="flex items-center justify-between px-4 sm:px-6 py-4 gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <button id="menu-toggle" class="md:hidden shrink-0 inline-flex items-center justify-center w-11 h-11 rounded-md text-neutral-700 hover:bg-neutral-100" aria-label="Open menu">
                <i class="text-lg fas fa-bars"></i>
            </button>
            <div class="min-w-0">
                @if($eyebrow)
                    <p class="text-sm font-semibold uppercase tracking-wider text-neutral-600 truncate">{{ $eyebrow }}</p>
                @endif
                @if($title)
                    <h1 class="text-xl sm:text-2xl font-semibold text-neutral-900 truncate">{{ $title }}</h1>
                @endif
                @if(trim($slot) !== '')
                    <div class="mt-1 text-base text-neutral-600">{{ $slot }}</div>
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
