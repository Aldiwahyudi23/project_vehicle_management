<?php

namespace App\Filament\Resources\VehicleDetailResource\Pages;

use App\Filament\Resources\VehicleDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleDetail extends EditRecord
{
    protected static string $resource = VehicleDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
