<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Gastos';

    protected static ?int $navigationSort = 4;

    // Categorías canónicas compartidas con la app Android
    public static function categorias(): array
    {
        return [
            'Alojamiento'  => 'Alojamiento',
            'Transporte'   => 'Transporte',
            'Comida'       => 'Comida',
            'Actividades'  => 'Actividades',
            'Compras'      => 'Compras',
            'Salud'        => 'Salud',
            'Comunicación' => 'Comunicación',
            'Otros'        => 'Otros',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('trip_id')
                    ->label('Viaje')
                    ->relationship('trip', 'title')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->label('Descripción')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->label('Monto')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\DatePicker::make('date')
                    ->label('Fecha')
                    ->required(),
                Forms\Components\Select::make('category')
                    ->label('Categoría')
                    ->options(self::categorias())
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('trip.title')
                    ->label('Viaje')
                    ->searchable(),
                Tables\Columns\TextColumn::make('trip.user.name')
                    ->label('Usuario')
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
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoría')
                    ->options(self::categorias()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportar_csv')
                    ->label('Exportar CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (): StreamedResponse {
                        $gastos = Expense::with(['trip', 'trip.user'])->get();
                        return response()->streamDownload(function () use ($gastos) {
                            $csv = fopen('php://output', 'w');
                            fputcsv($csv, ['ID', 'Descripción', 'Viaje', 'Usuario', 'Categoría', 'Monto', 'Fecha']);
                            foreach ($gastos as $g) {
                                fputcsv($csv, [
                                    $g->id,
                                    $g->description,
                                    $g->trip?->title,
                                    $g->trip?->user?->name,
                                    $g->category,
                                    $g->amount,
                                    $g->date,
                                ]);
                            }
                            fclose($csv);
                        }, 'gastos_' . now()->format('Y-m-d') . '.csv');
                    }),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit'   => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
