{{-- Shared page header — flat light theme.
     Usage: <x-ui.page-header eyebrow="Equipment" title="Manage inventory" />
            <x-ui.page-header eyebrow="..." title="...">
                <x-slot:actions><button>...</button></x-slot:actions>
            </x-ui.page-header>

     `logo` is opt-in and defaults to false, so every page that does not pass it
     (all the admin views, which get their branding from the sidebar) keeps the
     same left side. Borrower pages have no sidebar, so they pass it to fill the
     otherwise empty space.

     `menu` controls the mobile hamburger and defaults to true, which is what the
     admin views need. The toggle is wired up in components/default.blade.php
     against `.sidebar` / `.sidebar-overlay`, and both of those live in
     components/admin/navbar.blade.php — so on the borrower pages, which include
     no navbar, the button was inert: it rendered, took 44px of a phone-width
     header and did nothing on tap. Those pages pass :menu="false". --}}
@props(['eyebrow' => '', 'title' => '', 'logo' => false, 'menu' => true])

{{-- Translucent + blur so content scrolling underneath reads as passing behind
     the bar rather than being clipped by it; the solid `bg-white` stays as the
     fallback wherever backdrop-filter is unsupported. --}}
<header class="sticky top-0 z-30 bg-white border-b border-neutral-200 supports-[backdrop-filter]:bg-white/80 supports-[backdrop-filter]:backdrop-blur">
    {{-- max-w-content mx-auto matches the <main> of every page in the app, so the
         header no longer runs full-bleed while the content below it is centred. --}}
    <div class="flex items-center justify-between w-full gap-3 px-4 py-3 mx-auto sm:px-6 sm:py-4 max-w-content">
        <div class="flex items-center min-w-0 gap-3">
            @if($menu)
                <button id="menu-toggle" class="md:hidden shrink-0 inline-flex items-center justify-center w-11 h-11 rounded-md text-neutral-700 transition-colors hover:bg-neutral-100 active:bg-neutral-200" aria-label="Open menu">
                    <i class="text-lg fas fa-bars" aria-hidden="true"></i>
                </button>
            @endif
            @if($logo)
                {{-- Same logo + wordmark as the admin sidebar. Rendered as <p> rather
                     than <h1> so it does not compete with the page title below. --}}
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
