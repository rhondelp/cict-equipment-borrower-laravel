<div class="fixed inset-0 z-40 bg-black/75 backdrop-blur-md sidebar-overlay md:hidden transition-opacity" style="display: none;"></div>

<div class="fixed inset-y-0 left-0 z-50 flex flex-col text-white sidebar w-80 bg-neutral-950 border-r border-white/10 shadow-2xl">
    <div class="p-6 border-b border-white/10">
        <div class="flex items-center space-x-3.5">
            <div class="flex items-center justify-center w-11 h-11 rounded-xl overflow-hidden bg-white/5 border border-white/10 p-1 shadow-inner">
                <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <h1 class="text-base font-bold tracking-tight text-white leading-tight">CICT Equipment</h1>
                <p class="text-xs font-medium text-neutral-400">Management System</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
        @php
            $navActive = 'active bg-primary-500/15 text-white font-semibold border border-primary-500/20 shadow-sm';
            $navIdle = 'text-neutral-400 hover:bg-white/[0.05] hover:text-neutral-200 font-medium';
        @endphp
        <a href="{{ route('admin.dashboard') }}" class="nav-item flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? $navActive : $navIdle }}">
            <i class="fas fa-layout-dashboard w-5 text-center text-base {{ request()->routeIs('admin.dashboard') ? 'text-primary-400' : 'text-neutral-500' }}"></i>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('admin.equipment') }}" class="nav-item flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm transition-all duration-200 {{ request()->routeIs('admin.equipment') ? $navActive : $navIdle }}">
            <i class="fas fa-tools w-5 text-center text-base {{ request()->routeIs('admin.equipment') ? 'text-primary-400' : 'text-neutral-500' }}"></i>
            <span>Equipment</span>
        </a>
        <a href="{{ route('admin.users') }}" class="nav-item flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm transition-all duration-200 {{ request()->routeIs('admin.users') ? $navActive : $navIdle }}">
            <i class="fas fa-users w-5 text-center text-base {{ request()->routeIs('admin.users') ? 'text-primary-400' : 'text-neutral-500' }}"></i>
            <span>Users</span>
        </a>
        <a href="{{ route('admin.transaction') }}" class="nav-item flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm transition-all duration-200 {{ request()->routeIs('admin.transaction') ? $navActive : $navIdle }}">
            <i class="fas fa-exchange-alt w-5 text-center text-base {{ request()->routeIs('admin.transaction') ? 'text-primary-400' : 'text-neutral-500' }}"></i>
            <span>Borrow Transactions</span>
        </a>
        <a href="{{ route('admin.request') }}" class="nav-item flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm transition-all duration-200 {{ request()->routeIs('admin.request') ? $navActive : $navIdle }}">
            <i class="fas fa-clipboard-list w-5 text-center text-base {{ request()->routeIs('admin.request') ? 'text-primary-400' : 'text-neutral-500' }}"></i>
            <span>Requests</span>
        </a>
        <a href="{{ route('admin.logs') }}" class="nav-item flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm transition-all duration-200 {{ request()->routeIs('admin.logs') ? $navActive : $navIdle }}">
            <i class="fas fa-book w-5 text-center text-base {{ request()->routeIs('admin.logs') ? 'text-primary-400' : 'text-neutral-500' }}"></i>
            <span>Return Logs</span>
        </a>
    </nav>

    <div class="p-4 border-t border-white/10">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-neutral-900/90 border border-white/10">
            <img class="object-cover w-10 h-10 rounded-xl border border-white/10 shrink-0" src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="Admin">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-white truncate leading-tight">{{ Auth::user()->name }}</p>
                <p class="text-xs text-neutral-400 truncate mt-0.5">{{ Auth::user()->email }}</p>
            </div>
            <div class="relative shrink-0">
                <button id="settingsBtn" aria-label="Account Settings" class="w-9 h-9 grid place-items-center rounded-lg bg-white/5 border border-white/10 text-neutral-400 hover:text-white hover:bg-white/10 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500/30">
                    <i class="fas fa-cog text-sm"></i>
                </button>
                <div id="logoutDropdown" class="absolute right-0 bottom-full mb-2 hidden w-48 bg-neutral-900 border border-white/10 shadow-2xl rounded-xl overflow-hidden z-50">
                    <form id="logoutForm" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="button" id="logoutBtn" class="w-full px-4 py-3 text-sm text-left font-medium text-neutral-200 hover:bg-white/10 transition-colors flex items-center gap-2.5">
                            <i class="fas fa-sign-out-alt text-sm text-neutral-400"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.getElementById('settingsBtn')?.addEventListener('click', function() {
        document.getElementById('logoutDropdown')?.classList.toggle('hidden');
    });
    document.addEventListener('click', function(e) {
        var btn = document.getElementById('settingsBtn');
        var dd = document.getElementById('logoutDropdown');
        if (!btn || !dd) return;
        if (!btn.contains(e.target) && !dd.contains(e.target)) dd.classList.add('hidden');
    });
    document.getElementById('logoutBtn')?.addEventListener('click', function(e) {
        e.preventDefault();
        (window.showConfirm ? window.showConfirm({ title: 'Are you sure?', text: 'You will be logged out!', icon: 'warning', confirmText: 'Yes, logout' }) : Swal.fire({
            title: 'Are you sure?',
            text: 'You will be logged out!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#1e293b',
            confirmButtonText: 'Yes, logout',
            background: '#131a2b',
            color: '#e2e8f0'
        })).then((result) => {
            if (result.isConfirmed) document.getElementById('logoutForm').submit();
        });
    });
</script>
@endpush
