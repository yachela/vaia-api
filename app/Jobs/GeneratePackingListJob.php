<?php

namespace App\Jobs;

use App\Models\PackingItem;
use App\Models\Trip;
use App\Services\AiPackingService;
use App\Services\WeatherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GeneratePackingListJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        protected Trip $trip
    ) {}

    public function handle(WeatherService $weatherService, AiPackingService $aiPackingService): void
    {
        $packingList = $this->trip->packingList;

        if (! $packingList) {
            Log::warning("GeneratePackingListJob: No se encontró packing list para el viaje {$this->trip->id}.");

            return;
        }

        $packingList->update(['status' => 'generating']);

        // Obtener datos del clima
        $weatherData = $weatherService->getWeatherData($this->trip);

        if (! $weatherData) {
            Log::warning("GeneratePackingListJob: Sin datos climáticos para viaje {$this->trip->id}. Usando fallback rule-based.");
            $aiSuggestions = $weatherService->getWeatherSuggestions($this->trip);
        } else {
            // Llamar a la IA con los datos del clima
            $aiSuggestions = $aiPackingService->generateSuggestions($this->trip, $weatherData);

            // Fallback a reglas si la IA falla
            if (empty($aiSuggestions)) {
                Log::warning("GeneratePackingListJob: IA sin resultados para viaje {$this->trip->id}. Usando fallback rule-based.");
                $aiSuggestions = $weatherService->getWeatherSuggestions($this->trip);
            }
        }

        foreach ($aiSuggestions as $suggestion) {
            PackingItem::create([
                'packing_list_id' => $packingList->id,
                'name' => $suggestion['name'],
                'category' => $suggestion['category'],
                'is_packed' => false,
                'is_suggested' => true,
                'suggestion_reason' => $suggestion['suggestion_reason'],
            ]);
        }

        $packingList->update(['status' => 'ready']);

        Log::info("GeneratePackingListJob: Valija generada para viaje {$this->trip->id} con ".count($aiSuggestions).' sugerencias.');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("GeneratePackingListJob: Job falló para viaje {$this->trip->id}: {$exception->getMessage()}");

        $packingList = $this->trip->packingList;
        $packingList?->update(['status' => 'failed']);
    }
}
