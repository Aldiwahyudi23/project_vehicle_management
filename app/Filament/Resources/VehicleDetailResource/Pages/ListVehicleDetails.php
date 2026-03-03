<?php

namespace App\Filament\Resources\VehicleDetailResource\Pages;

use App\Filament\Resources\VehicleDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleDetails extends ListRecords
{
    protected static string $resource = VehicleDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
