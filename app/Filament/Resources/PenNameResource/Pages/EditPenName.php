<?php

namespace App\Filament\Resources\PenNameResource\Pages;

use App\Filament\Resources\PenNameResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenName extends EditRecord
{
    protected static string $resource = PenNameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
