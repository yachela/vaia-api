<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use App\Models\Expense;
use App\Models\Trip;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Usuarios', User::count())
                ->description('Total de usuarios registrados')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Viajes', Trip::count())
                ->description('Total de viajes creados')
                ->icon('heroicon-o-map-pin')
                ->color('success'),

            Stat::make('Actividades', Activity::count())
                ->description('Total de actividades')
                ->icon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Gastos', Expense::count())
                ->description('Total de gastos registrados')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning'),
        ];
    }
}
