<?php

namespace App\Filament\Resources\TripResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Actividades';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('Título')
                ->required()
                ->maxLength(255),
            Forms\Components\DatePicker::make('date')
                ->label('Fecha')
                ->required(),
            Forms\Components\TextInput::make('time')
                ->label('Hora'),
            Forms\Components\TextInput::make('location')
                ->label('Lugar'),
            Forms\Components\TextInput::make('cost')
                ->label('Costo')
                ->numeric()
                ->prefix('$'),
            Forms\Components\Textarea::make('description')
                ->label('Descripción')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Actividad')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('time')
                    ->label('Hora'),
                Tables\Columns\TextColumn::make('location')
                    ->label('Lugar')
                    ->icon('heroicon-m-map-pin'),
                Tables\Columns\TextColumn::make('cost')
                    ->label('Costo')
                    ->money('USD'),
            ])
            ->defaultSort('date')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
