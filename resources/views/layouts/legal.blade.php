{{-- Shared shell for the two legal pages. Nests inside the app layout and
     yields a `legal-body` section, so each page supplies only its prose:

       @extends('layouts.legal')
       @section('legal-title', 'Privacy policy')
       @section('legal-updated', '20 September 2026')
       @section('legal-body') <h2>…</h2><p>…</p> @endsection

     These pages are reachable while signed out, so they carry their own way
     back rather than relying on the app shell. --}}
@extends('components.default')

@section('title', View::yieldContent('legal-title') . ' - CICT Equipment Borrower System')

@section('content')
<div class="flex flex-col min-h-[100dvh] page-bg">

    <a href="#legal-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>

    <x-ui.page-header logo :menu="false" eyebrow="Legal" :title="View::yieldContent('legal-title')" />

    {{-- max-w-3xl rather than the max-w-content the admin pages use: those hold
         tables, this holds prose, which stops being readable past ~68 characters. --}}
    <main id="legal-content" class="w-full max-w-3xl px-4 py-10 mx-auto sm:px-6 sm:py-14">
        <article class="p-6 bg-white border rounded-xl border-neutral-200 shadow-flat sm:p-10">
            @hasSection('legal-updated')
                <p class="text-sm text-neutral-600">Last updated @yield('legal-updated')</p>
            @endif

            {{-- Prose styling lives here as descendant variants so each page
                 writes plain semantic HTML and nothing drifts between the two. --}}
            <div class="mt-6 space-y-8
                        [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:text-neutral-900 [&_h2]:mb-2 [&_h2]:text-balance
                        [&_p]:text-base [&_p]:leading-relaxed [&_p]:text-neutral-700 [&_p]:max-w-[68ch] [&_p]:text-pretty
                        [&_ul]:mt-3 [&_ul]:space-y-2 [&_ul]:pl-5 [&_ul]:list-disc
                        [&_li]:text-base [&_li]:leading-relaxed [&_li]:text-neutral-700 [&_li]:max-w-[64ch]
                        [&_a]:font-semibold [&_a]:text-primary-700 [&_a]:underline hover:[&_a]:text-primary-800">
                @yield('legal-body')
            </div>
        </article>

        <nav class="flex flex-wrap items-center mt-8 gap-x-6 gap-y-3" aria-label="Legal">
            <a href="{{ url('/') }}"
               class="inline-flex items-center gap-2 text-base font-semibold transition-colors text-primary-700 hover:text-primary-800">
                <i class="text-sm fas fa-arrow-left" aria-hidden="true"></i> Back to home
            </a>
            <a href="{{ route('legal.privacy') }}" class="text-sm transition-colors text-neutral-600 hover:text-neutral-900">Privacy policy</a>
            <a href="{{ route('legal.terms') }}" class="text-sm transition-colors text-neutral-600 hover:text-neutral-900">Terms of service</a>
        </nav>
    </main>
</div>
@endsection
