<?php

namespace App\Providers;

use App\Models\Trip;
use App\Models\TripDocumentChecklist;
use App\Observers\PackingListObserver;
use App\Observers\TripDocumentChecklistObserver;
use App\Observers\TripObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Forzar HTTPS en producción (Railway termina SSL en el proxy)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Trip::observe(TripObserver::class);
        Trip::observe(PackingListObserver::class);
        TripDocumentChecklist::observe(TripDocumentChecklistObserver::class);
    }
}
