{{-- Shared page header — flat light theme.
     Usage: <x-ui.page-header eyebrow="Equipment" title="Manage inventory" />
            <x-ui.page-header eyebrow="..." title="...">
                <x-slot:actions><button>...</button></x-slot:actions>
            </x-ui.page-header>

     `logo` is opt-in and defaults to false, so every page that does not pass it
     (all the admin views, which get their branding from the sidebar) renders
     exactly as before. Borrower pages have no sidebar, so they pass it to fill
     the otherwise empty left side of the header. --}}
@props(['eyebrow' => '', 'title' => '', 'logo' => false])

<header class="sticky top-0 z-30 bg-white border-b border-neutral-200">
    <div class="flex items-center justify-between px-4 sm:px-6 py-4 gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <button id="menu-toggle" class="md:hidden shrink-0 inline-flex items-center justify-center w-11 h-11 rounded-md text-neutral-700 hover:bg-neutral-100" aria-label="Open menu">
                <i class="text-lg fas fa-bars"></i>
            </button>
            @if($logo)
                {{-- Same logo + wordmark as the admin sidebar. Rendered as <p> rather
                     than <h1> so it does not compete with the page title below. --}}
                <div class="flex items-center gap-3 shrink-0">
                    <div class="flex items-center justify-center w-10 h-10 overflow-hidden bg-white border rounded-lg border-neutral-200 shrink-0">
                        <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT" class="object-cover w-full h-full">
                    </div>
                    <div class="hidden min-w-0 sm:block">
                        <p class="text-base font-semibold truncate text-neutral-900">CICT Equipment</p>
                        <p class="text-sm truncate text-neutral-600">Management System</p>
                    </div>
                </div>
                <div class="hidden w-px h-10 bg-neutral-200 shrink-0 sm:block" aria-hidden="true"></div>
            @endif
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
