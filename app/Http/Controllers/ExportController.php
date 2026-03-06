<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    /**
     * Exporta el itinerario de actividades del viaje como PDF.
     * GET /api/trips/{trip}/export/itinerary.pdf
     */
    public function itineraryPdf(Trip $trip): mixed
    {
        $this->authorize('view', $trip);

        $activities = $trip->activities()
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $grouped = $activities->groupBy(fn ($a) => $a->date->format('Y-m-d'));
        $totalExpenses = $trip->expenses()->sum('amount');

        $pdf = Pdf::loadView('exports.itinerary', [
            'trip' => $trip,
            'activities' => $activities,
            'grouped' => $grouped,
            'totalExpenses' => $totalExpenses,
        ])->setPaper('a4', 'portrait');

        $filename = 'itinerario-'.str($trip->title)->slug().'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Exporta los gastos del viaje como CSV.
     * GET /api/trips/{trip}/export/expenses.csv
     */
    public function expensesCsv(Trip $trip): mixed
    {
        $this->authorize('view', $trip);

        $expenses = $trip->expenses()
            ->orderBy('date')
            ->get();

        $filename = 'gastos-'.str($trip->title)->slug().'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($trip, $expenses) {
            $handle = fopen('php://output', 'w');

            // BOM para compatibilidad con Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Fecha', 'Descripción', 'Categoría', 'Monto'], ';');

            foreach ($expenses as $expense) {
                fputcsv($handle, [
                    $expense->date->format('Y-m-d'),
                    $expense->description,
                    $expense->category ?? '',
                    number_format($expense->amount, 2, '.', ''),
                ], ';');
            }

            // Totales
            fputcsv($handle, [], ';');
            fputcsv($handle, ['', '', 'TOTAL', number_format($expenses->sum('amount'), 2, '.', '')], ';');
            fputcsv($handle, ['', '', 'PRESUPUESTO', number_format($trip->budget, 2, '.', '')], ';');

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
