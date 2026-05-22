<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookResource\Pages;
use App\Jobs\GeneratePreviewJob;
use App\Mail\BookApprovedMail;
use App\Mail\BookRejectedMail;
use App\Models\Book;
use App\Models\ModerationLog;
use App\Services\FonnteWhatsApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookResource extends Resource
{
    protected static ?string $model = Book::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Katalog';
    protected static ?string $navigationLabel = 'Buku';
    protected static ?string $pluralModelLabel = 'Buku';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = Book::where('status', 'pending_review')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Buku')
                ->schema([
                    Forms\Components\TextInput::make('title')->label('Judul')->required()->maxLength(255)->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', \Illuminate\Support\Str::slug($state).'-'.\Illuminate\Support\Str::random(6))),
                    Forms\Components\TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                    Forms\Components\Select::make('author_id')->label('Author Account')
                        ->relationship('author', 'display_name')->searchable()->preload()->required()
                        ->live()
                        ->afterStateUpdated(fn ($set) => $set('pen_name_id', null)),
                    Forms\Components\Select::make('pen_name_id')->label('Pen Name (publish sebagai)')
                        ->options(fn ($get) => $get('author_id')
                            ? \App\Models\PenName::where('author_id', $get('author_id'))->pluck('name', 'id')
                            : []
                        )->searchable()->preload()
                        ->helperText('Nama yang tampil sebagai penulis di publik. Kosong = default pen name.'),
                    Forms\Components\Select::make('category_id')->label('Kategori')
                        ->relationship('category', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('tags')->label('Tag')
                        ->relationship('tags', 'name')->multiple()->preload()->searchable(),
                    Forms\Components\Textarea::make('description')->label('Deskripsi')->required()->rows(5)->columnSpanFull(),
                    Forms\Components\Textarea::make('table_of_contents')->label('Daftar Isi')->rows(5)->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('File & Harga')
                ->schema([
                    Forms\Components\TextInput::make('cover_path')->label('Cover URL/path')->required()->columnSpanFull()
                        ->helperText('URL https://... atau path R2 relatif'),
                    Forms\Components\TextInput::make('pdf_master_path')->label('PDF master path')->required()->columnSpanFull()
                        ->helperText('Path file PDF di R2 private bucket'),
                    Forms\Components\TextInput::make('price')->label('Harga (IDR)')->required()->numeric()
                        ->prefix('Rp')->minValue(15000)->maxValue(500000),
                    Forms\Components\TextInput::make('page_count')->label('Jumlah Halaman')->numeric(),
                    Forms\Components\TextInput::make('file_size_bytes')->label('Ukuran File (bytes)')->numeric(),
                ])->columns(2),

            Forms\Components\Section::make('Status & Moderasi')
                ->schema([
                    Forms\Components\Select::make('status')->required()->options([
                        'draft' => 'Draft',
                        'pending_review' => 'Menunggu Review',
                        'active' => 'Aktif (Live)',
                        'rejected' => 'Ditolak',
                        'archived' => 'Archived',
                    ]),
                    Forms\Components\Toggle::make('ai_disclosure')->label('Disclosure: Penulis pakai AI?'),
                    Forms\Components\Textarea::make('rejection_reason')->label('Alasan Penolakan')->rows(2)->columnSpanFull(),
                    Forms\Components\DateTimePicker::make('submitted_at')->label('Disubmit pada'),
                    Forms\Components\DateTimePicker::make('approved_at')->label('Disetujui pada'),
                ])->columns(2)->collapsed(),

            Forms\Components\Section::make('Statistik (read-only)')
                ->schema([
                    Forms\Components\TextInput::make('sales_count')->label('Terjual')->numeric()->disabled(),
                    Forms\Components\TextInput::make('total_revenue')->label('Total Revenue')->numeric()->disabled()->prefix('Rp'),
                ])->columns(2)->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_path')->label('')->size(48),
                Tables\Columns\TextColumn::make('title')->label('Judul')->searchable()->sortable()->limit(50)->weight('semibold'),
                Tables\Columns\TextColumn::make('penName.name')->label('Pen Name')->searchable()
                    ->placeholder('— default —'),
                Tables\Columns\TextColumn::make('author.display_name')->label('Author Account')->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('category.name')->label('Kategori')->badge(),
                Tables\Columns\TextColumn::make('price')->label('Harga')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'active' => 'success',
                    'pending_review' => 'warning',
                    'rejected' => 'danger',
                    'archived' => 'gray',
                    default => 'secondary',
                }),
                Tables\Columns\TextColumn::make('sales_count')->label('Terjual')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('approved_at')->label('Approved')->date()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'pending_review' => 'Pending Review',
                    'active' => 'Active', 'rejected' => 'Rejected', 'archived' => 'Archived',
                ]),
                SelectFilter::make('category_id')->label('Kategori')->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')->label('Approve')
                    ->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->visible(fn (Book $r): bool => in_array($r->status, ['pending_review', 'draft']))
                    ->action(function (Book $r): void {
                        $r->update(['status' => 'active', 'approved_at' => now(), 'approved_by' => auth()->id()]);

                        ModerationLog::create([
                            'book_id' => $r->id,
                            'provider' => 'admin',
                            'decision' => 'approve',
                            'admin_id' => auth()->id(),
                        ]);

                        // Regenerate preview kalau belum ada
                        if (! $r->preview_pdf_path) {
                            GeneratePreviewJob::dispatch($r->id);
                        }

                        // WA notif ke author
                        $author = $r->author?->user;
                        if ($author?->phone) {
                            $url = route('books.show', $r->slug);
                            app(FonnteWhatsApp::class)->send(
                                $author->phone,
                                "Halo {$author->name}! 🎉\n\nBuku kamu \"{$r->title}\" sudah LIVE di bukudigi.com!\n\nLihat: {$url}\n\nRoyalti akan otomatis masuk ke saldo kamu tiap ada pembelian."
                            );
                        }

                        // Email notif ke author
                        if ($author?->email) {
                            try {
                                Mail::to($author->email)->queue(new BookApprovedMail($r));
                            } catch (\Throwable $e) {
                                Log::warning('BookApprovedMail queue failed: '.$e->getMessage());
                            }
                        }

                        Notification::make()->title('Buku disetujui & author dinotifikasi')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')->label('Reject')
                    ->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (Book $r): bool => $r->status !== 'rejected')
                    ->form([
                        Forms\Components\Textarea::make('reason')->label('Alasan')->required()->rows(3),
                    ])
                    ->action(function (Book $r, array $data): void {
                        $r->update(['status' => 'rejected', 'rejection_reason' => $data['reason']]);

                        ModerationLog::create([
                            'book_id' => $r->id,
                            'provider' => 'admin',
                            'decision' => 'reject',
                            'admin_id' => auth()->id(),
                            'reason' => $data['reason'],
                        ]);

                        $author = $r->author?->user;
                        if ($author?->phone) {
                            app(FonnteWhatsApp::class)->send(
                                $author->phone,
                                "Halo {$author->name},\n\nBuku \"{$r->title}\" belum bisa kami publikasikan.\n\nAlasan: {$data['reason']}\n\nKamu bisa edit & submit ulang dari dashboard penulis."
                            );
                        }

                        // Email notif rejection
                        if ($author?->email) {
                            try {
                                Mail::to($author->email)->queue(new BookRejectedMail($r, $data['reason']));
                            } catch (\Throwable $e) {
                                Log::warning('BookRejectedMail queue failed: '.$e->getMessage());
                            }
                        }

                        Notification::make()->title('Buku ditolak & author dinotifikasi')->warning()->send();
                    }),
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
            'index' => Pages\ListBooks::route('/'),
            'create' => Pages\CreateBook::route('/create'),
            'edit' => Pages\EditBook::route('/{record}/edit'),
        ];
    }
}
