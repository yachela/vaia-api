<?php

namespace App\Console\Commands;

use App\Jobs\SendActivityReminderJob;
use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScheduleActivityReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:schedule-activities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Programar recordatorios de actividades para el día siguiente a las 9:00 AM';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $tomorrow = Carbon::tomorrow();

            // Buscar todas las actividades del día siguiente
            $activities = Activity::whereDate('date', $tomorrow)
                ->with('trip.user')
                ->get();

            if ($activities->isEmpty()) {
                $this->info('No hay actividades programadas para mañana');
                Log::info('No hay actividades programadas para '.Carbon::tomorrow()->toDateString());

                return Command::SUCCESS;
            }

            $scheduledCount = 0;

            foreach ($activities as $activity) {
                // Verificar que el usuario tenga habilitados los recordatorios
                if (! $activity->trip->user->activity_reminders) {
                    continue;
                }

                // Despachar job para enviar recordatorio
                SendActivityReminderJob::dispatch($activity);
                $scheduledCount++;
            }

            $this->info("Se programaron {$scheduledCount} recordatorios de actividades");
            Log::info("Se programaron {$scheduledCount} recordatorios de actividades para ".Carbon::tomorrow()->toDateString());

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error al programar recordatorios: '.$e->getMessage());
            Log::error('Error al programar recordatorios de actividades: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
