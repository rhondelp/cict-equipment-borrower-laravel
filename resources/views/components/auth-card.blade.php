{{-- Shared auth-card wrapper — provides theme-shell + centered card + header block --}}
@props(['title' => '', 'subtitle' => '', 'helper' => '', 'logo' => 'https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png'])

<div class="theme-shell">
    <div class="auth-card animate-fade-in">
        <div class="flex items-start gap-3 mb-6">
            <div class="auth-logo shrink-0">
                <img src="{{ $logo }}" alt="CICT Logo">
            </div>
            <div class="pt-1">
                <h1 class="auth-title">{{ $title }}</h1>
                @if($subtitle)<p class="auth-subtitle">{{ $subtitle }}</p>@endif
                @if($helper)<p class="auth-helper">{{ $helper }}</p>@endif
            </div>
        </div>
        {{ $slot }}
    </div>
</div>
