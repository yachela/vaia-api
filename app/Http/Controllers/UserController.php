<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Guardar token FCM del dispositivo
     */
    public function storeFcmToken(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'fcm_token' => 'required|string',
            ], [
                'fcm_token.required' => 'El token FCM es requerido',
                'fcm_token.string' => 'El token FCM debe ser una cadena de texto',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = $request->user();
            $user->fcm_token = $request->fcm_token;
            $user->save();

            return response()->json([
                'message' => 'Token FCM guardado exitosamente',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al guardar token FCM: '.$e->getMessage());

            return response()->json([
                'message' => 'Error al guardar el token FCM',
            ], 500);
        }
    }

    /**
     * Eliminar token FCM del dispositivo (al cerrar sesión)
     */
    public function deleteFcmToken(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->fcm_token = null;
            $user->save();

            return response()->json([
                'message' => 'Token FCM eliminado exitosamente',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al eliminar token FCM: '.$e->getMessage());

            return response()->json([
                'message' => 'Error al eliminar el token FCM',
            ], 500);
        }
    }

    /**
     * Actualizar preferencias de notificaciones del usuario
     */
    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'activity_reminders' => 'sometimes|boolean',
                'trip_reminders' => 'sometimes|boolean',
            ], [
                'activity_reminders.boolean' => 'El campo de recordatorios de actividades debe ser verdadero o falso',
                'trip_reminders.boolean' => 'El campo de recordatorios de viajes debe ser verdadero o falso',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = $request->user();

            if ($request->has('activity_reminders')) {
                $user->activity_reminders = $request->activity_reminders;
            }

            if ($request->has('trip_reminders')) {
                $user->trip_reminders = $request->trip_reminders;
            }

            $user->save();

            return response()->json([
                'message' => 'Preferencias de notificaciones actualizadas exitosamente',
                'data' => [
                    'activity_reminders' => $user->activity_reminders,
                    'trip_reminders' => $user->trip_reminders,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar preferencias de notificaciones: '.$e->getMessage());

            return response()->json([
                'message' => 'Error al actualizar las preferencias de notificaciones',
            ], 500);
        }
    }
}
