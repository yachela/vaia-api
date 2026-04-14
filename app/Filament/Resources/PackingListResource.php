<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackingListResource\Pages;
use App\Models\PackingList;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PackingListResource extends Resource
{
    protected static ?string $model = PackingList::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Equipaje';

    protected static ?string $navigationGroup = 'Viajes';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('trip_id')
                ->label('Viaje')
                ->relationship('trip', 'title')
                ->searchable()
                ->required(),
            Forms\Components\Select::make('user_id')
                ->label('Usuario')
                ->relationship('user', 'name')
                ->searchable()
                ->required(),
            Forms\Components\Select::make('status')
                ->label('Estado')
                ->options([
                    'pending'    => 'Pendiente',
                    'in_progress' => 'En progreso',
                    'completed'  => 'Completado',
                ]),
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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending'     => 'Pendiente',
                        'in_progress' => 'En progreso',
                        'completed'   => 'Completado',
                        default       => $state,
                    })
                    ->colors([
                        'gray'    => 'pending',
                        'warning' => 'in_progress',
                        'success' => 'completed',
                    ]),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items'),
                Tables\Columns\TextColumn::make('items_packed')
                    ->label('Empacados')
                    ->getStateUsing(fn (PackingList $record) =>
                        $record->items()->where('is_packed', true)->count()
                        . ' / ' .
                        $record->items()->count()
                    ),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending'     => 'Pendiente',
                        'in_progress' => 'En progreso',
                        'completed'   => 'Completado',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackingLists::route('/'),
        ];
    }
}
