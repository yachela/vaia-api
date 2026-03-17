<?php

namespace App\Services;

use App\Models\Trip;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    /**
     * Obtiene sugerencias de equipaje basadas en el pronóstico del clima
     *
     * @return array Array de sugerencias con nombre, categoría y razón
     */
    public function getWeatherSuggestions(Trip $trip): array
    {
        try {
            $weatherData = $this->fetchWeatherForecast($trip->destination, $trip->start_date);

            if (! $weatherData) {
                Log::warning("No se pudo obtener pronóstico para {$trip->destination}");

                return [];
            }

            return $this->generateSuggestionsFromWeather($weatherData);
        } catch (\Exception $e) {
            Log::error("Error al obtener sugerencias climáticas: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * Obtiene el pronóstico del clima desde el servicio externo
     */
    private function fetchWeatherForecast(string $destination, $startDate): ?array
    {
        $apiKey = config('services.openweather.key');

        if (! $apiKey) {
            Log::warning('API key de OpenWeather no configurada');

            return null;
        }

        try {
            $response = Http::timeout(5)->get('https://api.openweathermap.org/data/2.5/forecast', [
                'q' => $destination,
                'appid' => $apiKey,
                'units' => 'metric',
                'lang' => 'es',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'temp' => $data['list'][0]['main']['temp'] ?? null,
                    'rain_probability' => $data['list'][0]['pop'] ?? 0,
                    'description' => $data['list'][0]['weather'][0]['description'] ?? '',
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Error al consultar API de clima: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Genera sugerencias de equipaje basadas en los datos del clima
     */
    private function generateSuggestionsFromWeather(array $weatherData): array
    {
        $suggestions = [];
        $temp = $weatherData['temp'];
        $rainProbability = $weatherData['rain_probability'] * 100;

        // Temperatura baja (<10°C): agregar abrigo, bufanda, guantes
        if ($temp < 10) {
            $suggestions[] = [
                'name' => 'Abrigo',
                'category' => 'Ropa',
                'suggestion_reason' => "Temperatura baja ({$temp}°C)",
            ];
            $suggestions[] = [
                'name' => 'Bufanda',
                'category' => 'Ropa',
                'suggestion_reason' => "Temperatura baja ({$temp}°C)",
            ];
            $suggestions[] = [
                'name' => 'Guantes',
                'category' => 'Ropa',
                'suggestion_reason' => "Temperatura baja ({$temp}°C)",
            ];
        }

        // Alta probabilidad de lluvia (>40%): agregar paraguas
        if ($rainProbability > 40) {
            $suggestions[] = [
                'name' => 'Paraguas',
                'category' => 'Ropa',
                'suggestion_reason' => "Probabilidad de lluvia del {$rainProbability}%",
            ];
            $suggestions[] = [
                'name' => 'Impermeable',
                'category' => 'Ropa',
                'suggestion_reason' => "Probabilidad de lluvia del {$rainProbability}%",
            ];
        }

        // Temperatura alta (>28°C): agregar protector solar
        if ($temp > 28) {
            $suggestions[] = [
                'name' => 'Protector solar',
                'category' => 'Higiene',
                'suggestion_reason' => "Temperatura alta ({$temp}°C)",
            ];
            $suggestions[] = [
                'name' => 'Gorra o sombrero',
                'category' => 'Ropa',
                'suggestion_reason' => "Temperatura alta ({$temp}°C)",
            ];
        }

        return $suggestions;
    }
}
