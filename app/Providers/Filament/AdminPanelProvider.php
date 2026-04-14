<?php

namespace App\Providers\Filament;

use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\ChecklistResource;
use App\Filament\Resources\DocumentResource;
use App\Filament\Resources\ExpenseResource;
use App\Filament\Resources\PackingListResource;
use App\Filament\Resources\TripResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\ExpensesByCategoryWidget;
use App\Filament\Widgets\ExpensesByMonthWidget;
use App\Filament\Widgets\RecentExpensesWidget;
use App\Filament\Widgets\RecentTripsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Pages\Dashboard;
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
            ->pages([
                Dashboard::class,
            ])
            ->colors([
                'primary' => '#1565C0',
            ])
            ->resources([
                UserResource::class,
                TripResource::class,
                ActivityResource::class,
                ExpenseResource::class,
                DocumentResource::class,
                PackingListResource::class,
                ChecklistResource::class,
            ])
            ->widgets([
                StatsOverviewWidget::class,
                RecentTripsWidget::class,
                ExpensesByMonthWidget::class,
                ExpensesByCategoryWidget::class,
                RecentExpensesWidget::class,
            ]);
    }
}
