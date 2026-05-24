<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AffiliateResource\Pages;
use App\Filament\Resources\AffiliateResource\RelationManagers;
use App\Models\Affiliate;
use App\Services\FonnteWhatsApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AffiliateResource extends Resource
{
    protected static ?string $model = Affiliate::class;
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationGroup = 'Affiliate';
    protected static ?string $navigationLabel = 'Affiliate';
    protected static ?string $pluralModelLabel = 'Affiliate';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = Affiliate::where('status', 'pending')->count();
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
                        ->relationship('user', 'name')->searchable()->preload()->required()->disabledOn('edit'),
                    Forms\Components\Placeholder::make('codes_info')->label('Kode')
                        ->content(fn ($record) => $record
                            ? $record->codes->pluck('code')->join(', ') ?: '—'
                            : 'Akan auto-generate setelah save.'),
                    Forms\Components\Select::make('status')->required()->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'suspended' => 'Suspended',
                    ]),
                    Forms\Components\TextInput::make('commission_rate')->label('Komisi (%)')->numeric()
                        ->minValue(0)->maxValue(50)->default(10)->suffix('%'),
                ])->columns(2),

            Forms\Components\Section::make('Aplikasi')
                ->schema([
                    Forms\Components\TextInput::make('promo_channels')->label('Channel Promosi')->columnSpanFull()->maxLength(500),
                    Forms\Components\Textarea::make('motivation')->label('Motivasi')->columnSpanFull()->rows(4),
                    Forms\Components\Toggle::make('commitment_agreed')->label('Komitmen disetujui?'),
                    Forms\Components\Textarea::make('rejection_reason')->label('Alasan reject/suspend')->columnSpanFull()->rows(2),
                    Forms\Components\Textarea::make('admin_note')->label('Catatan admin (internal)')->columnSpanFull()->rows(2),
                ]),

            Forms\Components\Section::make('Saldo & Stats (read-only)')
                ->schema([
                    Forms\Components\TextInput::make('balance_available')->prefix('Rp')->disabled(),
                    Forms\Components\TextInput::make('balance_pending')->prefix('Rp')->disabled(),
                    Forms\Components\TextInput::make('total_earned')->prefix('Rp')->disabled(),
                    Forms\Components\TextInput::make('clicks_count')->label('Total Klik')->disabled(),
                    Forms\Components\TextInput::make('conversions_count')->label('Total Konversi')->disabled(),
                    Forms\Components\DateTimePicker::make('approved_at')->disabled(),
                ])->columns(2)->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('defaultCode.code')->label('Kode Default')->searchable()->weight('semibold')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('codes_count')->counts('codes')->label('#Kode')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Nama')->searchable(),
                Tables\Columns\TextColumn::make('user.email')->label('Email')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'approved' => 'success', 'pending' => 'warning', 'rejected', 'suspended' => 'danger', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('commission_rate')->label('Rate')->suffix('%')->sortable(),
                Tables\Columns\TextColumn::make('balance_available')->label('Saldo')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('total_earned')->label('Total Earned')->money('IDR')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('clicks_count')->label('Klik')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('conversions_count')->label('Konv.')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('approved_at')->label('Approved')->date()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Daftar')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'suspended' => 'Suspended',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')->label('Approve')
                    ->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->visible(fn (Affiliate $r): bool => in_array($r->status, ['pending', 'rejected', 'suspended'], true))
                    ->action(function (Affiliate $r): void {
                        $r->update([
                            'status' => 'approved',
                            'approved_at' => now(),
                            'approved_by_id' => auth()->id(),
                            'rejection_reason' => null,
                        ]);

                        $r->ensureDefaultCode();
                        $code = $r->defaultCode()->first()?->code ?? '—';

                        if ($r->user?->phone) {
                            try {
                                app(FonnteWhatsApp::class)->send(
                                    $r->user->phone,
                                    "Halo {$r->user->name}! 🎉\n\nPendaftaran affiliate kamu di bukudigi.com sudah DISETUJUI.\n\nKode default: *{$code}*\n\nDashboard: ".route('affiliate.dashboard')
                                );
                            } catch (\Throwable $e) {}
                        }

                        Notification::make()->title('Affiliate disetujui & dinotifikasi')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')->label('Reject')
                    ->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (Affiliate $r): bool => $r->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('reason')->label('Alasan reject')->required()->rows(3),
                    ])
                    ->action(function (Affiliate $r, array $data): void {
                        $r->update(['status' => 'rejected', 'rejection_reason' => $data['reason']]);

                        if ($r->user?->phone) {
                            try {
                                app(FonnteWhatsApp::class)->send(
                                    $r->user->phone,
                                    "Halo {$r->user->name},\n\nPendaftaran affiliate bukudigi.com belum disetujui.\n\nAlasan: {$data['reason']}\n\nKamu bisa daftar ulang setelah memperbaiki yang disebutkan."
                                );
                            } catch (\Throwable $e) {}
                        }

                        Notification::make()->title('Affiliate di-reject & dinotifikasi')->warning()->send();
                    }),
                Tables\Actions\Action::make('suspend')->label('Suspend')
                    ->icon('heroicon-o-no-symbol')->color('danger')
                    ->visible(fn (Affiliate $r): bool => $r->status === 'approved')
                    ->form([
                        Forms\Components\Textarea::make('reason')->label('Alasan suspend')->required()->rows(3),
                    ])
                    ->action(function (Affiliate $r, array $data): void {
                        $r->update(['status' => 'suspended', 'rejection_reason' => $data['reason']]);

                        if ($r->user?->phone) {
                            try {
                                app(FonnteWhatsApp::class)->send(
                                    $r->user->phone,
                                    "Akun affiliate kamu di-suspend.\n\nAlasan: {$data['reason']}\n\nHubungi support: kulinerbdgcom@gmail.com"
                                );
                            } catch (\Throwable $e) {}
                        }

                        Notification::make()->title('Affiliate di-suspend & dinotifikasi')->warning()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CodesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAffiliates::route('/'),
            'create' => Pages\CreateAffiliate::route('/create'),
            'edit' => Pages\EditAffiliate::route('/{record}/edit'),
        ];
    }
}
