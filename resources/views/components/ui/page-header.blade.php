{{-- Shared page header — consistent eyebrow + title treatment
     Usage: <x-ui.page-header eyebrow="Equipment" title="Manage inventory" />
--}}
@props(['eyebrow' => '', 'title' => ''])

<header class="sticky top-0 z-30 dash-header">
    <div class="flex items-center justify-between px-6 py-4">
        <div class="flex items-center gap-3">
            <button id="menu-toggle" class="text-neutral-400 hover:text-white md:hidden lg:hidden">
                <i class="text-lg fas fa-bars"></i>
            </button>
            <div>
                @if($eyebrow)
                    <p class="text-xs font-medium tracking-widest uppercase" style="color:var(--text-muted)">{{ $eyebrow }}</p>
                @endif
                @if($title)
                    <h1 class="text-sm font-semibold tracking-tight text-white -mt-0.5">{{ $title }}</h1>
                @endif
                @if(trim($slot) !== '')
                    <div class="mt-0.5">{{ $slot }}</div>
                @endif
            </div>
        </div>
        @if(isset($actions))
            <div class="flex items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>
</header>
