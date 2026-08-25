@extends("components.default")

@section("title", "Dashboard - CICT Equipment Borrower System")

@section("content")
@include('components.admin.navbar')
<div class="min-h-screen bg-gray-50 md:ml-80">

    <!-- Top Header -->
    <header class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center space-x-4">
                <button id="menu-toggle" class="text-gray-500 hover:text-gray-700 md:hidden">
                    <i class="text-xl fas fa-bars"></i>
                </button>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">DASHBOARD</h1>
                    <p class="text-sm text-gray-500">Welcome back, Admin! Here's your overview.</p>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <!-- Search Bar -->
                <div class="relative hidden md:block">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="text-gray-400 fas fa-search"></i>
                    </div>
                    <input type="text"
                        class="w-64 py-2 pl-10 pr-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Search...">
                </div>

                <!-- Notifications -->
                <div class="relative">
                    <button class="relative p-2 text-gray-500 transition-colors duration-200 hover:text-gray-700">
                        <i class="text-xl fas fa-bell"></i>
                        <span
                            class="absolute flex items-center justify-center w-5 h-5 text-xs text-white bg-red-500 rounded-full -top-1 -right-1">3</span>
                    </button>
                </div>

                <!-- User Menu -->
                <div class="flex items-center space-x-3">
                    <img class="object-cover border-2 border-blue-500 h-9 w-9 rounded-xl"
                        src="https://ui-avatars.com/api/?name=Admin+User&background=0D8ABC&color=fff&bold=true"
                        alt="Admin">
                    <div class="hidden md:block">
                        <p class="text-sm font-semibold text-gray-900">Admin User</p>
                        <p class="text-xs text-gray-500">Administrator</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="p-6">

        <!-- Quick Stats (Compact Layout) -->
        <section class="mb-6">
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <!-- Total Equipment -->
                <div
                    class="flex items-center justify-between p-4 transition border border-blue-100 bg-blue-50 rounded-xl hover:bg-blue-100">
                    <div>
                        <p class="text-xs font-medium text-gray-600">Total Equipment</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $equipments->count() }}</p>
                    </div>
                    <div class="flex items-center justify-center w-10 h-10 bg-blue-100 rounded-lg">
                        <i class="text-lg text-blue-500 fas fa-tools"></i>
                    </div>
                </div>

                <!-- Active Users -->
                <div
                    class="flex items-center justify-between p-4 transition border border-green-100 bg-green-50 rounded-xl hover:bg-green-100">
                    <div>
                        <p class="text-xs font-medium text-gray-600">Active Users</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $users->count() }}</p>
                    </div>
                    <div class="flex items-center justify-center w-10 h-10 bg-green-100 rounded-lg">
                        <i class="text-lg text-green-500 fas fa-users"></i>
                    </div>
                </div>

                <!-- Active Transactions -->
                <div
                    class="flex items-center justify-between p-4 transition border border-purple-100 bg-purple-50 rounded-xl hover:bg-purple-100">
                    <div>
                        <p class="text-xs font-medium text-gray-600">Transactions</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $transactions->count() }}</p>
                    </div>
                    <div class="flex items-center justify-center w-10 h-10 bg-purple-100 rounded-lg">
                        <i class="text-lg text-purple-500 fas fa-exchange-alt"></i>
                    </div>
                </div>

                <!-- Pending Requests -->
                <div
                    class="flex items-center justify-between p-4 transition border border-orange-100 bg-orange-50 rounded-xl hover:bg-orange-100">
                    <div>
                        <p class="text-xs font-medium text-gray-600">Total Requests</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $requests->count() }}</p>
                    </div>
                    <div class="flex items-center justify-center w-10 h-10 bg-orange-100 rounded-lg">
                        <i class="text-lg text-orange-500 fas fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
        </section>


        <!-- Charts and Activity Section -->
        <div class="w-full px-4 py-4">
            <!-- Recent Activity -->
            <div class="w-full p-6 bg-white border border-gray-200 rounded-2xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Return Logs</h3>
                    <a href="{{ route('admin.logs') }}"
                        class="text-sm font-medium text-blue-500 hover:text-blue-700">View All</a>
                </div>
                @foreach ($returnLogs as $returnLog)
                <div class="flex items-start mb-4 space-x-3">
                    <div class="flex items-center justify-center flex-shrink-0 w-8 h-8 mt-1 bg-blue-100 rounded-lg">
                        <i class="text-sm text-blue-500 fas fa-undo"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">
                            {{ $returnLog->equipment->equipment_name ?? 'N/A' }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Borrowed by: {{ $returnLog->borrower->name ?? 'N/A' }}<br>
                            Received by: {{ $returnLog->receiver->name ?? 'N/A' }} •
                            {{ $returnLog->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
                <hr class="my-2">
                @endforeach
            </div>
        </div>


        <!-- Quick Actions & System Status -->
        {{-- <div class="w-full px-4 py-4">
            <!-- Quick Actions -->
            <div class="p-6 bg-white border border-gray-200 rounded-2xl">
                <h3 class="mb-6 text-lg font-semibold text-gray-900">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-4">
                    <button
                        class="flex flex-col items-center justify-center p-4 transition-all duration-200 border border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 group">
                        <div
                            class="flex items-center justify-center w-10 h-10 mb-2 transition-colors duration-200 bg-blue-100 rounded-lg group-hover:bg-blue-500">
                            <i class="text-blue-500 fas fa-plus group-hover:text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Add Equipment</span>
                    </button>
                    <button
                        class="flex flex-col items-center justify-center p-4 transition-all duration-200 border border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 group">
                        <div
                            class="flex items-center justify-center w-10 h-10 mb-2 transition-colors duration-200 bg-blue-100 rounded-lg group-hover:bg-blue-500">
                            <i class="text-blue-500 fas fa-user-plus group-hover:text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Add User</span>
                    </button>
                    <button
                        class="flex flex-col items-center justify-center p-4 transition-all duration-200 border border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 group">
                        <div
                            class="flex items-center justify-center w-10 h-10 mb-2 transition-colors duration-200 bg-blue-100 rounded-lg group-hover:bg-blue-500">
                            <i class="text-blue-500 fas fa-clipboard-check group-hover:text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Process Request</span>
                    </button>
                    <button
                        class="flex flex-col items-center justify-center p-4 transition-all duration-200 border border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 group">
                        <div
                            class="flex items-center justify-center w-10 h-10 mb-2 transition-colors duration-200 bg-blue-100 rounded-lg group-hover:bg-blue-500">
                            <i class="text-blue-500 fas fa-chart-bar group-hover:text-white"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700">View Reports</span>
                    </button>
                </div>
            </div>
        </div> --}}
    </main>
</div>

<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');

        // Mobile menu toggle
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        });

        // Close sidebar when clicking overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                sidebar.classList.remove('active');
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
    });
</script>
<style>
    .sidebar {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .nav-item {
        position: relative;
        transition: all 0.3s ease;
    }

    .nav-item.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 24px;
        background: #3b82f6;
        border-radius: 0 2px 2px 0;
    }

    .stats-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        transition: all 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }
    }
</style>
@endsection
