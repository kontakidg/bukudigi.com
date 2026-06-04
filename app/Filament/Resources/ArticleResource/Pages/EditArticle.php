<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use App\Jobs\PostArticleToFacebookJob;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view')
                ->label('Lihat')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn () => route('blog.show', $this->record->slug), shouldOpenInNewTab: true)
                ->visible(fn () => $this->record->status === 'published'),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Auto-post FB kalau published & belum pernah dipost (job sendiri guard)
        if ($this->record->status === 'published' && $this->record->fb_posted_at === null) {
            PostArticleToFacebookJob::dispatch($this->record->id)->delay(now()->addSeconds(5));
        }
    }
}
