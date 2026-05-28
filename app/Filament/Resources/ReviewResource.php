<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Katalog';
    protected static ?string $navigationLabel = 'Review';
    protected static ?string $pluralModelLabel = 'Review';
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $count = Review::where('is_hidden', false)->whereDate('created_at', today())->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('book_id')->label('Buku')
                ->relationship('book', 'title')->disabled(),
            Forms\Components\Select::make('user_id')->label('User')
                ->relationship('user', 'name')->disabled(),
            Forms\Components\TextInput::make('rating')->numeric()->disabled(),
            Forms\Components\Textarea::make('comment')->label('Komentar')->rows(3)->disabled()->columnSpanFull(),
            Forms\Components\Textarea::make('author_reply')->label('Balasan Penulis')->rows(2)->columnSpanFull(),
            Forms\Components\Toggle::make('is_hidden')->label('Sembunyikan (moderasi)')
                ->helperText('ON = review tidak tampil ke publik. Pakai untuk review spam/abusive.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('book.title')->label('Buku')->searchable()->limit(35)->weight('semibold'),
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable(),
                Tables\Columns\TextColumn::make('rating')->label('Rating')
                    ->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state)),
                Tables\Columns\TextColumn::make('comment')->label('Komentar')->limit(50)->placeholder('—')->wrap(),
                Tables\Columns\IconColumn::make('author_reply')->label('Dibalas')
                    ->boolean()->getStateUsing(fn (Review $r) => ! empty($r->author_reply)),
                Tables\Columns\IconColumn::make('is_hidden')->label('Hidden')->boolean()
                    ->trueColor('danger')->falseColor('success')
                    ->trueIcon('heroicon-o-eye-slash')->falseIcon('heroicon-o-eye'),
                Tables\Columns\TextColumn::make('created_at')->label('Tanggal')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('rating')->options([
                    5 => '5 bintang', 4 => '4 bintang', 3 => '3 bintang', 2 => '2 bintang', 1 => '1 bintang',
                ]),
                SelectFilter::make('is_hidden')->label('Status')->options([
                    0 => 'Tampil', 1 => 'Disembunyikan',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleHidden')
                    ->label(fn (Review $r) => $r->is_hidden ? 'Tampilkan' : 'Sembunyikan')
                    ->icon(fn (Review $r) => $r->is_hidden ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color(fn (Review $r) => $r->is_hidden ? 'success' : 'warning')
                    ->requiresConfirmation()
                    ->action(function (Review $r) {
                        $r->update(['is_hidden' => ! $r->is_hidden]);
                        Notification::make()->title($r->is_hidden ? 'Review disembunyikan' : 'Review ditampilkan')->success()->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('hide')->label('Sembunyikan')->icon('heroicon-o-eye-slash')->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_hidden' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}
