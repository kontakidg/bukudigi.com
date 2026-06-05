<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Pages\ArticleGenerator;
use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generator')
                ->label('Generator AI')
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->url(ArticleGenerator::getUrl()),
            Actions\Action::make('import')
                ->label('Import CSV + ZIP')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->url(static::$resource::getUrl('import')),
            Actions\CreateAction::make()->label('Tulis Artikel'),
        ];
    }
}
