@extends("components.default")

@section("title", "Welcome - CICT Equipment Borrower System")

@section("content")
<section class="flex min-h-[100dvh] items-center overflow-hidden bg-gray-50 px-6 py-12">

    <div class="mx-auto grid w-full max-w-7xl items-center gap-12 lg:grid-cols-12">

        <!-- Left Section -->
        <div class="place-self-center mx-auto flex flex-col items-center space-y-6 text-center lg:col-span-7 lg:items-start lg:text-left">
            <h1 class="max-w-2xl text-4xl font-semibold leading-tight tracking-tight text-gray-900 md:text-5xl">
                Welcome to
                <span class="text-brand">CICT Equipment Borrower System</span>
            </h1>
            <p class="max-w-xl text-base leading-relaxed text-gray-600">
                Borrow, track, and return department equipment with one record for every transaction.
            </p>

            <div class="flex flex-col justify-center gap-4 sm:flex-row lg:justify-start">
                <!-- Login Button -->
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center rounded-lg bg-brand px-6 py-3 text-base font-semibold text-white transition-colors hover:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2">
                    Login to System
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>

        <!-- Right Section -->
        <div class="mx-auto flex items-center justify-center lg:col-span-5">
            <img class="h-auto max-h-[360px] w-full object-contain"
                 src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png"
                 alt="CICT logo">
        </div>
    </div>
</section>
@endsection
