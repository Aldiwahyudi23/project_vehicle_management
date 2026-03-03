<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\MasterData\VehicleTypeResource\Pages;
use App\Models\VehicleType;
use App\Models\VehicleTypeBody;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VehicleTypeResource extends Resource
{
    protected static ?string $model = VehicleType::class;

    // Hidden from navigation
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('model_id')
                    ->relationship('model', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->rules(function () {
                        return [
                            \Illuminate\Validation\Rule::unique('vehicle_types', 'name')
                                ->where('model_id', $this->getOwnerRecord()->id)
                                ->where('is_active', true)
                                ->ignore($this->getMountedTableActionRecord()?->id),
                        ];
                    })
                    ->validationMessages([
                        'unique' => 'Variant ini sudah ada dan masih aktif untuk model ini.',
                    ]),

                Forms\Components\Select::make('type_body_id')
                    ->label('Body Type')
                    ->options(
                        VehicleTypeBody::query()
                            ->where('is_active', true)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('model.name')
                    ->label('Model')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Type')
                    ->searchable(),

                Tables\Columns\TextColumn::make('typeBody.name')
                    ->label('Body Type')
                    ->badge(),

                Tables\Columns\TextColumn::make('year_start')
                    ->label('Start')
                    ->sortable(),

                Tables\Columns\TextColumn::make('year_end')
                    ->label('End')
                    ->sortable(),

                Tables\Columns\TextColumn::make('attributes.doors')
                    ->label('Doors')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('attributes.drive')
                    ->label('Drive'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('model_id')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicleTypes::route('/'),
            'create' => Pages\CreateVehicleType::route('/create'),
            'edit' => Pages\EditVehicleType::route('/{record}/edit'),
        ];
    }
}
