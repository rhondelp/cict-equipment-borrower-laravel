{{-- Shared shell for the error pages. Kept deliberately close to the legal
     layout: the point of a branded error page is that it looks like the rest of
     the app rather than like the server fell over.

     Every error page offers a way onward, and where someone is signed in that
     way is their own dashboard rather than the public landing page. --}}
@extends('components.default')

@section('title', View::yieldContent('code') . ' ' . View::yieldContent('heading') . ' - CICT Equipment Borrower System')

@section('content')
<div class="grid min-h-[100dvh] place-items-center page-bg px-4 py-12">
    <main class="w-full max-w-lg text-center">

        <div class="flex items-center justify-center w-16 h-16 mx-auto overflow-hidden bg-white border rounded-xl border-neutral-200 shadow-flat">
            <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png"
                 alt="College of Information and Communications Technology" class="object-contain w-10 h-10">
        </div>

        {{-- The code is set in a lighter weight than the heading: it labels the
             page, the sentence beneath it is what the reader actually needs. --}}
        <p class="mt-8 text-sm font-semibold tracking-widest uppercase text-primary-700 tabular-nums">
            Error @yield('code')
        </p>
        <h1 class="mt-2 text-3xl font-bold text-balance text-neutral-900">@yield('heading')</h1>
        <p class="max-w-[50ch] mx-auto mt-3 text-base leading-relaxed text-pretty text-neutral-600">
            @yield('message')
        </p>

        <div class="flex flex-col items-center justify-center gap-3 mt-8 sm:flex-row">
            @auth
                @php
                    $home = Auth::user()->user_type === 'Admin'
                        ? route('admin.dashboard')
                        : route('borrower.dashboard');
                @endphp
                <a href="{{ $home }}" class="btn-primary w-full sm:w-auto">
                    <i class="text-base fas fa-gauge-high" aria-hidden="true"></i> Go to my dashboard
                </a>
            @else
                <a href="{{ url('/') }}" class="btn-primary w-full sm:w-auto">
                    <i class="text-base fas fa-house" aria-hidden="true"></i> Back to home
                </a>
                <a href="{{ route('login') }}" class="btn-secondary w-full sm:w-auto">
                    <i class="text-base fa-solid fa-right-to-bracket" aria-hidden="true"></i> Sign in
                </a>
            @endauth
        </div>

        @hasSection('detail')
            <p class="mt-8 text-sm text-neutral-500">@yield('detail')</p>
        @endif
    </main>
</div>
@endsection
