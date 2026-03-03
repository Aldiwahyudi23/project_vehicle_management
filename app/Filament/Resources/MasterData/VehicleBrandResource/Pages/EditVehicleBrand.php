<?php

namespace App\Filament\Resources\MasterData\VehicleBrandResource\Pages;

use App\Filament\Resources\MasterData\VehicleBrandResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleBrand extends EditRecord
{
    protected static string $resource = VehicleBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
