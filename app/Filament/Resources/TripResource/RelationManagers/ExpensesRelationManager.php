<?php

namespace App\Filament\Resources\TripResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    protected static ?string $title = 'Gastos';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('description')
                ->label('Descripción')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('amount')
                ->label('Monto')
                ->numeric()
                ->required()
                ->prefix('$'),
            Forms\Components\DatePicker::make('date')
                ->label('Fecha')
                ->required(),
            Forms\Components\Select::make('category')
                ->label('Categoría')
                ->options([
                    'Alojamiento'  => 'Alojamiento',
                    'Transporte'   => 'Transporte',
                    'Comida'       => 'Comida',
                    'Actividades'  => 'Actividades',
                    'Compras'      => 'Compras',
                    'Salud'        => 'Salud',
                    'Comunicación' => 'Comunicación',
                    'Otros'        => 'Otros',
                ])
                ->searchable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('category')
                    ->label('Categoría')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
