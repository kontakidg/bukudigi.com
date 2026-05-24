<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AffiliatePayoutResource\Pages;
use App\Models\AffiliateEarning;
use App\Models\AffiliatePayout;
use App\Services\FonnteWhatsApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AffiliatePayoutResource extends Resource
{
    protected static ?string $model = AffiliatePayout::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Affiliate';
    protected static ?string $navigationLabel = 'Payout Affiliate';
    protected static ?string $pluralModelLabel = 'Payout Affiliate';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Payout')->schema([
                Forms\Components\Select::make('affiliate_id')->label('Affiliate')
                    ->relationship('affiliate', 'code')->searchable()->preload()->required()->disabledOn('edit'),
                Forms\Components\TextInput::make('period')->label('Periode (YYYY-MM)')->required()->maxLength(7)
                    ->placeholder(now()->format('Y-m')),
                Forms\Components\TextInput::make('gross_earning')->label('Gross')->numeric()->prefix('Rp')->required(),
                Forms\Components\TextInput::make('transfer_fee')->label('Fee Transfer')->numeric()->prefix('Rp')->default(3250),
                Forms\Components\TextInput::make('net_transfer')->label('Net Transfer')->numeric()->prefix('Rp')->required(),
                Forms\Components\Select::make('status')->options([
                    'pending' => 'Pending', 'processed' => 'Processed', 'failed' => 'Failed',
                ])->required()->default('pending'),
                Forms\Components\DateTimePicker::make('processed_at'),
                Forms\Components\Textarea::make('admin_note')->columnSpanFull()->rows(2),
                Forms\Components\FileUpload::make('bank_proof_path')->label('Bukti Transfer')->image()->disk('private')->visibility('private'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('affiliate.code')->label('Kode')->searchable()->fontFamily('mono'),
                Tables\Columns\TextColumn::make('affiliate.user.name')->label('Nama')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('period')->label('Periode')->sortable(),
                Tables\Columns\TextColumn::make('gross_earning')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('transfer_fee')->money('IDR')->toggleable(),
                Tables\Columns\TextColumn::make('net_transfer')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn ($s) => match ($s) {
                    'processed' => 'success', 'pending' => 'warning', 'failed' => 'danger', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('processed_at')->date()->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'processed' => 'Processed', 'failed' => 'Failed',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_processed')->label('Tandai Diproses')
                    ->icon('heroicon-o-check-badge')->color('success')->requiresConfirmation()
                    ->visible(fn (AffiliatePayout $r): bool => $r->status === 'pending')
                    ->action(function (AffiliatePayout $r): void {
                        $aff = $r->affiliate;

                        // Pindahkan earnings 'available' yang terkait → 'paid' + kurangi balance_available
                        // (asumsi admin udah set payout sesuai saldo available affiliate)
                        $availEarnings = AffiliateEarning::where('affiliate_id', $aff->id)
                            ->where('status', 'available')
                            ->whereNull('payout_id')
                            ->get();

                        $sumApplied = 0;
                        foreach ($availEarnings as $e) {
                            if ($sumApplied + $e->amount > $r->gross_earning) {
                                break;
                            }
                            $e->update([
                                'status' => 'paid',
                                'payout_id' => $r->id,
                                'paid_out_at' => now(),
                            ]);
                            $sumApplied += $e->amount;
                        }

                        if ($sumApplied > 0) {
                            $aff->decrement('balance_available', $sumApplied);
                        }

                        $r->update([
                            'status' => 'processed',
                            'processed_at' => now(),
                        ]);
                        $aff->update(['last_payout_at' => now()]);

                        if ($aff->user?->phone) {
                            try {
                                app(FonnteWhatsApp::class)->send(
                                    $aff->user->phone,
                                    "Payout affiliate Rp ".number_format($r->net_transfer, 0, ',', '.')." sudah ditransfer ke rekening kamu. Periode {$r->period}. Cek dashboard: ".route('affiliate.dashboard')
                                );
                            } catch (\Throwable $e) {}
                        }

                        Notification::make()->title('Payout ditandai diproses & affiliate dinotifikasi')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAffiliatePayouts::route('/'),
            'create' => Pages\CreateAffiliatePayout::route('/create'),
            'edit' => Pages\EditAffiliatePayout::route('/{record}/edit'),
        ];
    }
}
