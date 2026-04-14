<?php

namespace App\Services;

use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiPackingService
{
    /**
     * Genera sugerencias de equipaje usando IA (OpenRouter) basadas en el clima y contexto del viaje.
     *
     * @return array Array de items con name, category, suggestion_reason
     */
    public function generateSuggestions(Trip $trip, ?array $weatherData): array
    {
        try {
            $apiKey = $this->resolveApiKey($trip);

            if (empty($apiKey)) {
                Log::warning("AiPackingService: No hay API key configurada para el viaje {$trip->id}.");

                return [];
            }

            $prompt = $this->buildPrompt($trip, $weatherData);
            $model = config('services.openrouter.model', 'anthropic/claude-haiku-4-5');

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'HTTP-Referer' => 'https://vaia.app',
                'X-Title' => 'VAIA Travel App',
            ])->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => $model,
                'max_tokens' => 1024,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if (! $response->successful()) {
                Log::error("AiPackingService: Error de OpenRouter [{$response->status()}]: {$response->body()}");

                return [];
            }

            $content = $response->json('choices.0.message.content', '');

            return $this->parseResponse($content);
        } catch (\Exception $e) {
            Log::error("AiPackingService: Excepción al generar sugerencias para viaje {$trip->id}: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * Determina la API key a usar: primero la del usuario, luego la de la app
     */
    private function resolveApiKey(Trip $trip): ?string
    {
        $user = $trip->user;

        if ($user && ! empty($user->ai_api_key)) {
            try {
                return decrypt($user->ai_api_key);
            } catch (\Exception $e) {
                Log::warning("AiPackingService: No se pudo desencriptar la API key del usuario {$user->id}.");
            }
        }

        return config('services.openrouter.key');
    }

    /**
     * Construye el prompt con toda la información del viaje y el clima
     */
    private function buildPrompt(Trip $trip, ?array $weatherData): string
    {
        $destination = $trip->destination;
        $startDate = $trip->start_date->format('d/m/Y');
        $endDate = $trip->end_date->format('d/m/Y');
        $duration = $trip->start_date->diffInDays($trip->end_date) + 1;
        $tripType = $trip->trip_type ?? 'general';
        $season = $this->determineSeason($trip->start_date, $destination);

        $climaSection = $this->buildClimaSection($weatherData);

        return <<<PROMPT
Eres un experto en viajes. Generá una lista de equipaje personalizada y práctica.

DATOS DEL VIAJE:
- Destino: {$destination}
- Fechas: {$startDate} al {$endDate} ({$duration} días)
- Temporada: {$season}
- Tipo de viaje: {$tripType}
{$climaSection}

INSTRUCCIONES:
- Generá entre 10 y 15 ítems específicos y útiles para este viaje
- Considerá el clima, la temporada, la duración y el tipo de viaje
- Sé específico: en vez de "ropa" escribí "remeras de manga corta" o "campera impermeable"
- Omití documentos básicos (pasaporte, DNI) y tecnología genérica (cargador, auriculares) porque ya están en otra lista
- Enfocate en ropa, higiene e ítems especiales según el destino/actividad

Respondé ÚNICAMENTE con JSON válido, sin texto adicional:
{"items":[{"name":"...","category":"...","suggestion_reason":"..."}]}

Categorías válidas (exactamente así): Ropa, Higiene, Tecnología, Documentación
suggestion_reason: explicación breve en español de por qué incluirlo (máx 80 caracteres)
PROMPT;
    }

    /**
     * Construye la sección de clima del prompt
     */
    private function buildClimaSection(?array $weatherData): string
    {
        if (! $weatherData) {
            return '';
        }

        $lines = ["\nCLIMA ESPERADO:"];
        $lines[] = "- Temperatura máxima: {$weatherData['avg_max']}°C";
        $lines[] = "- Temperatura mínima: {$weatherData['avg_min']}°C";
        $lines[] = "- Probabilidad de lluvia: {$weatherData['max_rain_probability']}%";

        if ($weatherData['has_snow']) {
            $lines[] = '- Se espera nieve';
        }

        if ($weatherData['has_storm']) {
            $lines[] = '- Se esperan tormentas';
        }

        return implode("\n", $lines);
    }

    /**
     * Determina la temporada según el hemisferio del destino
     */
    private function determineSeason(Carbon $date, string $destination): string
    {
        $month = $date->month;

        // Detectar hemisferio sur por palabras clave en el destino
        $southernKeywords = ['argentina', 'chile', 'uruguay', 'brasil', 'brazil', 'australia', 'nueva zelanda',
            'sudáfrica', 'south africa', 'perú', 'peru', 'bolivia', 'paraguay', 'patagonia',
            'buenos aires', 'santiago', 'montevideo', 'lima', 'bogotá', 'bogota'];

        $destinationLower = strtolower($destination);
        $isSouthern = collect($southernKeywords)->contains(fn ($k) => str_contains($destinationLower, $k));

        if ($isSouthern) {
            return match (true) {
                in_array($month, [12, 1, 2]) => 'Verano (hemisferio sur)',
                in_array($month, [3, 4, 5]) => 'Otoño (hemisferio sur)',
                in_array($month, [6, 7, 8]) => 'Invierno (hemisferio sur)',
                default => 'Primavera (hemisferio sur)',
            };
        }

        return match (true) {
            in_array($month, [12, 1, 2]) => 'Invierno (hemisferio norte)',
            in_array($month, [3, 4, 5]) => 'Primavera (hemisferio norte)',
            in_array($month, [6, 7, 8]) => 'Verano (hemisferio norte)',
            default => 'Otoño (hemisferio norte)',
        };
    }

    /**
     * Parsea la respuesta JSON del modelo de IA
     *
     * @return array<int, array{name: string, category: string, suggestion_reason: string}>
     */
    private function parseResponse(string $content): array
    {
        // Extraer JSON aunque haya texto extra alrededor
        preg_match('/\{.*\}/s', $content, $matches);

        if (empty($matches[0])) {
            Log::warning("AiPackingService: No se encontró JSON en la respuesta: {$content}");

            return [];
        }

        $data = json_decode($matches[0], true);

        if (! isset($data['items']) || ! is_array($data['items'])) {
            Log::warning("AiPackingService: Estructura JSON inesperada: {$matches[0]}");

            return [];
        }

        $validCategories = ['Ropa', 'Higiene', 'Tecnología', 'Documentación'];

        return collect($data['items'])
            ->filter(fn ($item) => isset($item['name'], $item['category'], $item['suggestion_reason']))
            ->filter(fn ($item) => in_array($item['category'], $validCategories))
            ->map(fn ($item) => [
                'name' => substr($item['name'], 0, 100),
                'category' => $item['category'],
                'suggestion_reason' => substr($item['suggestion_reason'], 0, 255),
            ])
            ->values()
            ->toArray();
    }
}
