<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenNameResource\Pages;
use App\Models\PenName;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PenNameResource extends Resource
{
    protected static ?string $model = PenName::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Katalog';
    protected static ?string $navigationLabel = 'Pen Name';
    protected static ?string $pluralModelLabel = 'Pen Name';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Pen Name')
                ->schema([
                    Forms\Components\Select::make('author_id')->label('Author Account')
                        ->relationship('author', 'display_name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('name')->label('Nama Tampil')->required()->maxLength(120),
                    Forms\Components\TextInput::make('slug')->maxLength(255)
                        ->helperText('Auto-generate kalau kosong. Hindari ubah setelah buku ada.'),
                    Forms\Components\Toggle::make('is_default')->label('Default untuk author ini')
                        ->helperText('Hanya 1 pen name default per author.'),
                    Forms\Components\Textarea::make('bio')->columnSpanFull()->rows(3),
                    Forms\Components\TextInput::make('avatar_path')->label('Avatar URL/path')->maxLength(255),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('author.display_name')->label('Author Account')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('slug')->toggleable(),
                Tables\Columns\IconColumn::make('is_default')->boolean()->label('Default'),
                Tables\Columns\TextColumn::make('books_count')->label('Buku')->counts('books')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenNames::route('/'),
            'create' => Pages\CreatePenName::route('/create'),
            'edit' => Pages\EditPenName::route('/{record}/edit'),
        ];
    }
}
