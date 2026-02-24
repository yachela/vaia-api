<?php

namespace App\Observers;

use App\Models\Trip;
use App\Models\TripDocumentChecklist;
use Illuminate\Support\Facades\Log;

class TripObserver
{
    public function created(Trip $trip): void
    {
        try {
            TripDocumentChecklist::create([
                'trip_id' => $trip->id,
            ]);

            Log::info('Document checklist auto-created for new trip', [
                'trip_id' => $trip->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create document checklist for trip', [
                'trip_id' => $trip->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
