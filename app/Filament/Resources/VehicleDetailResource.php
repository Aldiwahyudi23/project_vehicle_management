<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleDetailResource\Pages;
use App\Models\VehicleDetail;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VehicleDetailResource extends Resource
{
    protected static ?string $model = VehicleDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Vehicle Data';

    /* =====================================================
     | FORM
     ===================================================== */
    public static function form(Form $form): Form
    {
        return $form->schema([
            /* ================= BRAND ================= */
            Forms\Components\Select::make('brand_id')
                ->label('Brand')
                ->relationship('brand', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->reactive()
                ->afterStateUpdated(fn (callable $set) => [
                    $set('model_id', null),
                    $set('type_id', null),
                ]),

            /* ================= MODEL ================= */
            Forms\Components\Select::make('model_id')
                ->label('Model')
                ->relationship(
                    'model',
                    'name',
                    modifyQueryUsing: fn (Builder $query, callable $get) =>
                        $query->where('brand_id', $get('brand_id'))
                )
                ->required()
                ->searchable()
                ->preload()
                ->reactive()
                ->disabled(fn (callable $get) => ! $get('brand_id'))
                ->afterStateUpdated(fn (callable $set) => $set('type_id', null)),

            /* ================= TYPE ================= */
            Forms\Components\Select::make('type_id')
                ->label('Type')
                ->relationship(
                    'type',
                    'name',
                    modifyQueryUsing: fn (Builder $query, callable $get) =>
                        $query->where('model_id', $get('model_id'))
                )
                ->required()
                ->searchable()
                ->preload()
                ->disabled(fn (callable $get) => ! $get('model_id')),

            /* ================= TRANSMISSION ================= */
            Forms\Components\Select::make('transmission_id')
                ->label('Transmission')
                ->relationship('transmission', 'name')
                ->required()
                ->searchable()
                ->preload(),

            /* ================= ORIGIN ================= */
            Forms\Components\Select::make('origin_id')
                ->label('Origin')
                ->relationship('origin', 'name')
                ->required()
                ->searchable()
                ->preload(),

            /* ================= BASIC INFO ================= */
            Forms\Components\TextInput::make('year')
                ->numeric()
                ->required()
                ->minValue(1900)
                ->maxValue(now()->year + 1),

            Forms\Components\TextInput::make('cc')
                ->numeric()
                ->label('Engine CC')
                ->suffix('cc'),

            Forms\Components\Select::make('fuel_type')
                ->label('Fuel Type')
                ->options([
                    'Bensin' => 'Bensin',
                    'Diesel' => 'Diesel',
                    'Electric' => 'Electric',
                    'Hybrid' => 'Hybrid',
                    'Plug-in Hybrid' => 'Plug-in Hybrid',
                ])
                ->required(),

            Forms\Components\TextInput::make('engine_type')
                ->maxLength(255),

            Forms\Components\TextInput::make('generation')
                ->maxLength(255),

            Forms\Components\TextInput::make('market_period')
                ->maxLength(255),

                Forms\Components\RichEditor::make('description')
                    ->label('Deskripsi')
                    ->toolbarButtons([
                        'attachFiles',
                        'blockquote',
                        'bold',
                        'bulletList',
                        'codeBlock',
                        'h2',
                        'h3',
                        'italic',
                        'link',
                        'orderedList',
                        'redo',
                        'strike',
                        'underline',
                        'undo',
                    ])
                    ->fileAttachmentsDirectory('descriptions') // Folder untuk upload file
                    ->placeholder('Masukkan deskripsi di sini...')
                    ->helperText('Deskripsi tambahan tentang Kendaraan. Format HTML akan dipertahankan.')
                    ->columnSpanFull(),

            Forms\Components\FileUpload::make('image_path')
                ->image()
                ->directory('vehicle-details')
                ->columnSpanFull(),

            Forms\Components\Select::make('features')
                ->label('Features')
                ->relationship('features', 'name')
                ->multiple()
                ->preload()
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),


                /* =========================
                 * specifications (JSON)
                 * ========================= */
                Forms\Components\Section::make('Vehicle Specifications (Inspection)')
                    ->description('Digunakan untuk menentukan form & logic inspeksi')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('specifications.doors')
                                    ->numeric()
                                    ->label('Number of Doors')
                                    ->helperText('Contoh: 2 atau 4'),

                                Forms\Components\Select::make('specifications.drive')
                                    ->label('Drive Type')
                                    ->options([
                                        'FWD' => 'FWD',
                                        'RWD' => 'RWD',
                                        'AWD' => 'AWD',
                                        '4WD' => '4WD',
                                    ]),
                            ]),

                        Forms\Components\Toggle::make('specifications.pickup')
                            ->label('Pickup')
                            ->inline(false),

                        Forms\Components\Toggle::make('specifications.box')
                            ->label('Box / Cargo')
                            ->inline(false),
                    ]),
        ]);
    }

    /* =====================================================
     | TABLE
     ===================================================== */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Vehicle')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function ($q) use ($search) {
                            $q->whereHas('brand', fn ($subQ) => $subQ->where('name', 'like', "%{$search}%"))
                              ->orWhereHas('model', fn ($subQ) => $subQ->where('name', 'like', "%{$search}%"))
                              ->orWhereHas('type', fn ($subQ) => $subQ->where('name', 'like', "%{$search}%"))
                              ->orWhere('cc', 'like', "%{$search}%")
                              ->orWhereHas('transmission', fn ($subQ) => $subQ->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->sortable(['brand.name']),

                Tables\Columns\TextColumn::make('year')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('specification_summary')
                    ->label('Specification')
                    ->wrap(),

                Tables\Columns\TextColumn::make('fuel_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) =>
                        ucfirst(str_replace('_', ' ', $state))
                    ),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])

            /* ================= FILTERS ================= */
            ->filters([
                Tables\Filters\Filter::make('vehicle_relation')
                    ->form([
                        Forms\Components\Select::make('brand_id')
                            ->label('Brand')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => [
                                $set('model_id', null),
                                $set('type_id', null),
                            ]),

                        Forms\Components\Select::make('model_id')
                            ->label('Model')
                            ->options(fn (callable $get) =>
                                VehicleModel::where('brand_id', $get('brand_id'))
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->disabled(fn (callable $get) => ! $get('brand_id'))
                            ->afterStateUpdated(fn (callable $set) => $set('type_id', null)),

                        Forms\Components\Select::make('type_id')
                            ->label('Type')
                            ->options(fn (callable $get) =>
                                VehicleType::where('model_id', $get('model_id'))
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->disabled(fn (callable $get) => ! $get('model_id')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['brand_id'] ?? null,
                                fn ($q, $brand) => $q->where('brand_id', $brand)
                            )
                            ->when($data['model_id'] ?? null,
                                fn ($q, $model) => $q->where('model_id', $model)
                            )
                            ->when($data['type_id'] ?? null,
                                fn ($q, $type) => $q->where('type_id', $type)
                            );
                    }),

                Tables\Filters\SelectFilter::make('fuel_type')
                    ->options([
                        'Bensin' => 'Bensin',
                        'Diesel' => 'Diesel',
                        // 'Electric' => 'Electric',
                        'Listrik' => 'Listrik',
                        'Hybrid' => 'Hybrid',
                        'Plug-in Hybrid' => 'Plug-in Hybrid',
                    ]),

                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
            ])

            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /* =====================================================
     | PAGES
     ===================================================== */
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVehicleDetails::route('/'),
            'create' => Pages\CreateVehicleDetail::route('/create'),
            'view' => Pages\ViewVehicleDetails::route('/{record}'),
            'edit'   => Pages\EditVehicleDetail::route('/{record}/edit'),
        ];
    }
}
