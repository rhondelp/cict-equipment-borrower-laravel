<div class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm sidebar-overlay md:hidden" style="display: none;"></div>

<div class="fixed inset-y-0 left-0 z-50 flex flex-col text-slate-700 sidebar w-80 bg-[#e2e8f0] border-r border-slate-300/40 shadow-xl transition-all duration-300">
    <div class="p-6">
        <div class="flex items-center space-x-3.5 p-3.5 rounded-2xl neu-flat border border-white/60">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl neu-pressed overflow-hidden p-1">
                <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <h1 class="text-sm font-bold tracking-tight text-slate-800 leading-tight">CICT Equipment</h1>
                <p class="text-[11px] font-medium text-slate-500">Management System</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-4 py-2 space-y-3 overflow-y-auto">
        @php
            $navActive = 'neu-pressed text-indigo-600 font-bold border border-slate-300/30';
            $navIdle = 'neu-btn text-slate-600 font-medium hover:text-slate-800';
        @endphp

        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? $navActive : $navIdle }}">
            <i class="fas fa-layout-dashboard w-5 text-center text-sm {{ request()->routeIs('admin.dashboard') ? 'text-indigo-600' : 'text-slate-400' }}"></i>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('admin.equipment') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs transition-all duration-200 {{ request()->routeIs('admin.equipment') ? $navActive : $navIdle }}">
            <i class="fas fa-tools w-5 text-center text-sm {{ request()->routeIs('admin.equipment') ? 'text-indigo-600' : 'text-slate-400' }}"></i>
            <span>Equipment</span>
        </a>

        <a href="{{ route('admin.users') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs transition-all duration-200 {{ request()->routeIs('admin.users') ? $navActive : $navIdle }}">
            <i class="fas fa-users w-5 text-center text-sm {{ request()->routeIs('admin.users') ? 'text-indigo-600' : 'text-slate-400' }}"></i>
            <span>Users</span>
        </a>

        <a href="{{ route('admin.transaction') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs transition-all duration-200 {{ request()->routeIs('admin.transaction') ? $navActive : $navIdle }}">
            <i class="fas fa-exchange-alt w-5 text-center text-sm {{ request()->routeIs('admin.transaction') ? 'text-indigo-600' : 'text-slate-400' }}"></i>
            <span>Borrow Transactions</span>
        </a>

        <a href="{{ route('admin.request') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs transition-all duration-200 {{ request()->routeIs('admin.request') ? $navActive : $navIdle }}">
            <i class="fas fa-clipboard-list w-5 text-center text-sm {{ request()->routeIs('admin.request') ? 'text-indigo-600' : 'text-slate-400' }}"></i>
            <span>Requests</span>
        </a>

        <a href="{{ route('admin.logs') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs transition-all duration-200 {{ request()->routeIs('admin.logs') ? $navActive : $navIdle }}">
            <i class="fas fa-book w-5 text-center text-sm {{ request()->routeIs('admin.logs') ? 'text-indigo-600' : 'text-slate-400' }}"></i>
            <span>Return Logs</span>
        </a>
    </nav>

    <div class="p-4 space-y-3">
        <div class="flex items-center gap-3 p-3 rounded-xl neu-flat border border-white/60">
            <img class="object-cover w-9 h-9 rounded-xl border border-slate-300" src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="Admin">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-slate-800 truncate">{{ Auth::user()->name }}</p>
                <p class="text-[10px] text-slate-500 font-medium truncate">{{ Auth::user()->email }}</p>
            </div>
            <div class="relative">
                <button id="settingsBtn" class="w-8 h-8 grid place-items-center rounded-xl neu-btn text-slate-500 hover:text-indigo-600 focus:outline-none">
                    <i class="fas fa-cog text-xs"></i>
                </button>
                <div id="logoutDropdown" class="absolute right-0 bottom-full mb-3 hidden w-44 neu-flat border border-white/80 shadow-2xl rounded-xl overflow-hidden p-1">
                    <form id="logoutForm" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="button" id="logoutBtn" class="w-full px-3 py-2 text-xs font-semibold text-slate-700 hover:text-red-600 hover:bg-slate-300/30 rounded-lg flex items-center gap-2 transition">
                            <i class="fas fa-sign-out-alt text-xs"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <button id="themeToggle" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl neu-btn text-xs font-semibold text-slate-600 focus:outline-none" aria-label="Toggle theme">
            <span class="flex items-center gap-2"><i class="fas fa-sun text-xs text-amber-500"></i> <span id="themeLabel">Light mode</span></span>
            <span class="w-8 h-4 rounded-full neu-pressed relative inline-block"><span id="themeKnob" class="absolute top-0.5 left-0.5 w-3 h-3 rounded-full bg-indigo-600 transition-all"></span></span>
        </button>
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
