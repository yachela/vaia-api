<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ExpensesByMonthWidget extends ChartWidget
{
    protected static ?string $heading = 'Gastos por mes';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'half';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Últimos 6 meses
        $meses  = collect();
        $montos = collect();

        for ($i = 5; $i >= 0; $i--) {
            $fecha = Carbon::now()->subMonths($i);
            $meses->push($fecha->translatedFormat('M Y'));
            $montos->push(
                Expense::whereYear('date', $fecha->year)
                    ->whereMonth('date', $fecha->month)
                    ->sum('amount')
            );
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Gastos (USD)',
                    'data'            => $montos->toArray(),
                    'borderColor'     => '#1565C0',
                    'backgroundColor' => 'rgba(21, 101, 192, 0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointBackgroundColor' => '#1565C0',
                    'pointRadius'     => 4,
                ],
            ],
            'labels' => $meses->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
