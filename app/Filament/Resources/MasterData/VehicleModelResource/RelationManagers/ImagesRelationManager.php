<?php

namespace App\Filament\Resources\MasterData\VehicleModelResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $recordTitleAttribute = 'angle';

    protected static ?string $title = 'Model Images';

    protected static ?string $icon = 'heroicon-o-photo';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('image_path')
                    ->label('Image')
                    ->required()
                    ->image()
                    ->directory('vehicle-model-images')
                    ->maxSize(5120) // 5MB
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('16:9')
                    ->imageResizeTargetWidth('800')
                    ->imageResizeTargetHeight('450')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->helperText('Max 5MB. Recommended: 800x450 (16:9)')
                    ->columnSpanFull(),
                
                Forms\Components\Select::make('angle')
                    ->label('View Angle')
                    ->required()
                    ->options([
                        'front' => 'Front View',
                        'side' => 'Side View',
                        'rear' => 'Rear View',
                        'interior' => 'Interior',
                        'dashboard' => 'Dashboard',
                        'engine' => 'Engine Bay',
                        'wheel' => 'Wheel',
                        'other' => 'Other',
                    ])
                    ->default('front'),
                
                Forms\Components\TextInput::make('caption')
                    ->label('Caption')
                    ->maxLength(255)
                    ->nullable(),
                
                Forms\Components\Toggle::make('is_primary')
                    ->label('Set as Primary Image')
                    ->helperText('This image will be shown as the main image')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get, $record) {
                        if ($state && $record) {
                            // Unset primary from other images
                            $this->getRelationship()->where('id', '!=', $record->id)
                                ->update(['is_primary' => false]);
                        }
                    }),
                
                Forms\Components\TextInput::make('order')
                    ->label('Display Order')
                    ->numeric()
                    ->default(0)
                    ->helperText('Lower numbers appear first'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Image')
                    ->size(80)
                    ->circular()
                    ->disk('public')
                    ->extraImgAttributes(['class' => 'object-cover'])
                    ->defaultImageUrl(url('/images/placeholder-vehicle.jpg')),
                
                Tables\Columns\TextColumn::make('angle')
                    ->label('View')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn ($state) => match ($state) {
                        'front' => 'primary',
                        'side' => 'info',
                        'rear' => 'success',
                        'interior' => 'warning',
                        'dashboard' => 'danger',
                        'engine' => 'gray',
                        default => 'secondary',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('caption')
                    ->label('Caption')
                    ->limit(30)
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('angle')
                    ->label('View Angle')
                    ->options([
                        'front' => 'Front View',
                        'side' => 'Side View',
                        'rear' => 'Rear View',
                        'interior' => 'Interior',
                        'dashboard' => 'Dashboard',
                        'engine' => 'Engine Bay',
                        'wheel' => 'Wheel',
                        'other' => 'Other',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Primary Image')
                    ->placeholder('All Images')
                    ->trueLabel('Primary Only')
                    ->falseLabel('Not Primary'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Upload Image')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['model_id'] = $this->getOwnerRecord()->id;
                        
                        // If setting as primary, unset others
                        if ($data['is_primary'] ?? false) {
                            $this->getRelationship()->update(['is_primary' => false]);
                        }
                        
                        return $data;
                    }),
                
                Tables\Actions\Action::make('batch_upload')
                    ->label('Batch Upload')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->form([
                        Forms\Components\FileUpload::make('images')
                            ->label('Multiple Images')
                            ->multiple()
                            ->required()
                            ->image()
                            ->directory('vehicle-model-images')
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxFiles(10)
                            ->helperText('Max 10 images, 5MB each')
                            ->columnSpanFull(),
                        
                        Forms\Components\Select::make('angle')
                            ->label('Default View Angle')
                            ->options([
                                'front' => 'Front View',
                                'side' => 'Side View',
                                'rear' => 'Rear View',
                                'interior' => 'Interior',
                                'other' => 'Other',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $modelId = $this->getOwnerRecord()->id;
                        
                        foreach ($data['images'] as $image) {
                            $this->getRelationship()->create([
                                'model_id' => $modelId,
                                'image_path' => $image,
                                'angle' => $data['angle'],
                                'is_primary' => false,
                            ]);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('set_primary')
                    ->label('Set as Primary')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->action(function ($record) {
                        // Unset primary from all other images
                        $this->getRelationship()
                            ->where('id', '!=', $record->id)
                            ->update(['is_primary' => false]);
                        
                        // Set this as primary
                        $record->update(['is_primary' => true]);
                    })
                    ->visible(fn ($record) => !$record->is_primary)
                    ->requiresConfirmation(),
                
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Image Preview')
                    ->modalContent(function ($record) {
                        return view('filament.pages.image-preview', [
                            'imageUrl' => Storage::url($record->image_path),
                            'caption' => $record->caption,
                            'angle' => $record->angle,
                        ]);
                    })
                    ->modalCancelAction(false),
                
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil'),
                
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->after(function ($record) {
                        // If deleted image was primary, set another as primary
                        if ($record->is_primary) {
                            $newPrimary = $this->getRelationship()
                                ->where('id', '!=', $record->id)
                                ->first();
                            
                            if ($newPrimary) {
                                $newPrimary->update(['is_primary' => true]);
                            }
                        }
                        
                        // Delete file from storage
                        Storage::delete($record->image_path);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->after(function ($records) {
                            foreach ($records as $record) {
                                Storage::delete($record->image_path);
                            }
                        }),
                    
                    Tables\Actions\BulkAction::make('update_angle')
                        ->label('Update View Angle')
                        ->icon('heroicon-o-arrows-pointing-out')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('angle')
                                ->label('New View Angle')
                                ->required()
                                ->options([
                                    'front' => 'Front View',
                                    'side' => 'Side View',
                                    'rear' => 'Rear View',
                                    'interior' => 'Interior',
                                    'dashboard' => 'Dashboard',
                                    'engine' => 'Engine Bay',
                                    'wheel' => 'Wheel',
                                    'other' => 'Other',
                                ]),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $record->update(['angle' => $data['angle']]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->emptyStateHeading('No images uploaded')
            ->emptyStateDescription('Upload images to showcase this vehicle model.')
            ->emptyStateIcon('heroicon-o-photo')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Upload First Image')
                    ->icon('heroicon-o-cloud-arrow-up'),
            ]);
    }
    
    public static function getTitleForRecord($ownerRecord): string
    {
        return "{$ownerRecord->name} Images";
    }
}