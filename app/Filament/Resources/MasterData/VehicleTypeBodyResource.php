<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\MasterData\VehicleTypeBodyResource\Pages;
use App\Models\VehicleTypeBody;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VehicleTypeBodyResource extends Resource
{
    protected static ?string $model = VehicleTypeBody::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Vehicle Body Types';

    protected static ?string $pluralModelLabel = 'Vehicle Body Types';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Body Type Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('SUV, MPV, Pickup')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (string $state, callable $set) {
                                $set(
                                    'code',
                                    str($state)
                                        ->lower()
                                        ->replace(' ', '')
                                        ->replaceMatches('/[^a-z0-9]/', '')
                                );
                            })
                            ->rules(fn (callable $get, ?\App\Models\VehicleTypeBody $record) => [
                                \Illuminate\Validation\Rule::unique('vehicle_type_bodies', 'name')
                                    ->ignore($record?->id),
                            ])
                            ->validationMessages([
                                'unique' => 'Body Type ini sudah ada.',
                            ]),

                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(50)
                            ->disabled()
                            ->dehydrated() // 🔥 WAJIB supaya tetap tersimpan
                            ->placeholder('suv, mpv, pickup')
                            ->helperText('Gunakan huruf kecil & tanpa spasi'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->placeholder('Deskripsi singkat body type'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Body Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->description(fn ($record) => 
                        str($record->description)->limit(40)
                    ),

                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Code copied'),

                Tables\Columns\TextColumn::make('vehicleTypes_count')
                    ->label('Used By Types')
                    ->counts('vehicleTypes')
                    ->alignCenter(),

                Tables\Columns\ToggleColumn::make('is_active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
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
        return [
            // nanti bisa ditambah RelationManager VehicleType kalau mau
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicleTypeBodies::route('/'),
            // 'create' => Pages\CreateVehicleTypeBody::route('/create'),
            // 'edit' => Pages\EditVehicleTypeBody::route('/{record}/edit'),
        ];
    }
}
