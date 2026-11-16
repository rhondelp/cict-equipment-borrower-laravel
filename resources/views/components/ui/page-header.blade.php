@props(['eyebrow' => '', 'title' => '', 'logo' => false, 'menu' => true])

<header class="sticky top-0 z-30 bg-white border-b border-neutral-200 supports-[backdrop-filter]:bg-white/80 supports-[backdrop-filter]:backdrop-blur">
    <div class="flex items-center justify-between w-full gap-3 px-4 py-3 mx-auto sm:px-6 sm:py-4 max-w-content">
        <div class="flex items-center min-w-0 gap-3">
            @if($menu)
                <button id="menu-toggle" class="md:hidden shrink-0 inline-flex items-center justify-center w-11 h-11 rounded-md text-neutral-700 transition-colors hover:bg-neutral-100 active:bg-neutral-200" aria-label="Open menu">
                    <i class="text-lg fas fa-bars" aria-hidden="true"></i>
                </button>
            @endif
            @if($logo)
                <div class="flex items-center gap-3 shrink-0">
                    <div class="flex items-center justify-center w-10 h-10 overflow-hidden bg-white border rounded-lg border-neutral-200 shrink-0">
                        <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT" class="object-cover w-full h-full">
                    </div>
                    <div class="hidden min-w-0 sm:block">
                        <p class="text-base font-semibold leading-tight truncate text-neutral-900">CICT Equipment</p>
                        <p class="text-sm leading-tight truncate text-neutral-600">Management System</p>
                    </div>
                </div>
                <div class="hidden w-px h-10 bg-neutral-200 shrink-0 sm:block" aria-hidden="true"></div>
            @endif
            <div class="min-w-0">
                @if($eyebrow)
                    <p class="text-xs font-semibold tracking-wider uppercase truncate sm:text-sm text-primary-700">{{ $eyebrow }}</p>
                @endif
                @if($title)
                    <h1 class="text-lg font-semibold truncate sm:text-2xl text-neutral-900">{{ $title }}</h1>
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
