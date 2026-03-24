<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendActivityReminderJob implements ShouldQueue
{
    use Queueable;

    /**
     * Número de intentos antes de fallar
     */
    public int $tries = 3;

    /**
     * Tiempo de espera entre reintentos (en segundos)
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Activity $activity
    ) {}

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            // Cargar relaciones necesarias
            $this->activity->load('trip.user');

            $user = $this->activity->trip->user;

            // Verificar preferencias de notificaciones del usuario
            if (! $user->activity_reminders) {
                Log::info("Usuario {$user->id} tiene deshabilitados los recordatorios de actividades");

                return;
            }

            // Preparar datos de la notificación
            $notificationData = [
                'title' => 'Recordatorio de Actividad',
                'body' => "Tienes '{$this->activity->title}' programada para hoy a las {$this->activity->time->format('H:i')}",
                'data' => [
                    'type' => 'activity_reminder',
                    'activity_id' => $this->activity->id,
                    'trip_id' => $this->activity->trip_id,
                    'title' => $this->activity->title,
                    'time' => $this->activity->time->format('H:i'),
                    'location' => $this->activity->location ?? '',
                ],
            ];

            // Enviar notificación
            $sent = $notificationService->sendPushNotification($user, $notificationData);

            if ($sent) {
                Log::info("Recordatorio de actividad {$this->activity->id} enviado al usuario {$user->id}");
            } else {
                Log::warning("No se pudo enviar recordatorio de actividad {$this->activity->id} al usuario {$user->id}");
            }
        } catch (\Exception $e) {
            Log::error("Error al enviar recordatorio de actividad {$this->activity->id}: ".$e->getMessage());
            throw $e; // Re-lanzar para que el job se reintente
        }
    }
}
