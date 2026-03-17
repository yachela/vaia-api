<?php

namespace App\Http\Controllers;

use App\Http\Resources\PackingListResource;
use App\Models\PackingList;
use App\Models\Trip;
use App\Services\PackingListService;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PackingListController extends Controller
{
    public function __construct(
        protected PackingListService $packingListService,
        protected WeatherService $weatherService
    ) {}

    public function show(Trip $trip): JsonResponse|PackingListResource
    {
        try {
            $this->authorize('view', $trip);

            $packingList = PackingList::with('items')
                ->where('trip_id', $trip->id)
                ->firstOrFail();

            return new PackingListResource($packingList);
        } catch (\Exception $e) {
            Log::error("Error al obtener lista de equipaje: {$e->getMessage()}");

            return response()->json([
                'message' => 'Error al obtener la lista de equipaje',
            ], 500);
        }
    }

    public function generate(Trip $trip): JsonResponse|PackingListResource
    {
        try {
            $this->authorize('update', $trip);

            $packingList = $this->packingListService->generateBaseList($trip);

            return new PackingListResource($packingList);
        } catch (\Exception $e) {
            Log::error("Error al generar lista de equipaje: {$e->getMessage()}");

            return response()->json([
                'message' => 'Error al generar la lista de equipaje',
            ], 500);
        }
    }

    public function weatherSuggestions(Trip $trip): JsonResponse
    {
        try {
            $this->authorize('view', $trip);

            $suggestions = $this->weatherService->getWeatherSuggestions($trip);

            return response()->json([
                'suggestions' => $suggestions,
            ]);
        } catch (\Exception $e) {
            Log::error("Error al obtener sugerencias climáticas: {$e->getMessage()}");

            return response()->json([
                'message' => 'Error al obtener sugerencias climáticas',
            ], 500);
        }
    }

    public function toggleItem(Request $request, string $itemId): JsonResponse
    {
        try {
            $item = \App\Models\PackingItem::findOrFail($itemId);
            $packingList = $item->packingList;

            $this->authorize('update', $packingList->trip);

            $item->is_packed = ! $item->is_packed;
            $item->save();

            return response()->json([
                'item' => new \App\Http\Resources\PackingItemResource($item),
            ]);
        } catch (\Exception $e) {
            Log::error("Error al alternar estado de ítem: {$e->getMessage()}");

            return response()->json([
                'message' => 'Error al actualizar el ítem',
            ], 500);
        }
    }

    public function addItem(Request $request, Trip $trip): JsonResponse
    {
        try {
            $this->authorize('update', $trip);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category' => 'required|in:Higiene,Ropa,Tecnología,Documentación',
            ], [
                'name.required' => 'El nombre del ítem es requerido',
                'category.required' => 'La categoría es requerida',
                'category.in' => 'La categoría debe ser: Higiene, Ropa, Tecnología o Documentación',
            ]);

            $packingList = PackingList::where('trip_id', $trip->id)->firstOrFail();

            $item = \App\Models\PackingItem::create([
                'packing_list_id' => $packingList->id,
                'name' => $validated['name'],
                'category' => $validated['category'],
                'is_packed' => false,
                'is_suggested' => false,
            ]);

            return response()->json([
                'item' => new \App\Http\Resources\PackingItemResource($item),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error al agregar ítem: {$e->getMessage()}");

            return response()->json([
                'message' => 'Error al agregar el ítem',
            ], 500);
        }
    }

    public function deleteItem(string $itemId): JsonResponse
    {
        try {
            $item = \App\Models\PackingItem::findOrFail($itemId);
            $packingList = $item->packingList;

            $this->authorize('update', $packingList->trip);

            $item->delete();

            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error("Error al eliminar ítem: {$e->getMessage()}");

            return response()->json([
                'message' => 'Error al eliminar el ítem',
            ], 500);
        }
    }
}
