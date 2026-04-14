<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentExpensesWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'half';

    protected static ?string $heading = 'Últimos gastos';

    public function table(Table $table): Table
    {
        return $table
            ->query(Expense::with(['trip', 'trip.user'])->latest('date')->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(30),
                Tables\Columns\TextColumn::make('trip.user.name')
                    ->label('Usuario'),
                Tables\Columns\TextColumn::make('trip.title')
                    ->label('Viaje')
                    ->limit(20),
                Tables\Columns\BadgeColumn::make('category')
                    ->label('Categoría')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('USD')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y'),
            ])
            ->paginated(false);
    }
}
