<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoucherResource\Pages;
use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Promo';
    protected static ?string $navigationLabel = 'Voucher';
    protected static ?string $pluralModelLabel = 'Voucher';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identitas Voucher')
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('Kode Voucher')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(64)
                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                        ->dehydrateStateUsing(fn ($state) => strtoupper(trim((string) $state)))
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('generate')
                                ->icon('heroicon-o-arrow-path')
                                ->action(fn ($set) => $set('code', 'BD'.strtoupper(Str::random(6))))
                        )
                        ->helperText('Kode unik tanpa spasi. Akan otomatis diuppercase.'),
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Internal')
                        ->required()
                        ->maxLength(200)
                        ->helperText('Untuk dilihat admin saja. Mis: "Promo Lebaran 2026"'),
                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi (optional, tampil ke user)')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Tipe Diskon')
                ->schema([
                    Forms\Components\Select::make('discount_type')
                        ->label('Tipe')
                        ->required()
                        ->options([
                            'percentage' => 'Persentase (%)',
                            'fixed' => 'Nominal tetap (Rp)',
                        ])
                        ->default('percentage')
                        ->live(),
                    Forms\Components\TextInput::make('discount_value')
                        ->label(fn ($get) => $get('discount_type') === 'percentage' ? 'Persentase' : 'Nominal (Rp)')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(fn ($get) => $get('discount_type') === 'percentage' ? 100 : 500000)
                        ->suffix(fn ($get) => $get('discount_type') === 'percentage' ? '%' : 'Rp'),
                    Forms\Components\TextInput::make('max_discount_amount')
                        ->label('Cap Maksimum Diskon (Rp)')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->helperText('Hanya untuk tipe persentase. Kosongkan = tidak ada cap.')
                        ->visible(fn ($get) => $get('discount_type') === 'percentage'),
                    Forms\Components\TextInput::make('min_purchase_amount')
                        ->label('Minimum Pembelian (Rp)')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->helperText('Kosongkan = tidak ada minimum'),
                ])->columns(2),

            Forms\Components\Section::make('Limit Penggunaan')
                ->schema([
                    Forms\Components\TextInput::make('max_uses')
                        ->label('Maksimum Total Pemakaian')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Kosongkan = unlimited'),
                    Forms\Components\TextInput::make('max_uses_per_user')
                        ->label('Maksimum per User')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required()
                        ->helperText('Default 1: 1 user cuma boleh pakai 1x'),
                    Forms\Components\DateTimePicker::make('valid_from')
                        ->label('Berlaku Mulai')
                        ->helperText('Kosongkan = berlaku langsung'),
                    Forms\Components\DateTimePicker::make('valid_until')
                        ->label('Berlaku Sampai')
                        ->helperText('Kosongkan = tidak ada expiry'),
                ])->columns(2),

            Forms\Components\Section::make('Scope (Berlaku untuk Buku/Kategori)')
                ->description('Kosongkan dua-duanya = voucher berlaku untuk SEMUA buku. Isi minimal salah satu untuk membatasi.')
                ->schema([
                    Forms\Components\Select::make('applicable_book_ids')
                        ->label('Buku tertentu')
                        ->multiple()
                        ->options(\App\Models\Book::where('status', 'active')->pluck('title', 'id'))
                        ->searchable()
                        ->preload()
                        ->dehydrateStateUsing(fn ($state) => is_array($state) && ! empty($state) ? array_map('intval', $state) : null)
                        ->helperText('Voucher berlaku hanya untuk buku yang dipilih. Boleh skip kalau pakai filter kategori.'),
                    Forms\Components\Select::make('applicable_category_ids')
                        ->label('Kategori tertentu')
                        ->multiple()
                        ->options(\App\Models\Category::pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->dehydrateStateUsing(fn ($state) => is_array($state) ? array_map('intval', $state) : null)
                        ->helperText('Voucher berlaku untuk semua buku di kategori ini.'),
                ])->columns(2)->collapsed(),

            Forms\Components\Section::make('Status')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->weight('semibold')
                    ->copyable()
                    ->copyMessage('Kode disalin'),
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('discount_type')
                    ->label('Diskon')
                    ->formatStateUsing(fn (Voucher $r) => $r->displayDiscount()),
                Tables\Columns\TextColumn::make('used_count')
                    ->label('Pemakaian')
                    ->formatStateUsing(fn (Voucher $r) => $r->used_count.($r->max_uses ? '/'.$r->max_uses : ''))
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Expired')
                    ->date()
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Dibuat')->date()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('discount_type')->options([
                    'percentage' => 'Persentase',
                    'fixed' => 'Nominal',
                ]),
                SelectFilter::make('is_active')->options([
                    1 => 'Aktif',
                    0 => 'Nonaktif',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (Voucher $r) => $r->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (Voucher $r) => $r->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (Voucher $r) => $r->is_active ? 'warning' : 'success')
                    ->action(fn (Voucher $r) => $r->update(['is_active' => ! $r->is_active])),
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
            'index' => Pages\ListVouchers::route('/'),
            'create' => Pages\CreateVoucher::route('/create'),
            'edit' => Pages\EditVoucher::route('/{record}/edit'),
        ];
    }
}
