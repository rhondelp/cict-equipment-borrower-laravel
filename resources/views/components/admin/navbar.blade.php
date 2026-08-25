<!-- Sidebar Overlay -->
<div class="fixed inset-0 z-40 bg-black bg-opacity-50 sidebar-overlay md:hidden" style="display: none;"></div>

<!-- Sidebar -->
<div class="fixed inset-y-0 left-0 z-50 flex flex-col text-white bg-gray-900 sidebar w-80">
    <!-- Header -->
    <div class="p-6 border-b border-gray-700">
        <div class="flex items-center space-x-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl">
                {{-- <i class="text-lg text-white fas fa-laptop-code"></i> --}}
                <img src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="">
            </div>
            <div>
                <h1 class="text-xl font-bold text-white">CICT Equipment</h1>
                <p class="text-sm text-gray-400">Management System</p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-2">
        <a href="{{ route('admin.dashboard') }}" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-gray-800 hover:text-white {{ request()->is('equipment*') ? 'active bg-gray-800 text-white' : 'text-gray-300' }}">
            <div class="w-6 text-center">
                <i class="fas fa-dashboard {{ request()->is('equipment*') ? 'text-blue-400' : 'text-gray-400' }}"></i>
            </div>
            <span class="font-medium">Dashboard</span>
        </a>
        <a href="{{ route('admin.equipment') }}" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-gray-800 hover:text-white {{ request()->is('equipment*') ? 'active bg-gray-800 text-white' : 'text-gray-300' }}">
            <div class="w-6 text-center">
                <i class="fas fa-tools {{ request()->is('equipment*') ? 'text-blue-400' : 'text-gray-400' }}"></i>
            </div>
            <span class="font-medium">Equipment</span>
        </a>

        <a href="{{ route('admin.users') }}" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-gray-800 hover:text-white {{ request()->is('users*') ? 'active bg-gray-800 text-white' : 'text-gray-300' }}">
            <div class="w-6 text-center">
                <i class="fas fa-users {{ request()->is('users*') ? 'text-blue-400' : 'text-gray-400' }}"></i>
            </div>
            <span class="font-medium">Users</span>
        </a>

        <a href="{{ route('admin.transaction') }}" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-gray-800 hover:text-white {{ request()->is('transaction*') ? 'active bg-gray-800 text-white' : 'text-gray-300' }}">
            <div class="w-6 text-center">
                <i class="fas fa-exchange-alt {{ request()->is('transaction*') ? 'text-blue-400' : 'text-gray-400' }}"></i>
            </div>
            <span class="font-medium">Borrow Transactions</span>
        </a>

        <a href="{{ route('admin.request') }}" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-gray-800 hover:text-white {{ request()->is('request*') ? 'active bg-gray-800 text-white' : 'text-gray-300' }}">
            <div class="w-6 text-center">
                <i class="fas fa-clipboard-list {{ request()->is('request*') ? 'text-blue-400' : 'text-gray-400' }}"></i>
            </div>
            <span class="font-medium">Requests</span>
            {{-- <span class="px-2 py-1 ml-auto text-xs text-white bg-blue-500 rounded-full">12</span> --}}
        </a>

        {{-- <a href="{{ route('admin.notifications') }}" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-gray-800 hover:text-white {{ request()->is('notification*') ? 'active bg-gray-800 text-white' : 'text-gray-300' }}">
            <div class="w-6 text-center">
                <i class="fas fa-bell {{ request()->is('notification*') ? 'text-blue-400' : 'text-gray-400' }}"></i>
            </div>
            <span class="font-medium">Notifications</span> --}}
            {{-- <span class="px-2 py-1 ml-auto text-xs text-white bg-red-500 rounded-full">{{ $countNotifs }}</span> --}}
        {{-- </a> --}}

        <a href="{{ route('admin.logs') }}" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-gray-800 hover:text-white {{ request()->is('notification*') ? 'active bg-gray-800 text-white' : 'text-gray-300' }}">
            <div class="w-6 text-center">
                <i class="fas fa-book {{ request()->is('notification*') ? 'text-blue-400' : 'text-gray-400' }}"></i>
            </div>
            <span class="font-medium">Return Logs</span>
            {{-- <span class="px-2 py-1 ml-auto text-xs text-white bg-red-500 rounded-full">{{ $countNotifs }}</span> --}}
        </a>
    </nav>

    <!-- User Profile -->
    <div class="p-4 border-t border-gray-700">
        <div class="flex items-center p-3 space-x-3 bg-gray-800 rounded-xl">
            <img class="object-cover w-10 h-10 rounded-xl" src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png" alt="Admin">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</p>
            </div>

            <div class="relative">
                <button id="settingsBtn" class="text-gray-400 transition-colors duration-200 hover:text-white focus:outline-none">
                    <i class="fas fa-cog"></i>
                </button>
                <div id="logoutDropdown" class="absolute right-0 z-50 hidden w-40 mt-2 bg-gray-800 shadow-lg rounded-xl">
                    <form id="logoutForm" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="button" id="logoutBtn" class="block w-full px-4 py-2 text-sm text-left text-gray-300 hover:bg-gray-700 hover:text-white rounded-xl">
                            <i class="mr-2 fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.getElementById('settingsBtn').addEventListener('click', function() {
        var dropdown = document.getElementById('logoutDropdown');
        dropdown.classList.toggle('hidden');
    });
    document.addEventListener('click', function(e) {
        var btn = document.getElementById('settingsBtn');
        var dropdown = document.getElementById('logoutDropdown');
        if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Are you sure?',
            text: 'You will be logged out!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, logout'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('logoutForm').submit();
            }
        });
    });
</script>
@endpush
