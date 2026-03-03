<?php

namespace App\Filament\Resources\MasterData\VehicleModelResource\RelationManagers;

use App\Models\VehicleTypeBody;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TypesRelationManager extends RelationManager
{
    protected static string $relationship = 'types';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Model Variants';

    protected static ?string $icon = 'heroicon-o-cog';

    public function form(Form $form): Form
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
                                // ->where('is_active', true)
                                ->ignore($this->getMountedTableActionRecord()?->id),
                        ];
                    })
                    ->validationMessages([
                        'unique' => 'Variant ini sudah ada untuk model ini.',
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

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Variant')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('typeBody.name')
                    ->label('Body Type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('details_count')
                    ->label('Vehicles')
                    ->counts('details')
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                 Tables\Filters\SelectFilter::make('type_body_id')
                    ->label('Body Type')
                    ->relationship('typeBody', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Variants')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
                
                Tables\Filters\Filter::make('has_vehicles')
                    ->label('Has Vehicles')
                    ->query(fn (Builder $query): Builder => $query->has('details')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Variant')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['model_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_vehicles')
                    ->label('View Vehicles')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->url(fn ($record) => route('filament.admin.resources.vehicle-details.index', [
                        'tableFilters' => [
                            'type' => [
                                'values' => [$record->id],
                            ],
                        ],
                    ]))
                    ->openUrlInNewTab(),
                
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil'),
                
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->before(function ($record) {
                        // Check if variant has vehicles before deletion
                        if ($record->details()->count() > 0) {
                            throw new \Exception('Cannot delete variant that has vehicles. Delete the vehicles first.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                if ($record->details()->count() > 0) {
                                    throw new \Exception("Variant '{$record->name}' has vehicles and cannot be deleted.");
                                }
                            }
                        }),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => true]);
                            });
                        })
                        ->requiresConfirmation(),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => false]);
                            });
                        })
                        ->requiresConfirmation(),
                    
                ]),
            ])
            ->emptyStateHeading('No variants added')
            ->emptyStateDescription('Add variants (e.g., GL, GLS, Sport) for this model.')
            ->emptyStateIcon('heroicon-o-cog')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add First Variant')
                    ->icon('heroicon-o-plus'),
            ]);
    }
    
    public static function getTitleForRecord($ownerRecord): string
    {
        return "{$ownerRecord->name} Variants";
    }
}