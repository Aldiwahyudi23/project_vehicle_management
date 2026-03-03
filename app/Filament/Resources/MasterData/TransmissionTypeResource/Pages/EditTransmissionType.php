<?php

namespace App\Filament\Resources\MasterData\TransmissionTypeResource\Pages;

use App\Filament\Resources\MasterData\TransmissionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransmissionType extends EditRecord
{
    protected static string $resource = TransmissionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
