<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ExpensesByCategoryWidget;
use App\Filament\Widgets\ExpensesByMonthWidget;
use App\Filament\Widgets\RecentExpensesWidget;
use App\Filament\Widgets\RecentTripsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Panel de control';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    public function getColumns(): int | string | array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            RecentTripsWidget::class,
            ExpensesByMonthWidget::class,
            ExpensesByCategoryWidget::class,
            RecentExpensesWidget::class,
        ];
    }
}
