<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChecklistResource\Pages;
use App\Models\TripDocumentChecklist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ChecklistResource extends Resource
{
    protected static ?string $model = TripDocumentChecklist::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Checklists';

    protected static ?string $navigationGroup = 'Viajes';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('trip_id')
                ->label('Viaje')
                ->relationship('trip', 'title')
                ->searchable()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('trip.title')
                    ->label('Viaje')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('trip.user.name')
                    ->label('Usuario')
                    ->searchable(),
                Tables\Columns\TextColumn::make('trip.destination')
                    ->label('Destino')
                    ->icon('heroicon-m-map-pin'),
                Tables\Columns\TextColumn::make('progreso')
                    ->label('Progreso')
                    ->getStateUsing(function (TripDocumentChecklist $record): string {
                        $total     = $record->items()->count();
                        $completados = $record->items()->where('is_completed', true)->count();
                        if ($total === 0) return '—';
                        $pct = round(($completados / $total) * 100);
                        return "{$completados}/{$total} ({$pct}%)";
                    }),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items totales')
                    ->counts('items'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChecklists::route('/'),
        ];
    }
}
