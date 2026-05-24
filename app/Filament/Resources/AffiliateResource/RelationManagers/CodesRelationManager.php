<?php

namespace App\Filament\Resources\AffiliateResource\RelationManagers;

use App\Models\AffiliateCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CodesRelationManager extends RelationManager
{
    protected static string $relationship = 'codes';
    protected static ?string $title = 'Kode Affiliate';
    protected static ?string $recordTitleAttribute = 'code';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->label('Kode')->required()->maxLength(32)
                ->helperText('A-Z, 0-9, _ atau -. Wajib unique global.')
                ->dehydrateStateUsing(fn ($state) => strtoupper(trim($state ?? ''))),
            Forms\Components\TextInput::make('label')->label('Label')->maxLength(60),
            Forms\Components\Toggle::make('is_default')->label('Default?')->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->fontFamily('mono')->weight('semibold')->searchable(),
                Tables\Columns\TextColumn::make('label')->toggleable(),
                Tables\Columns\IconColumn::make('is_default')->boolean()->label('Default'),
                Tables\Columns\TextColumn::make('clicks_count')->label('Klik')->sortable(),
                Tables\Columns\TextColumn::make('conversions_count')->label('Konv.')->sortable(),
                Tables\Columns\TextColumn::make('total_earned')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->date()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['code'] = $data['code'] ?: AffiliateCode::generateUniqueCode();
                        return $data;
                    })
                    ->before(function (array $data, RelationManager $livewire) {
                        $count = $livewire->getOwnerRecord()->codes()->count();
                        if ($count >= AffiliateCode::MAX_PER_AFFILIATE) {
                            Notification::make()->title('Maks '.AffiliateCode::MAX_PER_AFFILIATE.' kode per affiliate')->danger()->send();
                            $livewire->halt();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('set_default')->label('Set Default')->icon('heroicon-o-star')->color('warning')
                    ->visible(fn (AffiliateCode $r) => ! $r->is_default)
                    ->action(function (AffiliateCode $r): void {
                        $r->affiliate->codes()->update(['is_default' => false]);
                        $r->update(['is_default' => true]);
                        Notification::make()->title('Kode default di-update')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (AffiliateCode $r) => ! $r->is_default && $r->conversions_count === 0),
            ]);
    }
}
