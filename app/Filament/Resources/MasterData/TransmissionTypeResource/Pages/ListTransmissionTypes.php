<?php

namespace App\Filament\Resources\MasterData\TransmissionTypeResource\Pages;

use App\Filament\Resources\MasterData\TransmissionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransmissionTypes extends ListRecords
{
    protected static string $resource = TransmissionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
