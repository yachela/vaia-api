<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\Trip;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * Display all activities for the authenticated user across all trips.
     * GET /api/activities
     */
    public function all(Request $request)
    {
        try {
            $user = $request->user();
            $tripIds = $user->trips()->select('id')->pluck('id');

            $activities = Activity::whereIn('trip_id', $tripIds)
                ->orderBy('date')
                ->orderBy('time')
                ->paginate(50);

            return ActivityResource::collection($activities);
        } catch (\Exception $e) {
            Log::error('Error al obtener todas las actividades: '.$e->getMessage());

            return response()->json(['message' => 'Error al obtener las actividades.'], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * Lista actividades de un viaje específico, verifica autorización.
     */
    public function index(Trip $trip)
    {
        $this->authorize('view', $trip);

        try {
            $activities = $trip->activities()->paginate(15);

            return ActivityResource::collection($activities);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener las actividades.'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * Crea actividad para el viaje, verifica autorización.
     */
    public function store(StoreActivityRequest $request, Trip $trip)
    {
        $this->authorize('update', $trip); // Solo owner puede agregar actividades

        try {
            $activity = $trip->activities()->create($request->validated());

            return new ActivityResource($activity);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear la actividad.'], 500);
        }
    }

    /**
     * Display the specified resource.
     * Muestra actividad específica, verifica que pertenezca al viaje autorizado.
     */
    public function show(Trip $trip, Activity $activity)
    {
        $this->authorize('view', $trip);

        if ($activity->trip_id !== $trip->id) {
            return response()->json(['message' => 'Actividad no pertenece al viaje.'], 404);
        }

        return new ActivityResource($activity);
    }

    /**
     * Update the specified resource in storage.
     * Actualiza actividad, verifica autorización del viaje.
     */
    public function update(UpdateActivityRequest $request, Trip $trip, Activity $activity)
    {
        $this->authorize('update', $trip);

        if ($activity->trip_id !== $trip->id) {
            return response()->json(['message' => 'Actividad no pertenece al viaje.'], 404);
        }

        try {
            $activity->update($request->validated());

            return new ActivityResource($activity);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar la actividad.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Soft delete actividad, verifica autorización.
     */
    public function destroy(Trip $trip, Activity $activity)
    {
        $this->authorize('update', $trip);

        if ($activity->trip_id !== $trip->id) {
            return response()->json(['message' => 'Actividad no pertenece al viaje.'], 404);
        }

        try {
            $activity->delete();

            return response()->json(['message' => 'Actividad eliminada correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar la actividad.'], 500);
        }
    }
}
