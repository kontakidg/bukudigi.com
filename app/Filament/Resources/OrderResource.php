<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Order';
    protected static ?string $pluralModelLabel = 'Order';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Order')
                ->schema([
                    Forms\Components\TextInput::make('order_code')->disabled(),
                    Forms\Components\Select::make('status')->required()->options([
                        'pending' => 'Pending', 'paid' => 'Paid', 'watermarking' => 'Watermarking',
                        'ready' => 'Ready', 'refunded' => 'Refunded', 'failed' => 'Failed', 'expired' => 'Expired',
                    ]),
                    Forms\Components\Select::make('user_id')->label('Pembeli')->relationship('user', 'name')->searchable()->disabled(),
                    Forms\Components\Select::make('book_id')->label('Buku')->relationship('book', 'title')->searchable()->disabled(),
                    Forms\Components\TextInput::make('payment_method')->disabled(),
                    Forms\Components\DateTimePicker::make('paid_at')->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Keuangan')
                ->schema([
                    Forms\Components\TextInput::make('gross_amount')->prefix('Rp')->disabled(),
                    Forms\Components\TextInput::make('commission')->prefix('Rp')->disabled(),
                    Forms\Components\TextInput::make('author_earning')->prefix('Rp')->disabled(),
                    Forms\Components\TextInput::make('gateway_fee')->prefix('Rp')->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Watermark & Download')
                ->schema([
                    Forms\Components\TextInput::make('watermarked_pdf_path')->disabled(),
                    Forms\Components\DateTimePicker::make('watermarked_at')->disabled(),
                    Forms\Components\DateTimePicker::make('download_expires_at')->disabled(),
                    Forms\Components\TextInput::make('download_count')->disabled(),
                ])->columns(2)->collapsed(),

            Forms\Components\Section::make('Refund')
                ->schema([
                    Forms\Components\DateTimePicker::make('refunded_at')->disabled(),
                    Forms\Components\Textarea::make('refund_reason')->columnSpanFull()->disabled(),
                ])->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_code')->label('Kode')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('user.name')->label('Pembeli')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('book.title')->label('Buku')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('gross_amount')->label('Total')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $s) => match ($s) {
                    'ready' => 'success', 'paid', 'watermarking' => 'info',
                    'refunded', 'failed', 'expired' => 'danger', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('payment_method')->label('Metode')->toggleable(),
                Tables\Columns\TextColumn::make('paid_at')->label('Dibayar')->dateTime('d M Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'paid' => 'Paid', 'watermarking' => 'Watermarking',
                    'ready' => 'Ready', 'refunded' => 'Refunded', 'failed' => 'Failed', 'expired' => 'Expired',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('refund')->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')->color('danger')
                    ->visible(fn (Order $o): bool => in_array($o->status, ['paid', 'ready']))
                    ->form([
                        Forms\Components\Textarea::make('reason')->label('Alasan refund')->required()->rows(3),
                    ])
                    ->action(function (Order $o, array $data): void {
                        $o->update([
                            'status' => 'refunded',
                            'refunded_at' => now(),
                            'refund_reason' => $data['reason'],
                        ]);
                        Notification::make()->title('Refund diproses')->success()->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
