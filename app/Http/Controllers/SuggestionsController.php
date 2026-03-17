<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class SuggestionsController extends Controller
{
    /**
     * Genera sugerencias de actividades con IA para el viaje.
     * POST /api/trips/{trip}/suggestions
     */
    public function suggest(Trip $trip): JsonResponse
    {
        $this->authorize('view', $trip);

        // Obtener parámetro intensity_level (relaxed, moderate, intense)
        $intensityLevel = request()->input('intensity_level', 'moderate');
        
        // Validar intensity_level
        if (!in_array($intensityLevel, ['relaxed', 'moderate', 'intense'])) {
            return response()->json([
                'message' => 'El parámetro intensity_level debe ser: relaxed, moderate o intense'
            ], 422);
        }

        // Determinar número de sugerencias según intensidad
        $suggestionsCount = match($intensityLevel) {
            'relaxed' => 1,
            'moderate' => 2,
            'intense' => 3,
            default => 2
        };

        $apiKey = config('services.anthropic.key');
        if (empty($apiKey)) {
            return response()->json(['error' => 'Servicio de IA no configurado.'], 503);
        }

        $activities = $trip->activities()->orderBy('date')->orderBy('time')->get();
        $activitiesText = $activities->isNotEmpty()
            ? $activities->map(fn ($a) => "- {$a->title} en {$a->location}")->join("\n")
            : 'Sin actividades planificadas aún.';

        $intensityDescription = match($intensityLevel) {
            'relaxed' => 'relajado (1 actividad por día)',
            'moderate' => 'moderado (2 actividades por día)',
            'intense' => 'intenso (3 actividades por día)',
            default => 'moderado'
        };

        $prompt = <<<PROMPT
Eres un experto en viajes y turismo. El usuario tiene un viaje a {$trip->destination} del {$trip->start_date} al {$trip->end_date} con presupuesto de {$trip->budget} USD.

Nivel de intensidad del itinerario: {$intensityDescription}

Actividades ya planificadas:
{$activitiesText}

Sugiere exactamente {$suggestionsCount} actividades adicionales que complementen bien el viaje y no repitan las existentes. Para cada actividad incluye campos específicos y realistas para {$trip->destination}.

Responde ÚNICAMENTE con JSON válido en este formato exacto, sin texto adicional:
{"suggestions":[{"title":"...","description":"...","location":"...","cost":0,"time":"09:00"}]}

Reglas:
- title: máximo 50 caracteres
- description: máximo 120 caracteres, en español
- location: lugar específico en {$trip->destination}
- cost: número entero en USD (0 si es gratis)
- time: formato HH:MM en 24h
- Máximo {$suggestionsCount} actividades por día
PROMPT;

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-haiku-4-5-20251001',
            'max_tokens' => 1024,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (! $response->successful()) {
            return response()->json(['error' => 'Error al contactar el servicio de IA.'], 502);
        }

        $content = $response->json('content.0.text', '');
        // Extraer JSON aunque haya texto extra alrededor
        preg_match('/\{.*\}/s', $content, $matches);
        $data = isset($matches[0]) ? json_decode($matches[0], true) : null;

        if (! isset($data['suggestions']) || ! is_array($data['suggestions'])) {
            return response()->json(['error' => 'Respuesta inesperada del servicio de IA.'], 500);
        }

        return response()->json(['data' => $data['suggestions']]);
    }
}
