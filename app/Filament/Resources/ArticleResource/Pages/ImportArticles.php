<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use App\Services\ArticleImportService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportArticles extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ArticleResource::class;
    protected static string $view = 'filament.pages.import-articles';
    protected static ?string $title = 'Import Artikel (CSV + ZIP)';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Forms\Components\Section::make('File Import')
                ->description('Upload CSV artikel + (opsional) ZIP berisi gambar cover.')
                ->schema([
                    Forms\Components\FileUpload::make('csv')
                        ->label('File CSV')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                        ->disk('local')
                        ->directory('imports')
                        ->required()
                        ->helperText('Header wajib ada kolom "title". Lihat template di bawah.'),
                    Forms\Components\FileUpload::make('zip')
                        ->label('File ZIP gambar (opsional)')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'])
                        ->disk('local')
                        ->directory('imports')
                        ->helperText('Isi ZIP = file gambar (jpg/png/webp). Cocokkan dgn kolom "cover_filename" di CSV.'),
                ]),
        ]);
    }

    public function import(): void
    {
        $state = $this->form->getState();

        $csvPath = $state['csv'] ?? null;
        $zipPath = $state['zip'] ?? null;

        if (! $csvPath) {
            Notification::make()->title('File CSV wajib diupload')->danger()->send();
            return;
        }

        $csvAbs = Storage::disk('local')->path($csvPath);
        $zipAbs = $zipPath ? Storage::disk('local')->path($zipPath) : null;

        $result = app(ArticleImportService::class)->import($csvAbs, $zipAbs);

        // Bersihkan file upload sementara
        Storage::disk('local')->delete(array_filter([$csvPath, $zipPath]));

        $body = "Berhasil: {$result['imported']} · Dilewati: {$result['skipped']}";
        if (! empty($result['errors'])) {
            $body .= "\n" . implode("\n", array_slice($result['errors'], 0, 5));
            if (count($result['errors']) > 5) {
                $body .= "\n… +" . (count($result['errors']) - 5) . " error lain";
            }
        }

        Notification::make()
            ->title($result['imported'] > 0 ? 'Import selesai' : 'Import tidak menghasilkan artikel')
            ->body($body)
            ->color($result['imported'] > 0 ? 'success' : 'warning')
            ->persistent()
            ->send();

        $this->form->fill();
    }

    /** Download CSV template. */
    public function downloadTemplate(): StreamedResponse
    {
        $csv = ArticleImportService::templateCsv();

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'template-artikel-bukudigi.csv', ['Content-Type' => 'text/csv']);
    }
}
