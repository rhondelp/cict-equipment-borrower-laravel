<?php

namespace App\Providers;

use App\Models\ItemRequest;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Badge on the sidebar's Requests link. Scoped to the partial so the
        // count only runs on pages that render the admin sidebar.
        View::composer('components.admin.navbar', function ($view) {
            $view->with('pendingRequests', ItemRequest::where('status', 'Pending')->count());
        });
    }
}
