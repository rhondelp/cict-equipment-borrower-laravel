@extends("components.default")

@section("title", "Login - CICT Equipment Borrower System")

@section("content")
<section class="flex min-h-[100dvh] items-center justify-center overflow-hidden bg-gray-50 px-4 py-12">

  <div class="flex w-full max-w-md flex-col">

      <!-- Logo + Title -->
      <a href="#" class="mb-6 flex items-center justify-center gap-3 text-xl font-semibold text-gray-900">
          <img class="h-10 w-10"
               src="https://www.nmsc.edu.ph/application/files/9117/2319/6158/CICT_LOGO.png"
               alt="CICT logo">
          CICT Borrower System
      </a>

      <!-- Login Card -->
      <div class="w-full rounded-lg border border-gray-200 bg-white shadow-sm sm:max-w-md">
          <div class="space-y-6 p-6 sm:p-8">

              <!-- Title -->
              <h1 class="text-2xl font-semibold tracking-tight text-gray-900">
                  Sign in to your account
              </h1>

              <!-- Feedback -->
              <x-ui.feedback />

              <!-- Login Form -->
              <form class="space-y-5" action="{{ route('login.store') }}" method="POST">
                  @csrf
                  <div>
                      <label for="email" class="mb-2 block text-sm font-medium text-gray-700">Email Address</label>
                      <input type="email" name="email" id="email" placeholder="name@company.com"
                             class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" required>
                  </div>

                  <div>
                      <label for="password" class="mb-2 block text-sm font-medium text-gray-700">Password</label>
                      <input type="password" name="password" id="password" placeholder="••••••••"
                             class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20" required>
                  </div>

                  <!-- Button -->
                  <button type="submit"
                          class="w-full rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2">
                      Sign in
                  </button>

                  <!-- Register Link -->
                  <p class="text-sm text-gray-600">
                      Don’t have an account yet?
                      <a href="{{ route('register') }}" class="font-medium text-brand hover:underline">
                          Sign up
                      </a>
                  </p>
              </form>
          </div>
      </div>
  </div>
</section>
@endsection
