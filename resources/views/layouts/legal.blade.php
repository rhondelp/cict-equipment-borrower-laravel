{{-- The shared shell for both legal documents.

     Each page supplies one `$doc` array and nothing else — title, lede,
     summary bullets and an ordered list of sections. The headings in the
     table-of-contents rail and the headings in the article are then the same
     strings read twice, so the rail cannot drift out of step with the prose,
     which is the usual way a hand-maintained contents list goes wrong.

       extends  -> layouts.legal
       php block -> $doc = ['key' => 'terms', 'title' => '...', 'sections' => [...]]

     (Directive names are written without their @ above: Blade lifts php
     blocks out of a template before it strips comments, so an @-prefixed php
     directive inside a comment swallows the file down to the next endphp.)

     Section shape: ['id', 'heading', 'paras' => [], 'items' => [], 'note' => '']
     `note` renders as a bordered aside — for a clause that changes what
     someone has to do and would otherwise be the last sentence of a paragraph.

     These pages are reachable signed out, so they carry their own navigation
     rather than relying on the app shell. --}}
@extends('components.default')

@php
    $docs = [
        'terms' => ['label' => 'Terms of service', 'url' => route('legal.terms')],
        'privacy' => ['label' => 'Privacy policy', 'url' => route('legal.privacy')],
    ];

    $otherKey = $doc['key'] === 'terms' ? 'privacy' : 'terms';
    $other = $docs[$otherKey];

    // Counted from the text on the page rather than written into it, so it
    // cannot quietly become wrong when a section is added.
    $words = str_word_count(collect($doc['sections'])
        ->flatMap(fn ($s) => array_merge($s['paras'] ?? [], $s['items'] ?? [], array_filter([$s['note'] ?? null])))
        ->prepend($doc['lede'])
        ->concat($doc['summary'])
        ->implode(' '));
    $readMinutes = max(1, (int) round($words / 220));
@endphp

@section('title', $doc['title'].' - CICT Equipment Borrower System')
@section('description', $doc['lede'])

@push('styles')
    {{-- The reading face, on these two pages only. --}}
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,400;8..60,600&display=swap" rel="stylesheet">
@endpush

@section('content')
<div class="min-h-[100dvh] page-bg">

    <a href="#legal-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-toast focus:rounded-md focus:border focus:border-primary-200 focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700">
        Skip to content
    </a>

    {{-- The tab switch. Two links to two real URLs, not a client-side toggle:
         these documents are linked to from consent checkboxes and emails, and
         a tab that only exists in JavaScript cannot be linked to. --}}
    <header class="sticky top-0 z-sticky bg-white border-b border-neutral-200">
        <div class="mx-auto flex h-[60px] max-w-[1060px] items-center justify-between gap-5 px-4 sm:px-8">
            <a href="{{ url('/') }}" class="flex items-center gap-[11px] min-w-0" aria-label="CICT Equipment Borrower System home">
                <span class="grid w-8 h-8 overflow-hidden bg-white border rounded-[9px] shrink-0 place-items-center border-neutral-200">
                    <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="" class="object-contain w-6 h-6">
                </span>
                <span class="min-w-0 hidden sm:block">
                    <span class="block text-[13px] font-semibold leading-tight text-neutral-900">CICT Equipment</span>
                    <span class="block text-[11px] leading-tight text-neutral-500">Borrower System</span>
                </span>
            </a>

            <nav class="flex items-center gap-1.5 shrink-0" aria-label="Legal documents">
                @foreach($docs as $key => $tab)
                    <a href="{{ $tab['url'] }}"
                       @if($key === $doc['key']) aria-current="page" @endif
                       class="flex h-8 items-center rounded-full border px-[13px] text-[13px] font-medium transition-colors
                              {{ $key === $doc['key']
                                    ? 'border-primary-300 bg-primary-50 text-primary-700'
                                    : 'border-neutral-200 bg-white text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </header>

    <div class="mx-auto grid max-w-[1060px] items-start gap-10 px-4 pb-18 pt-10 sm:px-8 lg:grid-cols-[minmax(0,1fr)_216px] lg:gap-12">

        <main id="legal-content" class="flex flex-col min-w-0 gap-[30px]">

            <div class="flex flex-col gap-2.5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-neutral-500">Legal</p>
                <h1 class="text-[28px] font-semibold leading-[1.15] tracking-[-0.02em] text-neutral-900 text-balance sm:text-[34px]">
                    {{ $doc['title'] }}
                </h1>
                <p class="max-w-[62ch] text-[15px] leading-[1.6] text-neutral-600 text-pretty">{{ $doc['lede'] }}</p>
                <p class="mt-0.5 flex flex-wrap items-center gap-2.5 text-[12.5px] text-neutral-500">
                    <span>Last updated {{ $doc['updated'] }}</span>
                    <span class="w-[3px] h-[3px] rounded-full bg-neutral-300" aria-hidden="true"></span>
                    <span>{{ $readMinutes }} minute read</span>
                </p>
            </div>

            {{-- The three things that actually change what someone does. Most
                 people read this box and nothing else, so it says the things
                 that cost them something if they do not know them. --}}
            <div class="flex flex-col gap-[11px] rounded-xl border border-neutral-200 bg-white px-5 py-[18px]">
                <h2 class="text-[12px] font-semibold uppercase tracking-[0.06em] text-neutral-500">The short version</h2>
                <ul class="flex flex-col gap-[11px]">
                    @foreach($doc['summary'] as $point)
                        <li class="flex items-start gap-[11px]">
                            <span class="mt-2 h-[5px] w-[5px] shrink-0 rounded-full bg-primary-600" aria-hidden="true"></span>
                            <span class="text-[14.5px] leading-[1.55] text-neutral-800 text-pretty">{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- The rail is a right-hand column from lg up; below that it folds
                 into a disclosure here, so a phone keeps the jump links without
                 a sticky column eating the viewport. --}}
            <details class="rounded-xl border border-neutral-200 bg-white lg:hidden">
                <summary class="cursor-pointer px-5 py-3 text-[13px] font-semibold text-neutral-700 marker:text-neutral-400">
                    On this page
                </summary>
                <ul class="flex flex-col gap-px px-3 pb-3">
                    @foreach($doc['sections'] as $section)
                        <li>
                            <a href="#{{ $section['id'] }}"
                               class="block rounded-md px-2 py-1.5 text-[13px] leading-[1.45] text-neutral-600 hover:bg-neutral-50 hover:text-primary-700">
                                {{ $section['heading'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </details>

            {{-- The document itself. 17px serif at a 66-character measure: this
                 is text someone is expected to read start to finish, and the
                 13px it sat at before was a size for scanning a table. --}}
            <article class="flex flex-col gap-[34px]">
                @foreach($doc['sections'] as $section)
                    <section id="{{ $section['id'] }}" class="flex scroll-mt-[84px] flex-col gap-3">
                        <h2 class="text-[20px] font-semibold tracking-[-0.01em] text-neutral-900 text-balance">
                            {{ $section['heading'] }}
                        </h2>

                        @foreach($section['paras'] ?? [] as $paragraph)
                            <p class="max-w-[66ch] font-serif text-[17px] leading-[1.65] text-neutral-800 text-pretty">{{ $paragraph }}</p>
                        @endforeach

                        @if(! empty($section['items']))
                            <ul class="flex max-w-[66ch] list-disc flex-col gap-[9px] pl-5 mt-0.5">
                                @foreach($section['items'] as $item)
                                    <li class="font-serif text-[17px] leading-[1.6] text-neutral-800 text-pretty">{{ $item }}</li>
                                @endforeach
                            </ul>
                        @endif

                        {{-- A fallback clause, given its own edge. Both of these
                             used to be the last sentence of a paragraph, which
                             is where a reader stops looking. --}}
                        @if(! empty($section['note']))
                            <p class="mt-1 max-w-[62ch] border-l-2 border-neutral-300 pl-3.5 text-[14px] leading-[1.6] text-neutral-600 text-pretty"
                               data-legal-aside>
                                {{ $section['note'] }}
                            </p>
                        @endif
                    </section>
                @endforeach
            </article>

            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-neutral-200 pt-[22px]">
                <p class="text-[14px] text-neutral-600 text-pretty">
                    Questions about any of this?
                    @if(config('office.email'))
                        Email <a href="mailto:{{ config('office.email') }}" class="font-medium text-primary-700 underline underline-offset-2 [overflow-wrap:anywhere] hover:text-primary-800">{{ config('office.email') }}</a>
                        or drop by the department office.
                    @else
                        Drop by the department office.
                    @endif
                </p>
                <a href="{{ $other['url'] }}"
                   class="flex h-[38px] shrink-0 items-center rounded-[9px] border border-neutral-200 bg-white px-[15px] text-[13.5px] font-medium text-neutral-700 transition-colors hover:bg-neutral-50 hover:text-neutral-900">
                    Read the {{ strtolower($other['label']) }} &rarr;
                </a>
            </div>
        </main>

        <nav class="sticky top-[84px] hidden flex-col gap-2.5 min-w-0 lg:flex" aria-label="On this page">
            <p class="pl-[11px] text-[11px] font-semibold uppercase tracking-[0.09em] text-neutral-500">On this page</p>
            <ul class="flex flex-col gap-px">
                @foreach($doc['sections'] as $section)
                    <li>
                        <a href="#{{ $section['id'] }}"
                           class="block border-l-2 border-neutral-200 px-[11px] py-1.5 text-[13px] leading-[1.45] text-neutral-600 text-pretty transition-colors hover:border-primary-400 hover:text-primary-700">
                            {{ $section['heading'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('login') }}" class="mt-3.5 pl-[11px] text-[13px] text-neutral-500 hover:text-neutral-800">&larr; Back to sign in</a>
        </nav>
    </div>
</div>
@endsection
