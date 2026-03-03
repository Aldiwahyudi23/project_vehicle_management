<?php

namespace App\Filament\Resources\MasterData\VehicleTypeResource\Pages;

use App\Filament\Resources\MasterData\VehicleTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleTypes extends ListRecords
{
    protected static string $resource = VehicleTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
