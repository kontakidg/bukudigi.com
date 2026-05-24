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
            'author_verification_level' => Setting::get('author_verification_level', '1'),
            'maintenance_enabled' => (bool) Setting::get('maintenance_enabled'),
            'maintenance_title' => Setting::get('maintenance_title', 'Lagi rapi-rapi rak buku 📚'),
            'maintenance_message' => Setting::get('maintenance_message', 'Halo! bukudigi.com sedang upgrade biar makin nyaman dibaca. Tenang, semua buku kamu di Perpustakaan aman tersimpan. Sebentar ya, kami segera kembali!'),
            'maintenance_estimated_end' => Setting::get('maintenance_estimated_end'),
            'maintenance_show_contact' => (bool) Setting::get('maintenance_show_contact', true),
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
                        ->label('Tagline baris 2 (highlight)')
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

            Forms\Components\Section::make('Verifikasi Penulis')
                ->description('Berapa ketat data wajib saat penulis baru daftar di /jual / /author/register.')
                ->schema([
                    Forms\Components\Select::make('author_verification_level')
                        ->label('Level verifikasi pendaftaran penulis')
                        ->required()
                        ->options([
                            '1' => 'Level 1 — Nama saja (paling longgar)',
                            '2' => 'Level 2 — Nama + No. WhatsApp wajib',
                            '3' => 'Level 3 — Lengkap: NIK + KTP + Selfie wajib',
                        ])
                        ->default('1')
                        ->helperText('Level lebih tinggi = lebih aman tapi friction onboarding lebih besar. Bisa diubah kapan saja; hanya berlaku untuk pendaftaran baru.'),
                ]),

            Forms\Components\Section::make('🚧 Maintenance Mode')
                ->description('Aktifkan untuk menutup site dari publik (admin tetap bisa akses /admin). Cocok untuk deployment, update DB, atau tweak besar.')
                ->schema([
                    Forms\Components\Toggle::make('maintenance_enabled')
                        ->label('Aktifkan Maintenance Mode')
                        ->helperText('Saat ON, semua user non-admin akan lihat halaman maintenance dengan response 503. Admin yang sudah login + path /admin tetap bisa diakses.')
                        ->live()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('maintenance_title')
                        ->label('Judul halaman maintenance')
                        ->maxLength(120)
                        ->placeholder('Lagi rapi-rapi rak buku 📚')
                        ->helperText('Boleh pakai emoji. Tampil sebagai heading utama.')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('maintenance_message')
                        ->label('Pesan ke user')
                        ->rows(4)
                        ->maxLength(500)
                        ->placeholder('Halo! bukudigi.com sedang upgrade biar makin nyaman dibaca...')
                        ->helperText('Tone friendly. Jelasin singkat kenapa down + janji kapan balik.')
                        ->columnSpanFull(),

                    Forms\Components\DateTimePicker::make('maintenance_estimated_end')
                        ->label('Perkiraan selesai (opsional)')
                        ->helperText('Kalau diisi, akan tampil countdown ke waktu ini di halaman maintenance.')
                        ->seconds(false),

                    Forms\Components\Toggle::make('maintenance_show_contact')
                        ->label('Tampilkan tombol "Hubungi Support"')
                        ->helperText('Default ON. Link ke mailto:support@bukudigi.com.')
                        ->default(true),
                ])->columns(2)
                ->collapsed(fn (callable $get) => ! $get('maintenance_enabled')),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $groupMap = [
            'hero_tagline_1' => 'homepage',
            'hero_tagline_2' => 'homepage',
            'hero_subtagline' => 'homepage',
            'site_tagline_meta' => 'seo',
            'author_verification_level' => 'author',
            'maintenance_enabled' => 'maintenance',
            'maintenance_title' => 'maintenance',
            'maintenance_message' => 'maintenance',
            'maintenance_estimated_end' => 'maintenance',
            'maintenance_show_contact' => 'maintenance',
        ];

        foreach ($data as $key => $value) {
            Setting::set($key, $value, $groupMap[$key] ?? 'general');
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
