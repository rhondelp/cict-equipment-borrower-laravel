<!-- Sidebar Overlay -->
<div class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm sidebar-overlay md:hidden" style="display: none;"></div>

<!-- Sidebar — Refined Light Palette -->
<aside class="fixed inset-y-0 left-0 z-50 flex flex-col sidebar w-72 bg-white border-r border-slate-200 shadow-lg md:shadow-none transition-transform duration-300">
    <!-- Brand Header -->
    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center p-1 shadow-sm shrink-0">
                <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="CICT Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <h1 class="text-sm font-bold tracking-tight text-slate-800 font-sans leading-tight">CICT Equipment</h1>
                <p class="text-[11px] font-medium text-slate-500">Management System</p>
            </div>
        </div>
        <button id="sidebar-close-btn" class="md:hidden text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition">
            <i class="fas fa-times text-sm"></i>
        </button>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto">
        <div class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">Main Menu</div>

        @php
            $navActive = 'bg-blue-600 text-white shadow-md shadow-blue-600/25 font-semibold';
            $navIdle = 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 font-medium';
        @endphp

        <a href="{{ route('admin.dashboard') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs transition duration-150 {{ request()->routeIs('admin.dashboard') ? $navActive : $navIdle }}">
            <div class="w-7 h-7 rounded-lg flex items-center justify-center {{ request()->routeIs('admin.dashboard') ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                <i class="fas fa-table-cells-large text-xs"></i>
            </div>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('admin.equipment') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs transition duration-150 {{ request()->routeIs('admin.equipment') ? $navActive : $navIdle }}">
            <div class="w-7 h-7 rounded-lg flex items-center justify-center {{ request()->routeIs('admin.equipment') ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                <i class="fas fa-box-archive text-xs"></i>
            </div>
            <span>Equipment</span>
        </a>

        <a href="{{ route('admin.users') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs transition duration-150 {{ request()->routeIs('admin.users') ? $navActive : $navIdle }}">
            <div class="w-7 h-7 rounded-lg flex items-center justify-center {{ request()->routeIs('admin.users') ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                <i class="fas fa-users-gear text-xs"></i>
            </div>
            <span>Users</span>
        </a>

        <a href="{{ route('admin.transaction') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs transition duration-150 {{ request()->routeIs('admin.transaction') ? $navActive : $navIdle }}">
            <div class="w-7 h-7 rounded-lg flex items-center justify-center {{ request()->routeIs('admin.transaction') ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                <i class="fas fa-arrow-right-arrow-left text-xs"></i>
            </div>
            <span>Borrow Transactions</span>
        </a>

        <a href="{{ route('admin.request') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs transition duration-150 {{ request()->routeIs('admin.request') ? $navActive : $navIdle }}">
            <div class="w-7 h-7 rounded-lg flex items-center justify-center {{ request()->routeIs('admin.request') ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                <i class="fas fa-clipboard-list text-xs"></i>
            </div>
            <span>Requests</span>
        </a>

        <a href="{{ route('admin.logs') }}" class="nav-item flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs transition duration-150 {{ request()->routeIs('admin.logs') ? $navActive : $navIdle }}">
            <div class="w-7 h-7 rounded-lg flex items-center justify-center {{ request()->routeIs('admin.logs') ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                <i class="fas fa-clock-rotate-left text-xs"></i>
            </div>
            <span>Return Logs</span>
        </a>
    </nav>

    <!-- User Profile & Quick Actions -->
    <div class="p-3 border-t border-slate-100 bg-slate-50/70">
        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-white border border-slate-200/80 shadow-sm">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 text-white flex items-center justify-center font-bold text-xs shadow-sm shrink-0">
                {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-slate-800 truncate">{{ Auth::user()->name ?? 'Admin User' }}</p>
                <p class="text-[11px] text-slate-500 truncate">{{ Auth::user()->email ?? 'admin@cict.edu.ph' }}</p>
            </div>
            <div class="relative">
                <button id="settingsBtn" class="w-7 h-7 grid place-items-center rounded-lg bg-slate-100 text-slate-500 hover:text-slate-800 hover:bg-slate-200 transition" title="Account settings">
                    <i class="fas fa-ellipsis-vertical text-xs"></i>
                </button>
                <div id="logoutDropdown" class="absolute right-0 bottom-full mb-2 hidden w-44 bg-white border border-slate-200 shadow-xl rounded-xl overflow-hidden py-1 z-50">
                    <form id="logoutForm" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="button" id="logoutBtn" class="w-full px-3.5 py-2 text-xs text-left text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium transition">
                            <i class="fas fa-arrow-right-from-bracket text-xs text-rose-500"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</aside>
@push('scripts')
<script>
    document.getElementById('settingsBtn')?.addEventListener('click', function() {
        document.getElementById('logoutDropdown')?.classList.toggle('hidden');
    });
    document.getElementById('sidebar-close-btn')?.addEventListener('click', function() {
        document.querySelector('.sidebar')?.classList.remove('active');
        const overlay = document.querySelector('.sidebar-overlay');
        if (overlay) overlay.style.display = 'none';
        document.body.style.overflow = '';
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
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, logout',
            background: '#ffffff',
            color: '#0f172a'
        })).then((result) => {
            if (result.isConfirmed) document.getElementById('logoutForm').submit();
        });
    });
</script>
@endpush
