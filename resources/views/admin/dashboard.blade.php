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

        {{-- Flash messages incl. login welcome --}}
        <x-ui.feedback />

        <!-- Quick Stats -->
        <section class="mb-6">
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <x-ui.stat-card label="Total Equipment" :value="$equipments->count()" icon="fa-tools" :href="route('admin.equipment')" />
                <x-ui.stat-card label="Active Users" :value="$users->count()" icon="fa-users" :href="route('admin.users')" />
                <x-ui.stat-card label="Transactions" :value="$transactions->count()" icon="fa-exchange-alt" :href="route('admin.transaction')" />
                <x-ui.stat-card label="Pending Requests" :value="$requests->where('status', 'Pending')->count()" icon="fa-clipboard-list" :href="route('admin.request')" />
            </div>
        </section>

        <!-- Recent Return Logs -->
        <section>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Recent Return Logs</h3>
                    <a href="{{ route('admin.logs') }}" class="text-sm font-medium text-brand hover:text-brand-dark">View All</a>
                </div>
                @if ($returnLogs->isEmpty())
                    <x-ui.empty-state icon="fa-undo" title="No returns logged yet"
                        hint="When transactions are marked Returned, they are recorded here." />
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($returnLogs->take(5) as $returnLog)
                        <li class="flex items-start space-x-3 py-3">
                            <div class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-brand-light">
                                <i class="fas fa-undo text-sm text-brand"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $returnLog->equipment->equipment_name ?? 'N/A' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Borrowed by {{ $returnLog->borrower->name ?? 'N/A' }} · Received by {{ $returnLog->receiver->name ?? 'N/A' }} · {{ $returnLog->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </main>
</div>
@endsection
