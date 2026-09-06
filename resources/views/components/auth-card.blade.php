{{-- Shared auth-card wrapper — flat light theme.
     Provides centered white card on neutral-50 background.
     Usage: <x-auth-card title="Sign in" subtitle="...">...form...</x-auth-card> --}}
@props([
    'title'    => '',
    'subtitle' => '',
    'helper'   => '',
    'logo'     => 'https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png',
])

<div class="min-h-screen bg-neutral-50 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md bg-white border border-neutral-200 rounded-xl p-8 shadow-flat">
        <div class="flex items-start gap-3 mb-6">
            <div class="shrink-0 w-12 h-12 rounded-lg bg-neutral-100 border border-neutral-200 flex items-center justify-center overflow-hidden">
                <img src="{{ $logo }}" alt="CICT Logo" class="w-full h-full object-cover">
            </div>
            <div class="pt-0.5">
                <h1 class="text-xl font-semibold tracking-tight text-neutral-900">{{ $title }}</h1>
                @if($subtitle)<p class="mt-1 text-sm text-neutral-600">{{ $subtitle }}</p>@endif
                @if($helper)<p class="mt-1 text-xs text-neutral-500">{{ $helper }}</p>@endif
            </div>
        </div>
        {{ $slot }}
    </div>
</div>
