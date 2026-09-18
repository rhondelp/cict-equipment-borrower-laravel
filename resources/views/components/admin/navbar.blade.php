<!-- Sidebar Overlay (mobile) -->
<div class="fixed inset-0 z-40 bg-neutral-900/50 backdrop-blur-sm sidebar-overlay md:hidden" style="display: none;"></div>

<!-- Sidebar — flat light theme -->
<aside class="fixed inset-y-0 left-0 z-50 flex flex-col w-64 transition-transform duration-200 transform -translate-x-full bg-white border-r border-neutral-200 text-neutral-900 sidebar md:translate-x-0">
    <!-- Header -->
    <div class="px-6 py-5 border-b border-neutral-200">
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 overflow-hidden bg-white border rounded-lg border-neutral-200 shrink-0">
                <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT" class="object-cover w-full h-full">
            </div>
            <div class="min-w-0">
                <h1 class="text-base font-semibold truncate text-neutral-900">CICT Equipment</h1>
                <p class="text-sm truncate text-neutral-600">Management System</p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <a href="{{ route('admin.dashboard') }}" class="nav-item flex items-center gap-2.5 border-l-4 px-3 py-3.5 rounded-lg text-lg leading-snug transition {{ request()->routeIs('admin.dashboard') ? 'bg-primary-50 text-primary-700 font-semibold border-primary-600' : 'border-transparent text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}">
            <i class="fas fa-gauge-high w-6 shrink-0 text-center text-lg {{ request()->routeIs('admin.dashboard') ? 'text-primary-600' : 'text-neutral-600' }}"></i>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('admin.equipment') }}" class="nav-item flex items-center gap-2.5 border-l-4 px-3 py-3.5 rounded-lg text-lg leading-snug transition {{ request()->routeIs('admin.equipment') ? 'bg-primary-50 text-primary-700 font-semibold border-primary-600' : 'border-transparent text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}">
            <i class="fas fa-tools w-6 shrink-0 text-center text-lg {{ request()->routeIs('admin.equipment') ? 'text-primary-600' : 'text-neutral-600' }}"></i>
            <span>Equipment</span>
        </a>
        <a href="{{ route('admin.users') }}" class="nav-item flex items-center gap-2.5 border-l-4 px-3 py-3.5 rounded-lg text-lg leading-snug transition {{ request()->routeIs('admin.users') ? 'bg-primary-50 text-primary-700 font-semibold border-primary-600' : 'border-transparent text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}">
            <i class="fas fa-users w-6 shrink-0 text-center text-lg {{ request()->routeIs('admin.users') ? 'text-primary-600' : 'text-neutral-600' }}"></i>
            <span>Users</span>
        </a>
        <a href="{{ route('admin.transaction') }}" class="nav-item flex items-center gap-2.5 border-l-4 px-3 py-3.5 rounded-lg text-lg leading-snug transition {{ request()->routeIs('admin.transaction') ? 'bg-primary-50 text-primary-700 font-semibold border-primary-600' : 'border-transparent text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}">
            <i class="fas fa-exchange-alt w-6 shrink-0 text-center text-lg {{ request()->routeIs('admin.transaction') ? 'text-primary-600' : 'text-neutral-600' }}"></i>
            <span>Borrow Transactions</span>
        </a>
        <a href="{{ route('admin.request') }}" class="nav-item flex items-center gap-2.5 border-l-4 px-3 py-3.5 rounded-lg text-lg leading-snug transition {{ request()->routeIs('admin.request') ? 'bg-primary-50 text-primary-700 font-semibold border-primary-600' : 'border-transparent text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}">
            <i class="fas fa-clipboard-list w-6 shrink-0 text-center text-lg {{ request()->routeIs('admin.request') ? 'text-primary-600' : 'text-neutral-600' }}"></i>
            <span>Requests</span>
        </a>
        <a href="{{ route('admin.logs') }}" class="nav-item flex items-center gap-2.5 border-l-4 px-3 py-3.5 rounded-lg text-lg leading-snug transition {{ request()->routeIs('admin.logs') ? 'bg-primary-50 text-primary-700 font-semibold border-primary-600' : 'border-transparent text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}">
            <i class="fas fa-book w-6 shrink-0 text-center text-lg {{ request()->routeIs('admin.logs') ? 'text-primary-600' : 'text-neutral-600' }}"></i>
            <span>Return Logs</span>
        </a>
    </nav>

    <!-- User Profile -->
    <div class="p-4 border-t border-neutral-200">
        <div class="flex items-center gap-3 p-2 border rounded-lg bg-neutral-50 border-neutral-200">
            <img class="object-cover w-10 h-10 border rounded-lg border-neutral-200" src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="Admin">
            <div class="flex-1 min-w-0">
                <p class="text-base font-semibold truncate text-neutral-900" title="{{ Auth::user()->name }}">{{ Auth::user()->name }}</p>
                <p class="text-sm truncate text-neutral-600" title="{{ Auth::user()->email }}">{{ Auth::user()->email }}</p>
            </div>
            <div class="relative">
                <button id="settingsBtn" class="grid w-10 h-10 transition bg-white border rounded-md place-items-center border-neutral-200 text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100" aria-label="Open menu">
                    <i class="text-base fas fa-cog"></i>
                </button>
                <div id="logoutDropdown" class="absolute right-0 hidden w-48 mb-2 overflow-hidden bg-white border rounded-lg bottom-full border-neutral-200">
                    <form id="logoutForm" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="button" id="logoutBtn" class="w-full min-h-[44px] px-4 py-3 text-base text-left text-neutral-700 hover:bg-neutral-100 flex items-center gap-2">
                            <i class="text-base fas fa-sign-out-alt text-neutral-600"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Plainly labelled sign-out, so it no longer hides behind the gear icon.
             Submits the existing #logoutForm via the existing POST /logout route. --}}
        {{-- <button type="button" id="sidebarLogoutBtn"
                class="flex items-center w-full gap-2.5 px-3 py-3.5 mt-3 text-lg font-semibold transition border rounded-lg bg-danger-50 text-danger-700 border-danger-200 hover:bg-danger-100">
            <i class="w-6 text-lg text-center shrink-0 fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </button> --}}
    </div>
</aside>

@push('scripts')
<script>
    document.getElementById('settingsBtn')?.addEventListener('click', function () {
        document.getElementById('logoutDropdown')?.classList.toggle('hidden');
    });
    document.addEventListener('click', function (e) {
        var btn = document.getElementById('settingsBtn');
        var dd  = document.getElementById('logoutDropdown');
        if (!btn || !dd) return;
        if (!btn.contains(e.target) && !dd.contains(e.target)) dd.classList.add('hidden');
    });
    // Shared by the gear-menu item and the sidebar's labelled Logout button, so
    // both confirm and submit the same #logoutForm. Behaviour is unchanged.
    var confirmLogout = function (e) {
        e.preventDefault();
        // window.showConfirm is loaded app-wide by resources/js/alert.js,
        // which is bundled into resources/js/app.js and loaded on every
        // page through the master layout. No fallback shim needed.
        window.showConfirm({
            title: 'Are you sure?',
            text: 'You will be logged out!',
            icon: 'warning',
            confirmText: 'Yes, logout'
        }).then(function (result) {
            if (result.isConfirmed) document.getElementById('logoutForm').submit();
        });
    };
    document.getElementById('logoutBtn')?.addEventListener('click', confirmLogout);
    document.getElementById('sidebarLogoutBtn')?.addEventListener('click', confirmLogout);
</script>
@endpush
