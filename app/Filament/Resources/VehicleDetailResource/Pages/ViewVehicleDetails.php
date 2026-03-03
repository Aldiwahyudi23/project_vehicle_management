<?php

namespace App\Filament\Resources\VehicleDetailResource\Pages;

use App\Filament\Resources\VehicleDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVehicleDetails extends ViewRecord
{
    protected static string $resource = VehicleDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
