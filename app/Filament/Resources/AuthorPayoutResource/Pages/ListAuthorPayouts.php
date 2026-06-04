<?php

namespace App\Filament\Resources\AuthorPayoutResource\Pages;

use App\Filament\Resources\AuthorPayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAuthorPayouts extends ListRecords
{
    protected static string $resource = AuthorPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
