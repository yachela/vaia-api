<?php

namespace App\Observers;

use App\Models\Trip;
use App\Services\PackingListService;
use Illuminate\Support\Facades\Log;

class PackingListObserver
{
    protected PackingListService $packingListService;

    public function __construct(PackingListService $packingListService)
    {
        $this->packingListService = $packingListService;
    }

    /**
     * Maneja el evento "created" del modelo Trip
     * Auto-genera la lista base de equipaje al crear un viaje
     */
    public function created(Trip $trip): void
    {
        try {
            $this->packingListService->generateBaseList($trip);
            Log::info("Lista de equipaje generada automáticamente para viaje {$trip->id}");
        } catch (\Exception $e) {
            Log::error("Error al generar lista de equipaje para viaje {$trip->id}: {$e->getMessage()}");
        }
    }
}
