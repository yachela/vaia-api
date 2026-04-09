<?php

namespace App\Providers\Filament;

use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\ExpenseResource;
use App\Filament\Resources\TripResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\StatsOverviewWidget;
use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => '#1565C0',
            ])
            ->resources([
                ActivityResource::class,
                ExpenseResource::class,
                TripResource::class,
                UserResource::class,
            ])
            ->widgets([
                StatsOverviewWidget::class,
            ]);
    }
}
