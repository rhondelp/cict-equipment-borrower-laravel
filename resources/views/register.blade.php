@extends('components.default')

@section("title", "Register - CICT Equipment Borrower System")

@section("content")
<section class="relative flex items-center justify-center min-h-screen overflow-hidden text-white bg-gradient-to-br from-black via-gray-900 to-gray-800">

  <div class="flex flex-col items-center justify-center w-full px-6 py-8 mx-auto md:h-screen lg:py-0">

      <!-- Logo + Title -->
      <a href="#" class="flex items-center mb-6 text-2xl font-semibold text-yellow-400 transition-all duration-300 hover:text-yellow-500 animate-fade-in">
          <img class="w-10 h-10 mr-3 drop-shadow-lg"
               src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png"
               alt="logo">
          CICT Borrower System
      </a>

      <!-- Register Card -->
      <div class="w-full delay-150 border shadow-2xl bg-gray-900/70 backdrop-blur-lg rounded-xl border-yellow-500/60 md:mt-0 sm:max-w-lg xl:p-0 animate-fade-in">
          <div class="p-6 space-y-6 sm:p-8">

              <!-- Title -->
              <h1 class="text-2xl font-bold tracking-tight text-yellow-400">
                  Create an account
              </h1>

              <!-- Register Form -->
              <form class="space-y-5" action="{{ route('register') }}" method="POST">
                @csrf

                <!-- Full Name -->
                <div>
                    <label for="name" class="block mb-2 text-sm font-medium text-gray-200">Full Name</label>
                    <input type="text" name="name" id="name" placeholder="Your name"
                           class="bg-gray-800 border border-gray-600 text-white rounded-lg focus:ring-yellow-400 focus:border-yellow-400 block w-full p-2.5 placeholder-gray-400 transition-all duration-300" required>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-200">Your Email</label>
                    <input type="email" name="email" id="email" placeholder="name@company.com"
                           class="bg-gray-800 border border-gray-600 text-white rounded-lg focus:ring-yellow-400 focus:border-yellow-400 block w-full p-2.5 placeholder-gray-400 transition-all duration-300" required>
                </div>

                <!-- Contact Number -->
                <div>
                    <label for="contact_number" class="block mb-2 text-sm font-medium text-gray-200">Contact Number</label>
                    <input type="text" name="contact_number" id="contact_number" placeholder="09XXXXXXXXX"
                           class="bg-gray-800 border border-gray-600 text-white rounded-lg focus:ring-yellow-400 focus:border-yellow-400 block w-full p-2.5 placeholder-gray-400 transition-all duration-300">
                </div>

                <!-- User Type -->
                <div>
                    <label for="user_type" class="block mb-2 text-sm font-medium text-gray-200">User Type</label>
                    <select name="user_type" id="user_type"
                            class="bg-gray-800 border border-gray-600 text-white rounded-lg focus:ring-yellow-400 focus:border-yellow-400 block w-full p-2.5 transition-all duration-300" required>
                        <option value="" disabled selected>Select user type</option>
                        {{-- <option value="admin">Admin</option> --}}
                        <option value="Instructor">Instructor</option>
                        <option value="Student">Student</option>
                    </select>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-200">Password</label>
                    <input type="password" name="password" id="password" placeholder="••••••••"
                           class="bg-gray-800 border border-gray-600 text-white rounded-lg focus:ring-yellow-400 focus:border-yellow-400 block w-full p-2.5 placeholder-gray-400 transition-all duration-300" required>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block mb-2 text-sm font-medium text-gray-200">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" placeholder="••••••••"
                           class="bg-gray-800 border border-gray-600 text-white rounded-lg focus:ring-yellow-400 focus:border-yellow-400 block w-full p-2.5 placeholder-gray-400 transition-all duration-300" required>
                </div>

                <!-- Terms -->
                <div class="flex items-start">
                    {{-- <div class="flex items-center h-5">
                        <input id="terms" aria-describedby="terms" type="checkbox"
                               class="w-4 h-4 bg-gray-700 border border-gray-500 rounded focus:ring-2 focus:ring-yellow-400" required>
                    </div> --}}
                    {{-- <div class="ml-3 text-sm">
                        <label for="terms" class="font-light text-gray-400">
                            I accept the <a href="#" class="font-medium text-yellow-400 hover:underline">Terms and Conditions</a>
                        </label>
                    </div> --}}
                </div>

                <!-- Button -->
                <button type="submit"
                        class="w-full text-black font-semibold rounded-lg text-sm px-5 py-2.5 text-center
                               bg-gradient-to-r from-yellow-400 to-yellow-600 hover:from-yellow-500 hover:to-yellow-700
                               shadow-md hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                    Create an account
                </button>

                <!-- Login Link -->
                <p class="text-sm font-light text-gray-400">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-medium text-yellow-400 hover:underline">
                        Login here
                    </a>
                </p>
              </form>
          </div>
      </div>
  </div>
</section>

<!-- Animations -->
<style>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(15px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fadeIn 0.9s ease forwards; opacity: 0; }
.delay-150 { animation-delay: 0.15s; }
</style>
@endsection
