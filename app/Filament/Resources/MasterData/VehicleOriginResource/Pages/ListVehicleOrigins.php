<?php

namespace App\Filament\Resources\MasterData\VehicleOriginResource\Pages;

use App\Filament\Resources\MasterData\VehicleOriginResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleOrigins extends ListRecords
{
    protected static string $resource = VehicleOriginResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
