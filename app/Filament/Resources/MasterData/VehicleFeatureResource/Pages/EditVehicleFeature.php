<?php

namespace App\Filament\Resources\MasterData\VehicleFeatureResource\Pages;

use App\Filament\Resources\MasterData\VehicleFeatureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleFeature extends EditRecord
{
    protected static string $resource = VehicleFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
