<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class RecentTripsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Viajes recientes';

    public function table(Table $table): Table
    {
        return $table
            ->query(Trip::with('user')->latest()->limit(8))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Viaje')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable(),
                Tables\Columns\TextColumn::make('destination')
                    ->label('Destino')
                    ->icon('heroicon-m-map-pin'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y'),
                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->getStateUsing(function (Trip $record): string {
                        $hoy = Carbon::today();
                        if ($record->start_date->gt($hoy)) {
                            return 'Próximo';
                        }
                        if ($record->end_date->lt($hoy)) {
                            return 'Finalizado';
                        }
                        return 'En curso';
                    })
                    ->colors([
                        'success' => 'En curso',
                        'primary' => 'Próximo',
                        'gray'    => 'Finalizado',
                    ]),
                Tables\Columns\TextColumn::make('budget')
                    ->label('Presupuesto')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('expenses_count')
                    ->label('Gastos')
                    ->counts('expenses'),
            ])
            ->paginated(false);
    }
}
