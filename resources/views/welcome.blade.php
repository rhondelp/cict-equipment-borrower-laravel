@extends("components.default")

@section("title", "Welcome - CICT Equipment Borrower System")

@section("content")
<section class="relative flex items-center justify-center min-h-screen overflow-hidden text-white bg-gradient-to-br from-black via-gray-900 to-gray-800">

    <div class="grid items-center w-full h-full gap-12 px-6 py-12 mx-auto lg:py-20 lg:grid-cols-12 max-w-7xl">

        <!-- Left Section -->
        <div class="flex flex-col items-center mx-auto space-y-6 text-center place-self-center lg:col-span-7 lg:items-start lg:text-left">
            <h1 class="max-w-2xl text-4xl font-extrabold leading-tight tracking-tight md:text-5xl xl:text-6xl animate-fade-in">
                Welcome to <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-yellow-600 drop-shadow-lg">
                    CICT Equipment Borrower System
                </span>
            </h1>
{{--
            <p class="max-w-2xl text-lg text-gray-300 delay-150 lg:text-xl animate-fade-in">
                Manage, monitor, and borrow CICT equipment easily with a seamless and secure system.
            </p> --}}

            <div class="flex flex-col justify-center gap-4 delay-300 sm:flex-row lg:justify-start animate-fade-in">
                <!-- Login Button -->
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center px-6 py-3 text-lg font-medium text-black transition-all duration-300 transform rounded-lg shadow-md bg-gradient-to-r from-yellow-400 to-yellow-600 hover:from-yellow-500 hover:to-yellow-700 hover:shadow-xl hover:scale-105">
                    Login to System
                    <svg class="w-5 h-5 ml-2 -mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6
                        6a1 1 0 010 1.414l-6 6a1 1
                        0 01-1.414-1.414L14.586 11H3a1
                        1 0 110-2h11.586l-4.293-4.293a1
                        1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </a>
            </div>
        </div>


        <div class="items-center justify-center mx-auto delay-500 lg:mt-0 lg:col-span-5 lg:flex animate-fade-in">
            <div class="p-6shadow-2xl to-black rounded-2xl">
                <img class="w-full h-auto max-h-[400px] object-contain"
                     src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png"
                     alt="CICT Logo">
            </div>
        </div>
    </div>
</section>

<style>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(15px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fadeIn 0.9s ease forwards; opacity: 0; }
.delay-150 { animation-delay: 0.15s; }
.delay-300 { animation-delay: 0.3s; }
.delay-500 { animation-delay: 0.5s; }
</style>
@endsection
