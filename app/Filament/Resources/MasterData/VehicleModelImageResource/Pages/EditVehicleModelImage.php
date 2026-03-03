<?php

namespace App\Filament\Resources\MasterData\VehicleModelImageResource\Pages;

use App\Filament\Resources\MasterData\VehicleModelImageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleModelImage extends EditRecord
{
    protected static string $resource = VehicleModelImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
