<?php

namespace App\Services;

use App\Models\Trip;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    /**
     * Obtiene sugerencias de equipaje basadas en el pronóstico real del clima.
     * Usa Open-Meteo (gratuito, sin API key) + Nominatim para geocoding.
     *
     * @return array Array de sugerencias con nombre, categoría y razón
     */
    public function getWeatherSuggestions(Trip $trip): array
    {
        try {
            $destination = $this->extractPrimaryDestination($trip->destination);
            $coordinates = $this->geocodeDestination($destination);

            if (! $coordinates) {
                Log::warning("No se pudo geocodificar: {$destination}. Usando sugerencias por tipo de viaje.");

                return $this->getSuggestionsByTripType($trip->trip_type ?? 'general');
            }

            $weatherData = $this->fetchOpenMeteoForecast(
                $coordinates['lat'],
                $coordinates['lon'],
                $trip->start_date->format('Y-m-d'),
                $trip->end_date->format('Y-m-d')
            );

            if (! $weatherData) {
                Log::warning("No se pudo obtener pronóstico para {$destination}.");

                return $this->getSuggestionsByTripType($trip->trip_type ?? 'general');
            }

            $weatherSuggestions = $this->generateSuggestionsFromWeather($weatherData, $destination);
            $typeSuggestions = $this->getSuggestionsByTripType($trip->trip_type ?? 'general');

            // Fusionar sin duplicados por nombre
            $allSuggestions = $weatherSuggestions;
            foreach ($typeSuggestions as $ts) {
                $exists = collect($allSuggestions)->contains(
                    fn ($s) => strtolower($s['name']) === strtolower($ts['name'])
                );
                if (! $exists) {
                    $allSuggestions[] = $ts;
                }
            }

            return $allSuggestions;
        } catch (\Exception $e) {
            Log::error("Error al obtener sugerencias climáticas: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * Extrae el destino principal (primer elemento si hay comas, multi-destino)
     */
    private function extractPrimaryDestination(string $destination): string
    {
        return trim(explode(',', $destination)[0]);
    }

    /**
     * Geocodifica un destino usando Nominatim (OpenStreetMap, gratuito, sin API key)
     */
    private function geocodeDestination(string $destination): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'VAIA-TravelApp/1.0 (contact@vaia.app)',
                'Accept-Language' => 'es',
            ])->timeout(5)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $destination,
                'format' => 'json',
                'limit' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (! empty($data)) {
                    return [
                        'lat' => (float) $data[0]['lat'],
                        'lon' => (float) $data[0]['lon'],
                    ];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::warning("Error en geocoding para '{$destination}': {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Obtiene pronóstico diario de Open-Meteo (API gratuita, sin key requerida)
     */
    private function fetchOpenMeteoForecast(float $lat, float $lon, string $startDate, string $endDate): ?array
    {
        try {
            // Open-Meteo solo soporta pronósticos hasta 16 días. Para fechas más lejanas usamos climatología.
            $start = \Carbon\Carbon::parse($startDate);
            $daysFromNow = now()->diffInDays($start, false);

            if ($daysFromNow > 15) {
                // Fecha muy lejana: usar datos históricos climáticos del mismo mes
                return $this->fetchClimateNormals($lat, $lon, $start->month);
            }

            $response = Http::timeout(8)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $lat,
                'longitude' => $lon,
                'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_probability_mean,weathercode',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'timezone' => 'auto',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            $daily = $data['daily'] ?? null;

            if (! $daily || empty($daily['temperature_2m_max'])) {
                return null;
            }

            $maxTemps = $daily['temperature_2m_max'];
            $minTemps = $daily['temperature_2m_min'];
            $rainProbs = $daily['precipitation_probability_mean'] ?? [];
            $weatherCodes = $daily['weathercode'] ?? [];

            $avgMax = array_sum($maxTemps) / count($maxTemps);
            $avgMin = array_sum($minTemps) / count($minTemps);
            $maxRain = ! empty($rainProbs) ? max($rainProbs) : 0;

            $hasSnow = collect($weatherCodes)->contains(fn ($c) => $c >= 71 && $c <= 77);
            $hasStorm = collect($weatherCodes)->contains(fn ($c) => $c >= 95 && $c <= 99);

            return [
                'avg_max' => round($avgMax, 1),
                'avg_min' => round($avgMin, 1),
                'max_rain_probability' => $maxRain,
                'has_snow' => $hasSnow,
                'has_storm' => $hasStorm,
            ];
        } catch (\Exception $e) {
            Log::warning("Error en Open-Meteo: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Obtiene promedios climáticos históricos para fechas lejanas (>15 días)
     * usando la API de ERA5 de Open-Meteo (también gratuita)
     */
    private function fetchClimateNormals(float $lat, float $lon, int $month): ?array
    {
        try {
            // Usar datos del año pasado como referencia climática
            $year = now()->year - 1;
            $startDate = sprintf('%d-%02d-01', $year, $month);
            $endDate = sprintf('%d-%02d-%02d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year));

            $response = Http::timeout(8)->get('https://archive-api.open-meteo.com/v1/archive', [
                'latitude' => $lat,
                'longitude' => $lon,
                'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'timezone' => 'auto',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            $daily = $data['daily'] ?? null;

            if (! $daily || empty($daily['temperature_2m_max'])) {
                return null;
            }

            $maxTemps = array_filter($daily['temperature_2m_max'], fn ($v) => $v !== null);
            $minTemps = array_filter($daily['temperature_2m_min'], fn ($v) => $v !== null);
            $precip = array_filter($daily['precipitation_sum'] ?? [], fn ($v) => $v !== null);

            if (empty($maxTemps)) {
                return null;
            }

            $avgRainDays = count(array_filter($precip, fn ($p) => $p > 2));
            $rainProbability = ! empty($precip) ? ($avgRainDays / count($precip)) * 100 : 0;

            return [
                'avg_max' => round(array_sum($maxTemps) / count($maxTemps), 1),
                'avg_min' => round(array_sum($minTemps) / count($minTemps), 1),
                'max_rain_probability' => round($rainProbability),
                'has_snow' => (array_sum($minTemps) / count($minTemps)) < -2,
                'has_storm' => false,
            ];
        } catch (\Exception $e) {
            Log::warning("Error en API de clima histórico: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Genera sugerencias de equipaje según el pronóstico del clima
     */
    private function generateSuggestionsFromWeather(array $w, string $destination): array
    {
        $suggestions = [];
        $maxTemp = $w['avg_max'];
        $minTemp = $w['avg_min'];
        $rainPct = $w['max_rain_probability'];

        // Calor (>28°C)
        if ($maxTemp > 28) {
            $suggestions[] = [
                'name' => 'Protector solar FPS 50+',
                'category' => 'Higiene',
                'suggestion_reason' => "Temperaturas de hasta {$maxTemp}°C en {$destination}",
            ];
            $suggestions[] = [
                'name' => 'Gorra o sombrero',
                'category' => 'Ropa',
                'suggestion_reason' => "Protección solar para {$maxTemp}°C",
            ];
            $suggestions[] = [
                'name' => 'Ropa ligera y transpirable',
                'category' => 'Ropa',
                'suggestion_reason' => "Clima cálido ({$maxTemp}°C) en {$destination}",
            ];
        }

        // Frío moderado (noches frescas, días templados)
        if ($minTemp < 15 && $maxTemp <= 28) {
            $suggestions[] = [
                'name' => 'Campera o chaqueta',
                'category' => 'Ropa',
                'suggestion_reason' => "Noches frescas ({$minTemp}°C) en {$destination}",
            ];
        }

        // Frío intenso (<10°C)
        if ($minTemp < 10) {
            $suggestions[] = [
                'name' => 'Abrigo de invierno',
                'category' => 'Ropa',
                'suggestion_reason' => "Temperatura mínima de {$minTemp}°C en {$destination}",
            ];
            $suggestions[] = [
                'name' => 'Bufanda',
                'category' => 'Ropa',
                'suggestion_reason' => "Clima frío ({$minTemp}°C) en {$destination}",
            ];
            $suggestions[] = [
                'name' => 'Guantes térmicos',
                'category' => 'Ropa',
                'suggestion_reason' => "Temperatura mínima de {$minTemp}°C",
            ];
        }

        // Lluvia probable (>40%)
        if ($rainPct > 40) {
            $suggestions[] = [
                'name' => 'Paraguas compacto',
                'category' => 'Ropa',
                'suggestion_reason' => "Probabilidad de lluvia del {$rainPct}% en {$destination}",
            ];
            $suggestions[] = [
                'name' => 'Impermeable o poncho',
                'category' => 'Ropa',
                'suggestion_reason' => "Lluvia probable ({$rainPct}%) en {$destination}",
            ];
        }

        // Nieve
        if ($w['has_snow']) {
            $suggestions[] = [
                'name' => 'Botas de nieve impermeables',
                'category' => 'Ropa',
                'suggestion_reason' => "Pronóstico de nieve en {$destination}",
            ];
            $suggestions[] = [
                'name' => 'Térmica interior',
                'category' => 'Ropa',
                'suggestion_reason' => "Protección adicional para la nieve en {$destination}",
            ];
        }

        // Tormenta
        if ($w['has_storm']) {
            $suggestions[] = [
                'name' => 'Poncho de lluvia resistente',
                'category' => 'Ropa',
                'suggestion_reason' => "Tormentas previstas en {$destination}",
            ];
        }

        return $suggestions;
    }

    /**
     * Genera sugerencias adicionales basadas en el tipo de viaje del usuario
     */
    private function getSuggestionsByTripType(string $tripType): array
    {
        return match ($tripType) {
            'aventura' => [
                [
                    'name' => 'Botiquín de primeros auxilios',
                    'category' => 'Higiene',
                    'suggestion_reason' => 'Esencial para viajes de aventura',
                ],
                [
                    'name' => 'Repelente de insectos',
                    'category' => 'Higiene',
                    'suggestion_reason' => 'Protección en actividades al aire libre',
                ],
                [
                    'name' => 'Botas de trekking',
                    'category' => 'Ropa',
                    'suggestion_reason' => 'Necesarias para actividades de aventura',
                ],
                [
                    'name' => 'Linterna o lámpara frontal',
                    'category' => 'Tecnología',
                    'suggestion_reason' => 'Útil en excursiones y camping',
                ],
            ],
            'familiar' => [
                [
                    'name' => 'Botiquín familiar',
                    'category' => 'Higiene',
                    'suggestion_reason' => 'Indispensable para viajes con niños',
                ],
                [
                    'name' => 'Protector solar extra',
                    'category' => 'Higiene',
                    'suggestion_reason' => 'Protección adicional para los más pequeños',
                ],
                [
                    'name' => 'Entretenimiento para el trayecto',
                    'category' => 'Tecnología',
                    'suggestion_reason' => 'Tableta o juegos para mantener ocupados a los niños',
                ],
            ],
            'solitario' => [
                [
                    'name' => 'Candado para mochila',
                    'category' => 'Documentación',
                    'suggestion_reason' => 'Seguridad extra viajando solo',
                ],
                [
                    'name' => 'Audífonos con cancelación de ruido',
                    'category' => 'Tecnología',
                    'suggestion_reason' => 'Compañero esencial del viajero solitario',
                ],
                [
                    'name' => 'Powerbank de alta capacidad',
                    'category' => 'Tecnología',
                    'suggestion_reason' => 'Autonomía extra cuando viajás solo',
                ],
            ],
            'amigos' => [
                [
                    'name' => 'Altavoz Bluetooth portátil',
                    'category' => 'Tecnología',
                    'suggestion_reason' => 'Música para el grupo',
                ],
                [
                    'name' => 'Botiquín de primeros auxilios grupal',
                    'category' => 'Higiene',
                    'suggestion_reason' => 'Para el grupo de viajeros',
                ],
            ],
            default => [
                [
                    'name' => 'Repelente de insectos',
                    'category' => 'Higiene',
                    'suggestion_reason' => 'Protección general recomendada para viajes',
                ],
            ],
        };
    }
}
