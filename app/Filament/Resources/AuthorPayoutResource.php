<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuthorPayoutResource\Pages;
use App\Mail\AuthorPayoutProcessedMail;
use App\Models\AuthorPayout;
use App\Services\FonnteWhatsApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class AuthorPayoutResource extends Resource
{
    protected static ?string $model = AuthorPayout::class;
    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Penulis';
    protected static ?string $navigationLabel = 'Payout Penulis';
    protected static ?string $pluralModelLabel = 'Payout Penulis';
    protected static ?int    $navigationSort  = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Rincian Payout')->schema([
                Forms\Components\Select::make('author_id')->label('Penulis')
                    ->relationship('author', 'display_name')->searchable()->preload()->required()->disabledOn('edit'),
                Forms\Components\TextInput::make('period')->label('Periode (YYYY-MM)')->required()->maxLength(7)
                    ->placeholder(now()->format('Y-m')),
                Forms\Components\TextInput::make('gross_earning')->label('Royalti Kotor')->numeric()->prefix('Rp')->required(),
                Forms\Components\TextInput::make('pph23_rate')->label('Rate PPh 23 (%)')->numeric()->default(2)->required(),
                Forms\Components\TextInput::make('pph23_amount')->label('PPh 23 (Rp)')->numeric()->prefix('Rp')->required(),
                Forms\Components\TextInput::make('transfer_fee')->label('Biaya Transfer')->numeric()->prefix('Rp')->default(3250),
                Forms\Components\TextInput::make('net_transfer')->label('Net Transfer')->numeric()->prefix('Rp')->required(),
            ])->columns(2),

            Forms\Components\Section::make('Rekening (snapshot)')->schema([
                Forms\Components\TextInput::make('bank_name')->label('Bank'),
                Forms\Components\TextInput::make('bank_account')->label('No. Rekening'),
                Forms\Components\TextInput::make('bank_holder')->label('Atas Nama'),
                Forms\Components\TextInput::make('npwp')->label('NPWP'),
            ])->columns(2),

            Forms\Components\Section::make('Status & Bukti')->schema([
                Forms\Components\Select::make('status')->options([
                    'requested'  => 'Menunggu Proses',
                    'processing' => 'Sedang Diproses',
                    'processed'  => 'Sudah Ditransfer',
                    'failed'     => 'Gagal',
                ])->required()->default('requested'),
                Forms\Components\TextInput::make('bank_ref')->label('No. Referensi Transfer Bank'),
                Forms\Components\DateTimePicker::make('processed_at')->label('Waktu Transfer'),
                Forms\Components\Textarea::make('admin_note')->label('Catatan Admin')->rows(2)->columnSpanFull(),
                Forms\Components\FileUpload::make('bank_proof_path')->label('Bukti Transfer')
                    ->image()->disk('private')->visibility('private')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('author.display_name')->label('Penulis')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('author.user.email')->label('Email')->searchable()->toggleable()->copyable(),
                Tables\Columns\TextColumn::make('period')->label('Periode')->sortable()->fontFamily('mono'),
                Tables\Columns\TextColumn::make('gross_earning')->label('Kotor')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('pph23_amount')->label('PPh 23')->money('IDR')->toggleable(),
                Tables\Columns\TextColumn::make('net_transfer')->label('Net Transfer')->money('IDR')->sortable()
                    ->weight('bold')->color('success'),
                Tables\Columns\TextColumn::make('bank_name')->label('Bank')->toggleable(),
                Tables\Columns\TextColumn::make('bank_account')->label('Rekening')->fontFamily('mono')->toggleable()->copyable(),
                Tables\Columns\TextColumn::make('bank_ref')->label('Ref Transfer')->fontFamily('mono')->toggleable()->copyable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn ($s) => match ($s) {
                    'processed'  => 'success',
                    'processing' => 'info',
                    'requested'  => 'warning',
                    'failed'     => 'danger',
                    default      => 'gray',
                })->formatStateUsing(fn ($state, AuthorPayout $record) => $record->statusLabel()),
                Tables\Columns\TextColumn::make('requested_at')->label('Diminta')->date()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('processed_at')->label('Diproses')->date()->sortable()->toggleable(),
            ])
            ->defaultSort('requested_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'requested'  => 'Menunggu Proses',
                    'processing' => 'Sedang Diproses',
                    'processed'  => 'Sudah Ditransfer',
                    'failed'     => 'Gagal',
                ]),
            ])
            ->actions([
                // Tandai processing
                Tables\Actions\Action::make('mark_processing')
                    ->label('Tandai Diproses')->icon('heroicon-o-clock')->color('info')
                    ->visible(fn (AuthorPayout $r) => $r->status === 'requested')
                    ->requiresConfirmation()
                    ->action(function (AuthorPayout $r): void {
                        $r->update(['status' => 'processing']);
                        Notification::make()->title('Ditandai sebagai sedang diproses.')->info()->send();
                    }),

                // Tandai selesai + notif
                Tables\Actions\Action::make('mark_processed')
                    ->label('Selesai — Transfer Berhasil')
                    ->icon('heroicon-o-check-badge')->color('success')
                    ->visible(fn (AuthorPayout $r) => in_array($r->status, ['requested', 'processing']))
                    ->form([
                        Forms\Components\TextInput::make('bank_ref')->label('No. Referensi Transfer Bank')
                            ->placeholder('e.g. TRF20260605123456')->required(),
                        Forms\Components\Textarea::make('admin_note')->label('Catatan (opsional)')->rows(2),
                    ])
                    ->action(function (AuthorPayout $r, array $data): void {
                        $r->update([
                            'status'       => 'processed',
                            'bank_ref'     => $data['bank_ref'],
                            'admin_note'   => $data['admin_note'] ?? null,
                            'processed_at' => now(),
                        ]);

                        // Kurangi balance_available author
                        $author = $r->author;
                        $deduct = min($r->gross_earning, $author->balance_available);
                        if ($deduct > 0) {
                            $author->decrement('balance_available', $deduct);
                        }
                        $author->update(['last_payout_at' => now()]);

                        // Email ke penulis
                        try {
                            $r->load('author.user');
                            Mail::to($r->author->user->email)->queue(new AuthorPayoutProcessedMail($r));
                        } catch (\Throwable $e) {}

                        // WA notif
                        if ($author->user?->phone) {
                            try {
                                app(FonnteWhatsApp::class)->send(
                                    $author->user->phone,
                                    "💸 Payout royalti Rp ".number_format($r->net_transfer, 0, ',', '.')." sudah ditransfer ke rekening kamu ({$r->bank_name} {$r->bank_account}). Periode {$r->period}. Cek dashboard: ".route('author.payout.index')
                                );
                            } catch (\Throwable $e) {}
                        }

                        Notification::make()->title('Payout diproses & penulis dinotifikasi via email/WA')->success()->send();
                    }),

                // Cetak slip
                Tables\Actions\Action::make('slip')
                    ->label('Cetak Slip')->icon('heroicon-o-printer')->color('gray')->url(
                        fn (AuthorPayout $r) => route('author.payout.slip', $r), shouldOpenInNewTab: true
                    ),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAuthorPayouts::route('/'),
            'create' => Pages\CreateAuthorPayout::route('/create'),
            'edit'   => Pages\EditAuthorPayout::route('/{record}/edit'),
        ];
    }
}
