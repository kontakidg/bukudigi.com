<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuthorResource\Pages;
use App\Models\Author;
use App\Services\FonnteWhatsApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuthorResource extends Resource
{
    protected static ?string $model = Author::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'Katalog';
    protected static ?string $navigationLabel = 'Penulis';
    protected static ?string $pluralModelLabel = 'Penulis';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = Author::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Profil')
                ->schema([
                    Forms\Components\Select::make('user_id')->label('User')
                        ->relationship('user', 'name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('display_name')->label('Nama Tampil')->required()->maxLength(120),
                    Forms\Components\Textarea::make('bio')->columnSpanFull()->rows(3),
                ])->columns(2),

            Forms\Components\Section::make('Verifikasi')
                ->schema([
                    Forms\Components\TextInput::make('nik')->label('NIK')->maxLength(32),
                    Forms\Components\TextInput::make('npwp')->label('NPWP')->maxLength(32)
                        ->helperText('Kosongkan jika tidak ada — PPh 23 jadi 4%'),
                    Forms\Components\FileUpload::make('ktp_image_path')->label('Foto KTP')->image()->disk('local')->visibility('private'),
                    Forms\Components\FileUpload::make('selfie_image_path')->label('Selfie + KTP')->image()->disk('local')->visibility('private'),
                    Forms\Components\Select::make('status')->required()->options([
                        'pending' => 'Pending', 'verified' => 'Verified', 'suspended' => 'Suspended',
                    ]),
                    Forms\Components\DateTimePicker::make('verified_at'),
                    Forms\Components\Textarea::make('rejection_reason')->columnSpanFull()->rows(2),
                ])->columns(2),

            Forms\Components\Section::make('Rekening')
                ->schema([
                    Forms\Components\TextInput::make('bank_name')->label('Nama Bank')->maxLength(64),
                    Forms\Components\TextInput::make('bank_account')->label('No. Rekening')->maxLength(64),
                    Forms\Components\TextInput::make('bank_holder')->label('Atas Nama')->maxLength(120)->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Saldo & Payout (read-only)')
                ->schema([
                    Forms\Components\TextInput::make('balance_available')->prefix('Rp')->disabled(),
                    Forms\Components\TextInput::make('balance_pending')->prefix('Rp')->disabled(),
                    Forms\Components\TextInput::make('total_earned')->prefix('Rp')->disabled(),
                    Forms\Components\DateTimePicker::make('last_payout_at')->disabled(),
                ])->columns(2)->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')->label('Nama')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('user.email')->label('Email')->searchable(),
                Tables\Columns\TextColumn::make('bank_name')->label('Bank')->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'verified' => 'success', 'pending' => 'warning', 'suspended' => 'danger', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('balance_available')->label('Saldo')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('total_earned')->label('Total Earned')->money('IDR')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('verified_at')->label('Verified')->date()->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'verified' => 'Verified', 'suspended' => 'Suspended',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('verify')->label('Verify')
                    ->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->visible(fn (Author $r): bool => $r->status === 'pending')
                    ->action(function (Author $r): void {
                        $r->update(['status' => 'verified', 'verified_at' => now()]);

                        if ($r->user?->phone) {
                            app(FonnteWhatsApp::class)->send(
                                $r->user->phone,
                                "Halo {$r->display_name}! 🎉\n\nAkun penulis kamu di bukudigi.com sudah terverifikasi. Kamu sekarang bisa upload buku via dashboard:\n\n".route('author.books.create')
                            );
                        }

                        Notification::make()->title('Author terverifikasi & dinotifikasi')->success()->send();
                    }),
                Tables\Actions\Action::make('suspend')->label('Suspend')
                    ->icon('heroicon-o-no-symbol')->color('danger')
                    ->visible(fn (Author $r): bool => $r->status !== 'suspended')
                    ->form([
                        Forms\Components\Textarea::make('reason')->label('Alasan')->required()->rows(3),
                    ])
                    ->action(function (Author $r, array $data): void {
                        $r->update(['status' => 'suspended', 'rejection_reason' => $data['reason']]);

                        if ($r->user?->phone) {
                            app(FonnteWhatsApp::class)->send(
                                $r->user->phone,
                                "Akun penulis kamu di-suspend.\n\nAlasan: {$data['reason']}\n\nHubungi support: kulinerbdgcom@gmail.com"
                            );
                        }

                        Notification::make()->title('Author di-suspend & dinotifikasi')->warning()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuthors::route('/'),
            'create' => Pages\CreateAuthor::route('/create'),
            'edit' => Pages\EditAuthor::route('/{record}/edit'),
        ];
    }
}
