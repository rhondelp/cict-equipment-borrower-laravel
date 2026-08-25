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
                    <h1 class="text-xl font-semibold tracking-tight text-gray-900">Dashboard</h1>
                    <p class="text-sm text-gray-500">Welcome back, {{ Auth::user()->name }}! Here's your overview.</p>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <!-- Notifications -->
                <div class="relative">
                    <button class="relative p-2 text-gray-500 transition-colors duration-200 hover:text-gray-700">
                        <i class="text-xl fas fa-bell"></i>
                    </button>
                </div>

                <!-- User Menu -->
                <div class="flex items-center space-x-3">
                    <img class="h-9 w-9 rounded-lg border-2 border-blue-600 object-cover"
                        src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=2563eb&color=fff&bold=true"
                        alt="{{ Auth::user()->name }}">
                    <div class="hidden md:block">
                        <p class="text-sm font-semibold text-gray-900">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-500">{{ Auth::user()->user_type ?? 'Administrator' }}</p>
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
                    class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-colors hover:border-blue-300">
                    <div>
                        <p class="text-xs font-medium text-gray-500">Total Equipment</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $equipments->count() }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50">
                        <i class="fas fa-tools text-lg text-blue-600"></i>
                    </div>
                </div>

                <!-- Active Users -->
                <div
                    class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-colors hover:border-blue-300">
                    <div>
                        <p class="text-xs font-medium text-gray-500">Active Users</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $users->count() }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50">
                        <i class="fas fa-users text-lg text-blue-600"></i>
                    </div>
                </div>

                <!-- Active Transactions -->
                <div
                    class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-colors hover:border-blue-300">
                    <div>
                        <p class="text-xs font-medium text-gray-500">Transactions</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $transactions->count() }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50">
                        <i class="fas fa-exchange-alt text-lg text-blue-600"></i>
                    </div>
                </div>

                <!-- Pending Requests -->
                <div
                    class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-colors hover:border-blue-300">
                    <div>
                        <p class="text-xs font-medium text-gray-500">Total Requests</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $requests->count() }}</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50">
                        <i class="fas fa-clipboard-list text-lg text-blue-600"></i>
                    </div>
                </div>
            </div>
        </section>


        <!-- Charts and Activity Section -->
        <div class="w-full px-4 py-4">
            <!-- Recent Activity -->
            <div class="w-full rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-base font-semibold text-gray-900">Recent Return Logs</h3>
                    <a href="{{ route('admin.logs') }}"
                        class="text-sm font-medium text-blue-600 hover:text-blue-700">View All</a>
                </div>
                @foreach ($returnLogs as $returnLog)
                <div class="flex items-start mb-4 space-x-3">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-blue-50 mt-1">
                        <i class="fas fa-undo text-sm text-blue-600"></i>
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
                <hr class="my-2 border-gray-100">
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
@endsection
