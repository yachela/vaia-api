<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use App\Models\Expense;
use App\Models\Trip;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $inicioMes    = Carbon::now()->startOfMonth();
        $usuariosMes  = User::where('created_at', '>=', $inicioMes)->count();
        $viajesActivos = Trip::where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->count();
        $totalGastado = Expense::sum('amount');
        $presupuestoTotal = Trip::sum('budget');

        return [
            Stat::make('Usuarios', User::count())
                ->description("+{$usuariosMes} este mes")
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Viajes activos', $viajesActivos)
                ->description(Trip::count() . ' viajes en total')
                ->icon('heroicon-o-map-pin')
                ->color('success'),

            Stat::make('Total gastado', '$' . number_format($totalGastado, 2))
                ->description('Presupuesto total: $' . number_format($presupuestoTotal, 2))
                ->icon('heroicon-o-banknotes')
                ->color('warning'),

            Stat::make('Actividades', Activity::count())
                ->description(Expense::count() . ' gastos registrados')
                ->icon('heroicon-o-calendar-days')
                ->color('info'),
        ];
    }
}
