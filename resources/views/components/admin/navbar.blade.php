@php
    // $pendingRequests is shared by the view composer in AppServiceProvider::boot().
    $navGroups = [
        'Overview' => [
            ['route' => 'admin.dashboard',   'icon' => 'dashboard',   'label' => 'Dashboard'],
            ['route' => 'admin.equipment',   'icon' => 'equipment',   'label' => 'Equipment'],
            ['route' => 'admin.users',       'icon' => 'users',       'label' => 'Users'],
        ],
        'Circulation' => [
            ['route' => 'admin.transaction', 'icon' => 'transaction', 'label' => 'Borrow Transactions'],
            ['route' => 'admin.request',     'icon' => 'request',     'label' => 'Requests', 'badge' => $pendingRequests ?? 0],
            ['route' => 'admin.logs',        'icon' => 'logs',        'label' => 'Return Logs'],
        ],
    ];

    $user = Auth::user();
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
@endphp

<!-- Sidebar Overlay (mobile) -->
<div class="fixed inset-0 z-overlay bg-neutral-900/50 backdrop-blur-sm sidebar-overlay md:hidden" style="display: none;"></div>

<!-- Sidebar — flat light theme -->
<aside class="fixed inset-y-0 left-0 z-modal flex flex-col w-64 transition-transform duration-200 transform -translate-x-full bg-white border-r border-neutral-200 text-neutral-900 sidebar md:translate-x-0 px-3">
    <!-- Header -->
    <div class="flex items-center h-[114.59375px] -mx-3 px-6 border-b border-neutral-200 shrink-0">
        <div class="flex items-center min-w-0 gap-3">
            <div class="flex items-center justify-center overflow-hidden bg-white border rounded-lg w-9 h-9 border-neutral-200 shrink-0">
                <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT" class="object-cover w-full h-full">
            </div>
            <div class="min-w-0">
                <h1 class="text-sm font-semibold truncate text-neutral-900">CICT Equipment</h1>
                <p class="text-xs truncate text-neutral-500">Management System</p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 py-2 overflow-y-auto">
        @foreach ($navGroups as $group => $links)
            <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-neutral-400 px-3 {{ $loop->first ? 'pt-5' : 'pt-6' }} pb-2">{{ $group }}</p>
            <div class="space-y-0.5">
                @foreach ($links as $link)
                    @php $active = request()->routeIs($link['route'], $link['route'].'.*'); @endphp
                    <a href="{{ route($link['route']) }}"
                       @if ($active) aria-current="page" @endif
                       class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition relative {{ $active ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900' }}">
                        @if ($active)
                            <span class="absolute left-0 top-2 bottom-2 w-[3px] rounded-r bg-primary-600"></span>
                        @endif
                        <x-icon :name="$link['icon']" class="{{ $active ? 'text-primary-600' : 'text-neutral-400 group-hover:text-neutral-600' }}" />
                        <span>{{ $link['label'] }}</span>
                        @if (($link['badge'] ?? 0) > 0)
                            <span class="ml-auto rounded-full bg-red-600 px-1.5 py-0.5 text-[10.5px] font-semibold leading-none text-white">{{ $link['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <!-- User Profile -->
    <div class="p-3">
        <div class="flex items-center gap-3 p-2 border rounded-xl border-neutral-200">
            <div class="grid w-8 h-8 text-xs font-semibold rounded-full bg-primary-100 text-primary-700 place-items-center shrink-0" aria-hidden="true">{{ $initials }}</div>
            <div class="flex-1 min-w-0">
                <p class="text-[13px] font-semibold truncate text-neutral-900" title="{{ $user->name }}">{{ $user->name }}</p>
                <p class="text-[11px] truncate text-neutral-500" title="{{ $user->email }}">{{ $user->user_type }}</p>
            </div>
            <div class="relative">
                <button type="button" id="settingsBtn" class="grid w-8 h-8 transition rounded-md place-items-center text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100" aria-label="Open menu" aria-haspopup="true" aria-expanded="false" aria-controls="logoutDropdown">
                    <i class="text-sm fas fa-ellipsis-vertical"></i>
                </button>
                <div id="logoutDropdown" class="absolute right-0 hidden w-48 mb-2 overflow-hidden bg-white border rounded-lg bottom-full border-neutral-200 shadow-pop">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full min-h-[44px] px-4 py-3 text-sm text-left text-neutral-700 hover:bg-neutral-100 flex items-center gap-2">
                            <i class="text-sm fas fa-sign-out-alt text-neutral-600"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</aside>

@push('scripts')
<script>
    (function () {
        var btn = document.getElementById('settingsBtn');
        var dd  = document.getElementById('logoutDropdown');
        if (!btn || !dd) return;

        var setOpen = function (open) {
            dd.classList.toggle('hidden', !open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        btn.addEventListener('click', function () {
            setOpen(dd.classList.contains('hidden'));
        });
        document.addEventListener('click', function (e) {
            if (!btn.contains(e.target) && !dd.contains(e.target)) setOpen(false);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !dd.classList.contains('hidden')) {
                setOpen(false);
                btn.focus();
            }
        });
    })();
</script>
@endpush
