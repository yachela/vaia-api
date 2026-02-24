<?php

namespace App\Providers;

use App\Models\Trip;
use App\Models\TripDocumentChecklist;
use App\Observers\TripDocumentChecklistObserver;
use App\Observers\TripObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Trip::observe(TripObserver::class);
        TripDocumentChecklist::observe(TripDocumentChecklistObserver::class);
    }
}
