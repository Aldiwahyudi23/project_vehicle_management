<?php

namespace App\Filament\Resources\MasterData\VehicleModelImageResource\Pages;

use App\Filament\Resources\MasterData\VehicleModelImageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleModelImages extends ListRecords
{
    protected static string $resource = VehicleModelImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
