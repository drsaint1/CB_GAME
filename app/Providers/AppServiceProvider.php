<?php

namespace App\Providers;

// use App\Models\BookingChatMessage;
// use App\Observers\BookingChatMessageObserver;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

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
        // BookingChatMessage::observe(BookingChatMessageObserver::class);
    }
}
