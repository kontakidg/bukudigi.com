<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Jobs\GenerateArticleJob;
use App\Jobs\PostArticleToFacebookJob;
use App\Models\Article;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;
    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Blog';
    protected static ?string $navigationLabel = 'Artikel';
    protected static ?string $pluralModelLabel = 'Artikel';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Konten')->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Judul')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                        if ($operation === 'create') {
                            $set('slug', Str::slug($state) . '-' . Str::lower(Str::random(6)));
                        }
                    }),
                Forms\Components\TextInput::make('slug')
                    ->label('Slug URL')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Otomatis dari judul. Bisa diedit.'),
                Forms\Components\Textarea::make('excerpt')
                    ->label('Ringkasan (excerpt)')
                    ->rows(2)
                    ->maxLength(500)
                    ->helperText('Tampil di kartu blog & meta description default.')
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('content')
                    ->label('Isi Artikel')
                    ->required()
                    ->columnSpanFull()
                    ->toolbarButtons([
                        'bold', 'italic', 'underline', 'strike', 'link',
                        'h2', 'h3', 'bulletList', 'orderedList', 'blockquote',
                        'codeBlock', 'undo', 'redo',
                    ]),
            ])->columns(2),

            Forms\Components\Section::make('Meta')->schema([
                Forms\Components\FileUpload::make('cover_path')
                    ->label('Cover')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('article-covers')
                    ->maxSize(5120)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('category')
                    ->label('Kategori')
                    ->maxLength(100)
                    ->datalist(fn () => Article::query()->whereNotNull('category')->distinct()->pluck('category')->all())
                    ->helperText('Teks bebas, mis. "Tips Menulis", "Berita".'),
                Forms\Components\TextInput::make('author_name')
                    ->label('Penulis')
                    ->default('Tim bukudigi.com')
                    ->maxLength(120),
            ])->columns(2),

            Forms\Components\Section::make('Publikasi')->schema([
                Forms\Components\Select::make('status')
                    ->options([
                        'draft'     => 'Draft',
                        'scheduled' => 'Terjadwal',
                        'published' => 'Terbit',
                        'archived'  => 'Arsip',
                    ])
                    ->default('draft')
                    ->required()
                    ->live(),
                Forms\Components\DateTimePicker::make('published_at')
                    ->label('Waktu Terbit')
                    ->helperText('Untuk "Terjadwal": artikel auto-terbit pada waktu ini. Kosongkan untuk terbit sekarang.')
                    ->seconds(false),
            ])->columns(2),

            Forms\Components\Section::make('SEO (opsional)')->schema([
                Forms\Components\TextInput::make('meta_title')
                    ->label('Meta Title')
                    ->maxLength(255)
                    ->helperText('Kosongkan = pakai judul artikel.'),
                Forms\Components\Textarea::make('meta_description')
                    ->label('Meta Description')
                    ->rows(2)
                    ->maxLength(500)
                    ->helperText('Kosongkan = pakai ringkasan.'),
            ])->columns(1)->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_path')
                    ->label('Cover')
                    ->disk('public')
                    ->height(48)
                    ->width(72),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->limit(50)
                    ->weight('bold')
                    ->description(fn (Article $r) => $r->category ? '🏷️ ' . $r->category : null),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'published' => 'success',
                        'scheduled' => 'info',
                        'draft'     => 'gray',
                        'archived'  => 'warning',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'draft'     => 'Draft',
                        'scheduled' => 'Terjadwal',
                        'published' => 'Terbit',
                        'archived'  => 'Arsip',
                        default     => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('ai_status')
                    ->label('AI')
                    ->badge()
                    ->placeholder('—')
                    ->color(fn (?string $state) => match ($state) {
                        'done'       => 'success',
                        'generating' => 'info',
                        'pending'    => 'gray',
                        'failed'     => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'pending'    => 'Antri',
                        'generating' => 'Proses',
                        'done'       => 'Selesai',
                        'failed'     => 'Gagal',
                        default      => '—',
                    })
                    ->tooltip(fn (Article $r) => $r->ai_error),
                Tables\Columns\IconColumn::make('fb_posted_at')
                    ->label('FB')
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->getStateUsing(fn (Article $r) => $r->fb_posted_at !== null)
                    ->tooltip(fn (Article $r) => $r->fb_posted_at ? 'Dipost ' . $r->fb_posted_at->format('d M H:i') : 'Belum dipost'),
                Tables\Columns\TextColumn::make('views_count')
                    ->label('Views')
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Terbit')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'draft'     => 'Draft',
                    'scheduled' => 'Terjadwal',
                    'published' => 'Terbit',
                    'archived'  => 'Arsip',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Article $r) => route('blog.show', $r->slug), shouldOpenInNewTab: true)
                    ->visible(fn (Article $r) => $r->status === 'published'),
                Tables\Actions\Action::make('post_fb')
                    ->label('Post FB')
                    ->icon('heroicon-o-share')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (Article $r) => $r->status === 'published' && $r->fb_posted_at === null)
                    ->action(function (Article $r) {
                        PostArticleToFacebookJob::dispatch($r->id);
                        Notification::make()->title('Antrian post Facebook dikirim')->success()->send();
                    }),
                Tables\Actions\Action::make('regenerate')
                    ->label('Regenerate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Article $r) => $r->ai_status === 'failed')
                    ->form([
                        Forms\Components\TextInput::make('topic')->label('Topik')->required()
                            ->default(fn (Article $r) => $r->title),
                        Forms\Components\Select::make('length')->label('Panjang')
                            ->options(['pendek' => 'Pendek', 'sedang' => 'Sedang', 'panjang' => 'Panjang'])
                            ->default('sedang'),
                        Forms\Components\Select::make('image_style')->label('Gaya Gambar')
                            ->options(['ilustrasi' => 'Ilustrasi', 'foto' => 'Foto', 'minimalis' => 'Minimalis'])
                            ->default('ilustrasi'),
                    ])
                    ->action(function (Article $r, array $data) {
                        $words = match ($data['length']) {
                            'pendek' => 500, 'panjang' => 1200, default => 800,
                        };
                        $r->update(['ai_status' => 'pending', 'ai_error' => null]);
                        GenerateArticleJob::dispatch($r->id, $data['topic'], $words, $data['image_style']);
                        Notification::make()->title('Artikel diantrekan ulang')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Aktifkan / Terbitkan')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $n = 0;
                            foreach ($records as $r) {
                                if ($r->status !== 'published') {
                                    $r->update([
                                        'status'       => 'published',
                                        'published_at' => $r->published_at ?? now(),
                                    ]);
                                    PostArticleToFacebookJob::dispatch($r->id);
                                    $n++;
                                }
                            }
                            Notification::make()->title("{$n} artikel diterbitkan")->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('draft')
                        ->label('Jadikan Draft')
                        ->icon('heroicon-o-pencil')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['status' => 'draft']);
                            Notification::make()->title(count($records) . ' artikel jadi draft')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit'   => Pages\EditArticle::route('/{record}/edit'),
            'import' => Pages\ImportArticles::route('/import'),
        ];
    }
}
