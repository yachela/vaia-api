<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class TripsRelationManager extends RelationManager
{
    protected static string $relationship = 'trips';

    protected static ?string $title = 'Viajes';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('Título')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('destination')
                ->label('Destino')
                ->required()
                ->maxLength(255),
            Forms\Components\DatePicker::make('start_date')
                ->label('Fecha inicio')
                ->required(),
            Forms\Components\DatePicker::make('end_date')
                ->label('Fecha fin')
                ->required(),
            Forms\Components\TextInput::make('budget')
                ->label('Presupuesto')
                ->numeric()
                ->prefix('$'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Viaje')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('destination')
                    ->label('Destino')
                    ->icon('heroicon-m-map-pin'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y'),
                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->getStateUsing(function ($record): string {
                        $hoy = Carbon::today();
                        if ($record->start_date->gt($hoy)) return 'Próximo';
                        if ($record->end_date->lt($hoy)) return 'Finalizado';
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
            ->defaultSort('start_date', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
