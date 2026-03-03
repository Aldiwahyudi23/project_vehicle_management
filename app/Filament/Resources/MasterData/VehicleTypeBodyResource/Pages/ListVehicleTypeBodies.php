<?php

namespace App\Filament\Resources\MasterData\VehicleTypeBodyResource\Pages;

use App\Filament\Resources\MasterData\VehicleTypeBodyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleTypeBodies extends ListRecords
{
    protected static string $resource = VehicleTypeBodyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
