<?php

namespace App\Filament\Resources\PenNameResource\Pages;

use App\Filament\Resources\PenNameResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPenNames extends ListRecords
{
    protected static string $resource = PenNameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
