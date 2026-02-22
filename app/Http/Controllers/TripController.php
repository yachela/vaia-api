<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripRequest;
use App\Http\Requests\UpdateTripRequest;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     * Obtiene los viajes del usuario autenticado con paginación de 15, eager load de conteos.
     */
    public function index()
    {
        try {
            $trips = Auth::user()->trips()
                ->withCount(['activities', 'expenses'])
                ->paginate(15);

            return TripResource::collection($trips);
        } catch (\Exception $e) {
            Log::error('Error al obtener los viajes: '.$e->getMessage());

            return response()->json(['message' => 'Error interno del servidor al obtener los viajes.'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * Crea un nuevo viaje, asigna al usuario autenticado, retorna 201 con recurso.
     */
    public function store(StoreTripRequest $request)
    {
        try {
            $trip = Trip::create([
                'user_id' => Auth::id(),
                ...$request->validated(),
            ]);

            return new TripResource($trip);
        } catch (\Exception $e) {
            Log::error('Error al crear el viaje: '.$e->getMessage());

            return response()->json(['message' => 'Error interno del servidor al crear el viaje.'], 500);
        }
    }

    /**
     * Display the specified resource.
     * Muestra un viaje específico con actividades y gastos.
     */
    public function show(Trip $trip)
    {
        $this->authorize('view', $trip);

        try {
            $trip->load(['activities', 'expenses']);

            return new TripResource($trip);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener el viaje.'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * Actualiza el viaje, verifica autorización.
     */
    public function update(UpdateTripRequest $request, Trip $trip)
    {
        $this->authorize('update', $trip);

        try {
            $trip->update($request->validated());

            return new TripResource($trip);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el viaje.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Soft delete del viaje, verifica autorización.
     */
    public function destroy(Trip $trip)
    {
        $this->authorize('delete', $trip);

        try {
            $trip->delete();

            return response()->json(['message' => 'Viaje eliminado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el viaje.'], 500);
        }
    }
}
