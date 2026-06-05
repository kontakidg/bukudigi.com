<?php

namespace App\Filament\Pages;

use App\Jobs\GenerateArticleJob;
use App\Models\Article;
use App\Services\AnthropicService;
use App\Services\KieImageService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class ArticleGenerator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Blog';
    protected static ?string $navigationLabel = 'Generator AI';
    protected static ?string $title = 'Generator Artikel AI';
    protected static ?int    $navigationSort = 2;

    protected static string $view = 'filament.pages.article-generator';

    public ?array $data = [];

    /** Step: 1 = input, 2 = preview judul */
    public int $step = 1;

    /** Judul hasil preview (editable) */
    public array $titles = [];

    /** Status konfigurasi API (untuk banner di view) */
    public bool $anthropicActive = false;
    public bool $kieActive = false;

    public function mount(): void
    {
        $this->anthropicActive = app(AnthropicService::class)->isActive();
        $this->kieActive = app(KieImageService::class)->isActive();

        $this->form->fill([
            'count'          => 3,
            'interval_hours' => 5,
            'start_at'       => now()->format('Y-m-d H:i:s'),
            'length'         => 'sedang',
            'image_style'    => 'ilustrasi',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Forms\Components\Section::make('Parameter Generate')->schema([
                Forms\Components\Textarea::make('topic')
                    ->label('Topik')
                    ->required()
                    ->rows(2)
                    ->maxLength(500)
                    ->placeholder('mis. "Tips menulis dan menerbitkan ebook untuk penulis pemula"'),
                Forms\Components\TextInput::make('count')
                    ->label('Jumlah Artikel')
                    ->numeric()->required()->minValue(1)->maxValue(20)->default(3)
                    ->helperText('Maksimal 20 per batch.'),
                Forms\Components\TextInput::make('interval_hours')
                    ->label('Jeda Publish (jam)')
                    ->numeric()->required()->minValue(1)->maxValue(720)->default(5)
                    ->helperText('Jarak waktu terbit antar artikel.'),
                Forms\Components\DateTimePicker::make('start_at')
                    ->label('Mulai Dari')
                    ->required()->seconds(false)->default(now())
                    ->helperText('Artikel ke-1 terbit pada waktu ini (default: sekarang).'),
                Forms\Components\Select::make('length')
                    ->label('Panjang Artikel')
                    ->options([
                        'pendek' => 'Pendek (~500 kata)',
                        'sedang' => 'Sedang (~800 kata)',
                        'panjang'=> 'Panjang (~1200 kata)',
                    ])->default('sedang')->required(),
                Forms\Components\Select::make('image_style')
                    ->label('Gaya Gambar')
                    ->options([
                        'ilustrasi' => 'Ilustrasi digital',
                        'foto'      => 'Foto realistis',
                        'minimalis' => 'Minimalis / flat',
                    ])->default('ilustrasi')->required(),
            ])->columns(2),
        ]);
    }

    /** STEP 1 → 2: minta preview judul ke Claude. */
    public function previewTitles(): void
    {
        $ai = app(AnthropicService::class);
        if (! $ai->isActive()) {
            Notification::make()->title('Anthropic API key belum diisi di .env')->danger()->send();
            return;
        }

        $state = $this->form->getState();

        try {
            $titles = $ai->generateTitles($state['topic'], (int) $state['count']);
        } catch (\Throwable $e) {
            Notification::make()->title('Gagal membuat preview judul')->body($e->getMessage())->danger()->send();
            return;
        }

        $this->titles = array_values($titles);
        $this->step = 2;
    }

    public function backToInput(): void
    {
        $this->step = 1;
    }

    /** STEP 2: buat artikel + dispatch job generate. */
    public function generate(): void
    {
        $state = $this->form->getState();

        $titles = array_values(array_filter(array_map('trim', $this->titles), fn ($t) => $t !== ''));
        if (empty($titles)) {
            Notification::make()->title('Tidak ada judul untuk di-generate')->warning()->send();
            return;
        }

        $words = match ($state['length']) {
            'pendek'  => 500,
            'panjang' => 1200,
            default   => 800,
        };

        $start    = Carbon::parse($state['start_at']);
        $interval = max(1, (int) $state['interval_hours']);
        $style    = $state['image_style'];
        $topic    = $state['topic'];

        $n = 0;
        foreach ($titles as $i => $title) {
            $publishAt = $start->copy()->addHours($i * $interval);

            $article = Article::create([
                'title'        => $title,
                'content'      => '',
                'status'       => 'draft',
                'author_name'  => 'Tim bukudigi.com',
                'published_at' => $publishAt,
                'ai_generated' => true,
                'ai_status'    => 'pending',
                'created_by'   => auth()->id(),
            ]);

            GenerateArticleJob::dispatch($article->id, $topic, $words, $style);
            $n++;
        }

        Notification::make()
            ->title("{$n} artikel diantrekan untuk di-generate")
            ->body('Pantau progres di menu Artikel (kolom AI). Artikel terbit otomatis sesuai jadwal.')
            ->success()->persistent()->send();

        // Reset
        $this->titles = [];
        $this->step = 1;
        $this->form->fill([
            'count'          => 3,
            'interval_hours' => $interval,
            'start_at'       => now()->format('Y-m-d H:i:s'),
            'length'         => $state['length'],
            'image_style'    => $style,
        ]);
    }
}
