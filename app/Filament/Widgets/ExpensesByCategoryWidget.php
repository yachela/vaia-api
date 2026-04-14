<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;

class ExpensesByCategoryWidget extends ChartWidget
{
    protected static ?string $heading = 'Gastos por categoría';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'half';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $datos = Expense::query()
            ->selectRaw('COALESCE(NULLIF(category, ""), "Sin categoría") as categoria, SUM(amount) as total')
            ->groupBy('categoria')
            ->orderByDesc('total')
            ->limit(8)
            ->pluck('total', 'categoria')
            ->toArray();

        $colores = [
            '#1565C0', '#1976D2', '#1E88E5', '#2196F3',
            '#42A5F5', '#64B5F6', '#90CAF9', '#BBDEFB',
        ];

        return [
            'datasets' => [
                [
                    'data'            => array_values($datos),
                    'backgroundColor' => array_slice($colores, 0, count($datos)),
                    'borderWidth'     => 2,
                    'borderColor'     => '#1a1a2e',
                ],
            ],
            'labels' => array_keys($datos),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
