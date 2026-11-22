<?php

namespace App\Providers;

use App\Models\Legislature;
use App\Services\LegislatureContext;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LegislatureContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::bind('legislature', fn (string $sigla): Legislature => Legislature::query()
            ->where('sigla', strtoupper($sigla))
            ->firstOrFail());

        Event::listen(RouteMatched::class, fn () => $this->app->forgetInstance(LegislatureContext::class));

        view()->composer('*', fn ($view) => $view->with('legislatureContext', $this->app->make(LegislatureContext::class)));
    }
}
