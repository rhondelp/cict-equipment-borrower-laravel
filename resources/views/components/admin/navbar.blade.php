<!-- Sidebar Overlay (mobile) -->
<div class="fixed inset-0 z-40 bg-neutral-900/50 backdrop-blur-sm sidebar-overlay md:hidden" style="display: none;"></div>

<!-- Sidebar — flat light theme -->
<aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-neutral-200 flex flex-col text-neutral-900 sidebar transform -translate-x-full md:translate-x-0 transition-transform duration-200">
    <!-- Header -->
    <div class="px-6 py-5 border-b border-neutral-200">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-white border border-neutral-200 flex items-center justify-center overflow-hidden shrink-0">
                <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT" class="w-full h-full object-cover">
            </div>
            <div class="min-w-0">
                <h1 class="text-sm font-semibold tracking-tight text-neutral-900 truncate">CICT Equipment</h1>
                <p class="text-xs text-neutral-500 truncate">Management System</p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <a href="{{ route('admin.dashboard') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('admin.dashboard') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}">
            <i class="fas fa-layout-dashboard w-5 text-center text-sm {{ request()->routeIs('admin.dashboard') ? 'text-primary-600' : 'text-neutral-400' }}"></i>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('admin.equipment') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('admin.equipment') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}">
            <i class="fas fa-tools w-5 text-center text-sm {{ request()->routeIs('admin.equipment') ? 'text-primary-600' : 'text-neutral-400' }}"></i>
            <span>Equipment</span>
        </a>
        <a href="{{ route('admin.users') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('admin.users') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}">
            <i class="fas fa-users w-5 text-center text-sm {{ request()->routeIs('admin.users') ? 'text-primary-600' : 'text-neutral-400' }}"></i>
            <span>Users</span>
        </a>
        <a href="{{ route('admin.transaction') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('admin.transaction') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}">
            <i class="fas fa-exchange-alt w-5 text-center text-sm {{ request()->routeIs('admin.transaction') ? 'text-primary-600' : 'text-neutral-400' }}"></i>
            <span>Borrow Transactions</span>
        </a>
        <a href="{{ route('admin.request') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('admin.request') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}">
            <i class="fas fa-clipboard-list w-5 text-center text-sm {{ request()->routeIs('admin.request') ? 'text-primary-600' : 'text-neutral-400' }}"></i>
            <span>Requests</span>
        </a>
        <a href="{{ route('admin.logs') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('admin.logs') ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900' }}">
            <i class="fas fa-book w-5 text-center text-sm {{ request()->routeIs('admin.logs') ? 'text-primary-600' : 'text-neutral-400' }}"></i>
            <span>Return Logs</span>
        </a>
    </nav>

    <!-- User Profile -->
    <div class="p-4 border-t border-neutral-200">
        <div class="flex items-center gap-3 p-2 rounded-lg bg-neutral-50 border border-neutral-200">
            <img class="w-9 h-9 rounded-lg object-cover border border-neutral-200" src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="Admin">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-neutral-900 truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-neutral-500 truncate">{{ Auth::user()->email }}</p>
            </div>
            <div class="relative">
                <button id="settingsBtn" class="w-9 h-9 grid place-items-center rounded-md bg-white border border-neutral-200 text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 transition" aria-label="Open menu">
                    <i class="fas fa-cog text-xs"></i>
                </button>
                <div id="logoutDropdown" class="absolute right-0 bottom-full mb-2 hidden w-44 bg-white border border-neutral-200 shadow-flat rounded-lg overflow-hidden">
                    <form id="logoutForm" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="button" id="logoutBtn" class="w-full px-4 py-2.5 text-sm text-left text-neutral-700 hover:bg-neutral-100 flex items-center gap-2">
                            <i class="fas fa-sign-out-alt text-xs text-neutral-400"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
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
    document.getElementById('logoutBtn')?.addEventListener('click', function (e) {
        e.preventDefault();
        (window.showConfirm ? window.showConfirm({
            title: 'Are you sure?',
            text: 'You will be logged out!',
            icon: 'warning',
            confirmText: 'Yes, logout'
        }) : Swal.fire({
            title: 'Are you sure?',
            text: 'You will be logged out!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#e2e8f0',
            confirmButtonText: 'Yes, logout',
            background: '#ffffff',
            color: '#0f172a',
            customClass: { popup: 'rounded-lg border border-neutral-200 shadow-sm' }
        })).then(function (result) {
            if (result.isConfirmed) document.getElementById('logoutForm').submit();
        });
    });
</script>
@endpush
