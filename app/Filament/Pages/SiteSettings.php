<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Sistem';
    protected static ?string $navigationLabel = 'Site Settings';
    protected static ?string $title = 'Pengaturan Site';
    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.site-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'hero_tagline_1' => Setting::get('hero_tagline_1'),
            'hero_tagline_2' => Setting::get('hero_tagline_2'),
            'hero_subtagline' => Setting::get('hero_subtagline'),
            'site_tagline_meta' => Setting::get('site_tagline_meta'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Forms\Components\Section::make('Hero Homepage')
                ->description('Teks yang tampil di banner hero bagian atas bukudigi.com.')
                ->schema([
                    Forms\Components\TextInput::make('hero_tagline_1')
                        ->label('Tagline baris 1')
                        ->maxLength(255)
                        ->placeholder('Ebook PDF dari Penulis Indonesia'),
                    Forms\Components\TextInput::make('hero_tagline_2')
                        ->label('Tagline baris 2 (highlight, warna terang)')
                        ->maxLength(255)
                        ->placeholder('Bayar QRIS, Baca Sekarang'),
                    Forms\Components\Textarea::make('hero_subtagline')
                        ->label('Sub-tagline (paragraf di bawah)')
                        ->rows(3)
                        ->maxLength(500),
                ]),

            Forms\Components\Section::make('SEO')
                ->description('Deskripsi yang muncul di hasil pencarian Google + preview share social media.')
                ->schema([
                    Forms\Components\Textarea::make('site_tagline_meta')
                        ->label('Meta description default')
                        ->rows(2)
                        ->maxLength(160)
                        ->helperText('Max 160 karakter (rekomendasi Google).'),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            $group = str_starts_with($key, 'hero_') ? 'homepage' : 'seo';
            Setting::set($key, $value, $group);
        }

        Notification::make()->title('Settings tersimpan')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Simpan')
                ->action('save'),
        ];
    }
}
