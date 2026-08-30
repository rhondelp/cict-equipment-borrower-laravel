<!-- Sidebar Overlay -->
<div class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm sidebar-overlay md:hidden" style="display: none;"></div>

<!-- Sidebar — dark navy -->
<div class="fixed inset-y-0 left-0 z-50 flex flex-col text-white sidebar w-80" style="background:#0c1222; border-right:1px solid rgba(255,255,255,0.06)">
    <!-- Header -->
    <div class="p-6 border-b border-white/5">
        <div class="flex items-center space-x-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl overflow-hidden bg-white/5 border border-white/10">
                <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="" class="w-full h-full object-cover">
            </div>
            <div>
                <h1 class="text-[15px] font-bold tracking-tight text-white">CICT Equipment</h1>
                <p class="text-xs text-[#8b93a8]">Management System</p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
        @php
            $navActive = 'active bg-[rgba(59,130,246,0.12)] text-white';
            $navIdle = 'text-[#8b93a8] hover:bg-white/[0.04] hover:text-slate-200';
        @endphp
        <a href="{{ route('admin.dashboard') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition {{ request()->routeIs('admin.dashboard') ? $navActive : $navIdle }}">
            <i class="fas fa-layout-dashboard w-5 text-center text-[14px] {{ request()->routeIs('admin.dashboard') ? 'text-[#60a5fa]' : 'text-[#6b7a99]' }}"></i>
            <span class="font-medium">Dashboard</span>
        </a>
        <a href="{{ route('admin.equipment') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition {{ request()->routeIs('admin.equipment') ? $navActive : $navIdle }}">
            <i class="fas fa-tools w-5 text-center text-[14px] {{ request()->routeIs('admin.equipment') ? 'text-[#60a5fa]' : 'text-[#6b7a99]' }}"></i>
            <span class="font-medium">Equipment</span>
        </a>
        <a href="{{ route('admin.users') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition {{ request()->routeIs('admin.users') ? $navActive : $navIdle }}">
            <i class="fas fa-users w-5 text-center text-[14px] {{ request()->routeIs('admin.users') ? 'text-[#60a5fa]' : 'text-[#6b7a99]' }}"></i>
            <span class="font-medium">Users</span>
        </a>
        <a href="{{ route('admin.transaction') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition {{ request()->routeIs('admin.transaction') ? $navActive : $navIdle }}">
            <i class="fas fa-exchange-alt w-5 text-center text-[14px] {{ request()->routeIs('admin.transaction') ? 'text-[#60a5fa]' : 'text-[#6b7a99]' }}"></i>
            <span class="font-medium">Borrow Transactions</span>
        </a>
        <a href="{{ route('admin.request') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition {{ request()->routeIs('admin.request') ? $navActive : $navIdle }}">
            <i class="fas fa-clipboard-list w-5 text-center text-[14px] {{ request()->routeIs('admin.request') ? 'text-[#60a5fa]' : 'text-[#6b7a99]' }}"></i>
            <span class="font-medium">Requests</span>
        </a>
        <a href="{{ route('admin.logs') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition {{ request()->routeIs('admin.logs') ? $navActive : $navIdle }}">
            <i class="fas fa-book w-5 text-center text-[14px] {{ request()->routeIs('admin.logs') ? 'text-[#60a5fa]' : 'text-[#6b7a99]' }}"></i>
            <span class="font-medium">Return Logs</span>
        </a>
    </nav>

    <!-- User Profile -->
    <div class="p-4 border-t border-white/5">
        <div class="flex items-center gap-3 p-3 rounded-xl" style="background:#131a2b; border:1px solid rgba(255,255,255,0.06)">
            <img class="object-cover w-9 h-9 rounded-xl border border-white/10" src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="Admin">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-[#8b93a8] truncate">{{ Auth::user()->email }}</p>
            </div>
            <div class="relative">
                <button id="settingsBtn" class="w-8 h-8 grid place-items-center rounded-lg bg-white/5 border border-white/10 text-[#8b93a8] hover:text-white hover:bg-white/10 transition">
                    <i class="fas fa-cog text-xs"></i>
                </button>
                <div id="logoutDropdown" class="absolute right-0 bottom-full mb-2 hidden w-44 bg-[#131a2b] border border-white/10 shadow-xl rounded-xl overflow-hidden">
                    <form id="logoutForm" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="button" id="logoutBtn" class="w-full px-4 py-2.5 text-sm text-left text-slate-200 hover:bg-white/5 flex items-center gap-2">
                            <i class="fas fa-sign-out-alt text-xs text-[#8b93a8]"></i> Logout
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
