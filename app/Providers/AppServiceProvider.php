<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

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
        View::composer('partials.sitebar', function (ViewInstance $view): void {
            $user = Auth::user();
            $unreadNotifications = $user instanceof User
                ? $user->unreadNotifications()->latest()->get()
                : collect();

            $view->with(
                'unreadListingNotificationCount',
                $unreadNotifications->count(),
            )->with('unreadListingNotifications', $unreadNotifications);
        });
    }
}
