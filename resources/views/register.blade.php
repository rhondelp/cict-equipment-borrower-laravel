@extends('components.default')

@section("title", "Register - CICT Equipment Borrower System")

@section("content")
<section class="flex min-h-[100dvh] items-center justify-center overflow-hidden bg-gray-50 px-4 py-12">

  <div class="flex w-full max-w-lg flex-col">

      <!-- Logo + Title -->
      <a href="#" class="mb-6 flex items-center justify-center gap-3 text-xl font-semibold text-gray-900">
          <img class="h-10 w-10"
               src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png"
               alt="CICT logo">
          CICT Borrower System
      </a>

      <!-- Register Card -->
      <div class="w-full rounded-lg border border-gray-200 bg-white shadow-sm sm:max-w-lg">
          <div class="space-y-6 p-6 sm:p-8">

              <!-- Title -->
              <h1 class="text-2xl font-semibold tracking-tight text-gray-900">
                  Create an account
              </h1>

              <!-- Feedback -->
              <x-ui.feedback />

              @if (session('success'))
                  <!-- Account created: clear next step instead of an empty repeat form -->
                  <div class="rounded-lg border border-green-200 bg-green-50 p-6 text-center" role="status">
                      <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                          <i class="fas fa-check text-xl text-green-600"></i>
                      </div>
                      <h2 class="mt-3 text-lg font-semibold text-gray-900">Account created</h2>
                      <p class="mt-1 text-sm text-gray-600">Sign in with your email and password to start borrowing equipment.</p>
                      <a href="{{ route('login') }}"
                         class="mt-4 inline-flex items-center justify-center rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2">
                          Go to Login <i class="fas fa-arrow-right ml-2"></i>
                      </a>
                  </div>
              @else

              <!-- Register Form -->
              <form class="space-y-5" action="{{ route('register') }}" method="POST">
                @csrf

                <!-- Full Name -->
                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" name="name" id="name" placeholder="Your name"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" required>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-gray-700">Your Email</label>
                    <input type="email" name="email" id="email" placeholder="name@company.com"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" required>
                </div>

                <!-- Contact Number -->
                <div>
                    <label for="contact_number" class="mb-2 block text-sm font-medium text-gray-700">Contact Number</label>
                    <input type="text" name="contact_number" id="contact_number" placeholder="09XXXXXXXXX"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                </div>

                <!-- User Type -->
                <div>
                    <label for="user_type" class="mb-2 block text-sm font-medium text-gray-700">User Type</label>
                    <select name="user_type" id="user_type"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" required>
                        <option value="" disabled selected>Select user type</option>
                        {{-- <option value="admin">Admin</option> --}}
                        <option value="Instructor">Instructor</option>
                        <option value="Student">Student</option>
                    </select>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" id="password" placeholder="••••••••"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" required>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" placeholder="••••••••"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" required>
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
                        class="w-full rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2">
                    Create an account
                </button>

                <!-- Login Link -->
                <p class="text-sm text-gray-600">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-medium text-brand hover:underline">
                        Login here
                    </a>
                </p>
              </form>
              @endif
          </div>
      </div>
  </div>
</section>
@endsection
