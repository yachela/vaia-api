<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Documentos';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('trip_id')
                ->label('Viaje')
                ->relationship('trip', 'title')
                ->required(),
            Forms\Components\Select::make('user_id')
                ->label('Usuario')
                ->relationship('user', 'name')
                ->required(),
            Forms\Components\TextInput::make('file_name')
                ->label('Nombre de archivo')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('category')
                ->label('Categoría')
                ->options([
                    'Pasaporte'       => 'Pasaporte',
                    'Visa'            => 'Visa',
                    'Seguro de viaje' => 'Seguro de viaje',
                    'Reserva vuelo'   => 'Reserva vuelo',
                    'Reserva hotel'   => 'Reserva hotel',
                    'Itinerario'      => 'Itinerario',
                    'Médico'          => 'Médico',
                    'Otros'           => 'Otros',
                ])
                ->searchable(),
            Forms\Components\Textarea::make('description')
                ->label('Descripción')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_name')
                    ->label('Archivo')
                    ->searchable()
                    ->icon('heroicon-m-document-text'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable(),
                Tables\Columns\TextColumn::make('trip.title')
                    ->label('Viaje')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('category')
                    ->label('Categoría')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('mime_type')
                    ->label('Tipo')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('file_size')
                    ->label('Tamaño')
                    ->formatStateUsing(fn ($state) => $state
                        ? number_format($state / 1024, 1) . ' KB'
                        : '-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Subido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoría')
                    ->options([
                        'Pasaporte'       => 'Pasaporte',
                        'Visa'            => 'Visa',
                        'Seguro de viaje' => 'Seguro de viaje',
                        'Reserva vuelo'   => 'Reserva vuelo',
                        'Reserva hotel'   => 'Reserva hotel',
                        'Itinerario'      => 'Itinerario',
                        'Médico'          => 'Médico',
                        'Otros'           => 'Otros',
                    ]),
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
            'index' => Pages\ListDocuments::route('/'),
        ];
    }
}
