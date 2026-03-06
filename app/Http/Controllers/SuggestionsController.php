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

        $apiKey = config('services.anthropic.key');
        if (empty($apiKey)) {
            return response()->json(['error' => 'Servicio de IA no configurado.'], 503);
        }

        $activities = $trip->activities()->orderBy('date')->orderBy('time')->get();
        $activitiesText = $activities->isNotEmpty()
            ? $activities->map(fn ($a) => "- {$a->title} en {$a->location}")->join("\n")
            : 'Sin actividades planificadas aún.';

        $prompt = <<<PROMPT
Eres un experto en viajes y turismo. El usuario tiene un viaje a {$trip->destination} del {$trip->start_date} al {$trip->end_date} con presupuesto de {$trip->budget} USD.

Actividades ya planificadas:
{$activitiesText}

Sugiere exactamente 3 actividades adicionales que complementen bien el viaje y no repitan las existentes. Para cada actividad incluye campos específicos y realistas para {$trip->destination}.

Responde ÚNICAMENTE con JSON válido en este formato exacto, sin texto adicional:
{"suggestions":[{"title":"...","description":"...","location":"...","cost":0,"time":"09:00"}]}

Reglas:
- title: máximo 50 caracteres
- description: máximo 120 caracteres, en español
- location: lugar específico en {$trip->destination}
- cost: número entero en USD (0 si es gratis)
- time: formato HH:MM en 24h
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
