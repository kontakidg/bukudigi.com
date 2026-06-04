<?php

namespace App\Filament\Resources\AuthorPayoutResource\Pages;

use App\Filament\Resources\AuthorPayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAuthorPayout extends EditRecord
{
    protected static string $resource = AuthorPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
