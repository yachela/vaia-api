<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class NotificationService
{
    /**
     * Enviar notificación push a un usuario mediante FCM
     */
    public function sendPushNotification(User $user, array $data): bool
    {
        try {
            // Verificar que el usuario tenga token FCM
            if (empty($user->fcm_token)) {
                Log::warning("Usuario {$user->id} no tiene token FCM registrado");

                return false;
            }

            // Verificar credenciales de Firebase (soporta JSON string para entornos cloud)
            $credentialsJson = env('FIREBASE_CREDENTIALS_JSON');
            $credentialsPath = config('firebase.credentials.file');

            if (! empty($credentialsJson)) {
                $credentials = json_decode($credentialsJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('FIREBASE_CREDENTIALS_JSON no es un JSON válido');

                    return false;
                }
                $factory = (new Factory)->withServiceAccount($credentials);
            } elseif (! empty($credentialsPath) && file_exists($credentialsPath)) {
                $factory = (new Factory)->withServiceAccount($credentialsPath);
            } else {
                Log::error('Credenciales de Firebase no configuradas o no encontradas');

                return false;
            }

            $messaging = $factory->createMessaging();

            // Crear notificación
            $notification = Notification::create(
                $data['title'] ?? 'VAIA',
                $data['body'] ?? ''
            );

            // Crear mensaje
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification($notification);

            // Agregar datos adicionales si existen
            if (isset($data['data'])) {
                $message = $message->withData($data['data']);
            }

            // Enviar notificación
            $messaging->send($message);

            Log::info("Notificación enviada exitosamente al usuario {$user->id}");

            return true;
        } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
            Log::error("Token FCM inválido para usuario {$user->id}: ".$e->getMessage());

            // Eliminar token inválido
            $user->fcm_token = null;
            $user->save();

            return false;
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            Log::error("Error de Firebase Messaging para usuario {$user->id}: ".$e->getMessage());

            return false;
        } catch (\Exception $e) {
            Log::error("Error al enviar notificación push al usuario {$user->id}: ".$e->getMessage());

            return false;
        }
    }
}
