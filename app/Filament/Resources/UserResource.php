<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Sistem';
    protected static ?string $pluralModelLabel = 'User';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Profil')
                ->schema([
                    Forms\Components\TextInput::make('name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('phone')->tel()->maxLength(20)->unique(ignoreRecord: true),
                    Forms\Components\Select::make('role')->required()->options([
                        'user' => 'User (Pembeli)', 'author' => 'Author (Penulis)', 'admin' => 'Admin',
                    ])->default('user'),
                ])->columns(2),

            Forms\Components\Section::make('Verifikasi')
                ->schema([
                    Forms\Components\DateTimePicker::make('email_verified_at')->label('Email verified'),
                    Forms\Components\DateTimePicker::make('wa_verified_at')->label('WhatsApp verified'),
                    Forms\Components\TextInput::make('google_id')->label('Google ID')->maxLength(255)->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Password')
                ->schema([
                    Forms\Components\TextInput::make('password')->password()->revealable()
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context): bool => $context === 'create')
                        ->helperText('Kosongkan untuk tidak mengubah password (saat edit)'),
                ])->collapsed(fn (string $context) => $context === 'edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone')->toggleable(),
                Tables\Columns\TextColumn::make('role')->badge()->color(fn (string $state) => match ($state) {
                    'admin' => 'danger', 'author' => 'info', default => 'gray',
                }),
                Tables\Columns\IconColumn::make('email_verified_at')->label('Email')->boolean()->trueIcon('heroicon-o-check-circle')->falseIcon('heroicon-o-x-circle')->toggleable(),
                Tables\Columns\IconColumn::make('wa_verified_at')->label('WA')->boolean()->trueIcon('heroicon-o-check-circle')->falseIcon('heroicon-o-x-circle')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Daftar')->date()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')->options([
                    'user' => 'User', 'author' => 'Author', 'admin' => 'Admin',
                ]),
            ])
            ->actions([
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
