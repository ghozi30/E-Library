<?php

use App\Models\Book;
use App\Models\Bookmark;
use App\Models\Borrowing;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Katalog Buku - E-Library Sekolah')] class extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $category_id = '';

    #[Url(history: true)]
    public bool $onlyAvailable = false;

    #[Url(history: true)]
    public string $sortBy = 'latest';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedOnlyAvailable(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }

    public function selectCategory(string $categoryId): void
    {
        $this->category_id = $categoryId;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'category_id', 'onlyAvailable', 'sortBy']);
        $this->resetPage();
    }

    public function toggleBookmark(int $bookId): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        DB::transaction(function () use ($bookId): void {
            Book::whereKey($bookId)->lockForUpdate()->firstOrFail();

            $existing = Bookmark::where('user_id', auth()->id())
                ->where('book_id', $bookId);

            if ($existing->exists()) {
                $existing->delete();
            } else {
                Bookmark::create([
                    'user_id' => auth()->id(),
                    'book_id' => $bookId,
                ]);
            }
        });

        unset($this->bookmarkedBookIds, $this->readerSnapshot);
    }

    public function isFiltering(): bool
    {
        return trim($this->search) !== '' || $this->category_id !== '' || $this->onlyAvailable;
    }

    /**
     * Deterministic archival book-cloth cover theme for books without an uploaded cover.
     *
     * @return array{bg: string, title: string, subtitle: string, accent: string, pill: string}
     */
    public function coverTheme(Book $book): array
    {
        $themes = [
            0 => [
                'bg' => 'bg-[#114B43]',
                'title' => 'text-[#FAF8F4]',
                'subtitle' => 'text-[#D7EDE8]',
                'accent' => 'bg-[#FDE68A]',
                'pill' => 'bg-white/15 text-[#FAF8F4]',
            ],
            1 => [
                'bg' => 'bg-[#7C2D12]',
                'title' => 'text-[#FFF7ED]',
                'subtitle' => 'text-[#FED7AA]',
                'accent' => 'bg-[#FDBA74]',
                'pill' => 'bg-white/15 text-[#FFF7ED]',
            ],
            2 => [
                'bg' => 'bg-[#1E293B]',
                'title' => 'text-[#F8FAFC]',
                'subtitle' => 'text-[#CBD5E1]',
                'accent' => 'bg-[#93C5FD]',
                'pill' => 'bg-white/15 text-[#F8FAFC]',
            ],
            3 => [
                'bg' => 'bg-[#3F3A36]',
                'title' => 'text-[#FAF8F4]',
                'subtitle' => 'text-[#E7E2DC]',
                'accent' => 'bg-[#E7D3B0]',
                'pill' => 'bg-white/15 text-[#FAF8F4]',
            ],
            4 => [
                'bg' => 'bg-[#5B1D35]',
                'title' => 'text-[#FDF2F8]',
                'subtitle' => 'text-[#F5D0E0]',
                'accent' => 'bg-[#FBCFE8]',
                'pill' => 'bg-white/15 text-[#FDF2F8]',
            ],
        ];

        $index = ((int) ($book->category_id ?? $book->id)) % count($themes);

        return $themes[$index];
    }

    /**
     * @return array{titles: int, stock: int, categories: int}
     */
    #[Computed]
    public function catalogSummary(): array
    {
        return [
            'titles' => Book::count(),
            'stock' => (int) Book::sum('stock'),
            'categories' => Category::count(),
        ];
    }

    /**
     * @return array<int, int>
     */
    #[Computed]
    public function bookmarkedBookIds(): array
    {
        if (auth()->guest()) {
            return [];
        }

        return Bookmark::where('user_id', auth()->id())
            ->pluck('book_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Contextual reading desk snapshot for authenticated student or admin.
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function readerSnapshot(): ?array
    {
        if (auth()->guest()) {
            return null;
        }

        $user = auth()->user();

        if ($user->isAdmin()) {
            return [
                'role' => 'admin',
                'active_loans' => Borrowing::where('status', 'dipinjam')->count(),
                'overdue_loans' => Borrowing::where('status', 'dipinjam')
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count(),
            ];
        }

        $today = now()->startOfDay();
        $activeLoans = Borrowing::with('book')
            ->where('user_id', $user->id)
            ->where('status', 'dipinjam')
            ->orderBy('due_date')
            ->get();

        $overdueCount = 0;
        $runningFine = 0;

        foreach ($activeLoans as $loan) {
            $dueDate = $loan->due_date->copy()->startOfDay();

            if ($today->greaterThan($dueDate)) {
                $overdueCount++;
                $daysLate = (int) $dueDate->diffInDays($today);
                $runningFine += max((int) $loan->fine_amount, $daysLate * 2000);
            }
        }

        $nextLoan = $activeLoans->first();
        $nextDueDays = $nextLoan
            ? (int) $today->diffInDays($nextLoan->due_date->copy()->startOfDay(), false)
            : null;

        return [
            'role' => 'siswa',
            'active_count' => $activeLoans->count(),
            'overdue_count' => $overdueCount,
            'running_fine' => $runningFine,
            'bookmarks_count' => count($this->bookmarkedBookIds),
            'next_loan' => $nextLoan,
            'next_due_days' => $nextDueDays,
        ];
    }

    #[Computed]
    public function featuredBook(): ?Book
    {
        if ($this->isFiltering() || auth()->check()) {
            return null;
        }

        return Book::with('category')
            ->latest()
            ->orderByDesc('id')
            ->first();
    }

    public function with(): array
    {
        $categories = Category::withCount('books')
            ->orderBy('name')
            ->get();

        $term = trim($this->search);

        $books = Book::with('category')
            ->withCount('borrowings')
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($subQuery) use ($term): void {
                    $subQuery->where('title', 'like', "%{$term}%")
                        ->orWhere('author', 'like', "%{$term}%");
                });
            })
            ->when($this->category_id !== '', function ($query): void {
                $query->where('category_id', $this->category_id);
            })
            ->when($this->onlyAvailable, function ($query): void {
                $query->where('stock', '>', 0);
            })
            ->when($this->sortBy === 'popular', fn ($query) => $query->orderByDesc('borrowings_count')->latest())
            ->when($this->sortBy === 'title', fn ($query) => $query->orderBy('title'))
            ->when(! in_array($this->sortBy, ['popular', 'title'], true), fn ($query) => $query->latest())
            ->paginate(8);

        return [
            'books' => $books,
            'categories' => $categories,
        ];
    }
};
?>

<div class="pb-16">
    <!-- Asymmetric Editorial Portal Masthead -->
    <section class="border-b border-[#E5E0D8] bg-[#FAF8F4]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
            @php
                $summary = $this->catalogSummary;
                $reader = $this->readerSnapshot;
                $featured = $this->featuredBook;
            @endphp

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-center">
                <!-- Left 7 Cols: Editorial Display, Search Hub & Catalog Tally -->
                <div class="{{ ($reader || $featured) ? 'lg:col-span-7' : 'lg:col-span-12 max-w-3xl' }} space-y-6">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-[11px] uppercase tracking-[0.14em] text-[#5E5850]">
                        <span>Katalog Induk Sekolah</span>
                        <span aria-hidden="true" class="text-[#C8C0B4]">/</span>
                        <span class="tabular-nums text-[#114B43] font-medium">{{ number_format($summary['titles']) }} Judul &middot; {{ number_format($summary['stock']) }} Eksemplar</span>
                    </div>

                    <div class="space-y-3">
                        <h1 class="font-serif text-3xl sm:text-5xl font-semibold tracking-tight text-[#181615] leading-[1.12]">
                            @if ($reader && $reader['role'] === 'siswa')
                                Selamat membaca kembali, <span class="italic font-normal text-[#114B43]">{{ explode(' ', trim(auth()->user()->name))[0] }}</span>.
                            @else
                                Temukan bacaan bermutu di rak perpustakaan sekolah.
                            @endif
                        </h1>

                        <p class="text-sm sm:text-base text-[#5E5850] leading-relaxed max-w-2xl">
                            Telusuri koleksi buku pelajaran, literatur sains, fiksi pilihan, dan referensi teknologi berdasarkan judul, penulis, maupun lokasi rak fisik.
                        </p>
                    </div>

                    <!-- Integrated Search Bar -->
                    <div class="flex flex-col sm:flex-row gap-2.5 pt-1">
                        <div class="relative flex-1">
                            <label for="catalog-search-input" class="sr-only">Cari judul buku atau nama penulis</label>
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#5E5850]">
                                <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                id="catalog-search-input"
                                wire:model.live.debounce.300ms="search"
                                type="search"
                                placeholder="Cari judul buku atau nama penulis..."
                                class="w-full min-h-[46px] pl-11 pr-10 rounded-lg bg-white border border-[#D6D0C6] text-sm text-[#181615] placeholder-[#666058] focus:outline-none focus:border-[#114B43] focus:ring-2 focus:ring-[#114B43]/15 transition-colors"
                            >
                            @if ($search !== '')
                                <button
                                    type="button"
                                    wire:click="$set('search', '')"
                                    aria-label="Hapus kata kunci pencarian"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#5E5850] hover:text-[#181615] cursor-pointer"
                                >
                                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @endif
                        </div>

                        <button
                            type="button"
                            wire:click="$toggle('onlyAvailable')"
                            aria-pressed="{{ $onlyAvailable ? 'true' : 'false' }}"
                            @class([
                                'min-h-[46px] inline-flex items-center justify-center gap-2 px-4 rounded-lg text-xs font-semibold border transition-colors cursor-pointer shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43]',
                                'bg-[#114B43] text-[#FAF8F4] border-[#114B43]' => $onlyAvailable,
                                'bg-white text-[#181615] border-[#D6D0C6] hover:border-[#114B43] hover:text-[#114B43]' => ! $onlyAvailable,
                            ])
                        >
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Stok Tersedia</span>
                        </button>
                    </div>
                </div>

                <!-- Right 5 Cols: Contextual Wing (Student Desk / Admin Quick Wing / Guest Spotlight) -->
                @if ($reader && $reader['role'] === 'siswa')
                    <div class="lg:col-span-5">
                        <div class="bg-[#114B43] text-[#FAF8F4] rounded-xl p-6 border border-[#0C3832] space-y-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-mono text-[10px] uppercase tracking-[0.14em] text-[#B7D4CE]">
                                        Kartu Baca Siswa
                                    </div>
                                    <div class="font-serif text-xl font-medium mt-1">
                                        {{ auth()->user()->name }}
                                    </div>
                                    <div class="font-mono text-[11px] text-[#CBE2DC] mt-0.5">
                                        {{ auth()->user()->nis ? 'NIS ' . auth()->user()->nis : 'Anggota' }}
                                        {{ auth()->user()->class_name ? '• ' . auth()->user()->class_name : '' }}
                                    </div>
                                </div>

                                @if ($reader['overdue_count'] > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-[#FEE2E2] text-[#991B1B] font-mono text-[11px] font-semibold tabular-nums shrink-0">
                                        {{ $reader['overdue_count'] }} Terlambat
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-white/10 text-[#D7EDE8] font-mono text-[11px] shrink-0">
                                        Bebas Denda
                                    </span>
                                @endif
                            </div>

                            <!-- 2-Metric Ledger Inside Student Card -->
                            <div class="grid grid-cols-2 gap-3 pt-1">
                                <a href="/my-borrowings" wire:navigate class="rounded-lg bg-[#0C3832] hover:bg-[#092C27] border border-white/10 p-3.5 transition-colors">
                                    <div class="font-mono text-[10px] uppercase tracking-[0.1em] text-[#A7C4BE]">Dipinjam</div>
                                    <div class="font-serif text-2xl font-medium tabular-nums mt-1">{{ $reader['active_count'] }} <span class="font-sans text-xs text-[#CBE2DC]">buku</span></div>
                                </a>
                                <a href="/my-bookmarks" wire:navigate class="rounded-lg bg-[#0C3832] hover:bg-[#092C27] border border-white/10 p-3.5 transition-colors">
                                    <div class="font-mono text-[10px] uppercase tracking-[0.1em] text-[#A7C4BE]">Favorit</div>
                                    <div class="font-serif text-2xl font-medium tabular-nums mt-1">{{ $reader['bookmarks_count'] }} <span class="font-sans text-xs text-[#CBE2DC]">judul</span></div>
                                </a>
                            </div>

                            <!-- Next Due Loan or Calm Prompt -->
                            @if ($reader['next_loan'])
                                @php
                                    $nextLoan = $reader['next_loan'];
                                    $daysLeft = $reader['next_due_days'];
                                @endphp
                                <div class="pt-4 border-t border-white/15 flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="font-mono text-[10px] uppercase tracking-[0.1em] text-[#A7C4BE]">
                                            {{ $daysLeft < 0 ? 'Segera Kembalikan' : 'Tenggat Terdekat' }}
                                        </div>
                                        <div class="text-sm font-semibold truncate mt-0.5" title="{{ $nextLoan->book?->title }}">
                                            {{ $nextLoan->book?->title ?? 'Buku Dipinjam' }}
                                        </div>
                                        <div class="font-mono text-[11px] tabular-nums mt-0.5 {{ $daysLeft < 0 ? 'text-[#FCA5A5]' : 'text-[#FDE68A]' }}">
                                            @if ($daysLeft < 0)
                                                Terlambat {{ abs($daysLeft) }} hari &middot; Denda Rp {{ number_format($reader['running_fine'], 0, ',', '.') }}
                                            @elseif ($daysLeft === 0)
                                                Jatuh tempo hari ini
                                            @else
                                                {{ $daysLeft }} hari tersisa ({{ $nextLoan->due_date?->copy()->locale('id')->translatedFormat('d M Y') }})
                                            @endif
                                        </div>
                                    </div>
                                    <a
                                        href="/my-borrowings"
                                        wire:navigate
                                        class="min-h-[38px] inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#FAF8F4] text-[#114B43] hover:bg-[#EFECE4] transition-colors shrink-0"
                                    >
                                        Detail &rarr;
                                    </a>
                                </div>
                            @else
                                <div class="pt-4 border-t border-white/15 flex items-center justify-between gap-3">
                                    <p class="text-xs text-[#CBE2DC]">Pilih buku dari katalog untuk memulai pinjaman baru.</p>
                                    <a href="/my-borrowings" wire:navigate class="text-xs font-semibold text-[#FDE68A] hover:underline shrink-0">
                                        Riwayat &rarr;
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @elseif ($reader && $reader['role'] === 'admin')
                    <div class="lg:col-span-5">
                        <div class="bg-[#114B43] text-[#FAF8F4] rounded-xl p-6 border border-[#0C3832] space-y-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-mono text-[10px] uppercase tracking-[0.14em] text-[#B7D4CE]">Mode Pustakawan</div>
                                    <div class="font-serif text-xl font-medium mt-1">Ringkasan Sirkulasi Hari Ini</div>
                                </div>
                                @if ($reader['overdue_loans'] > 0)
                                    <span class="px-2.5 py-1 rounded-md bg-[#FEE2E2] text-[#991B1B] font-mono text-[11px] font-semibold tabular-nums">
                                        {{ $reader['overdue_loans'] }} Terlambat
                                    </span>
                                @endif
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-lg bg-[#0C3832] border border-white/10 p-3.5">
                                    <div class="font-mono text-[10px] uppercase tracking-[0.1em] text-[#A7C4BE]">Sedang Dipinjam</div>
                                    <div class="font-serif text-2xl font-medium tabular-nums mt-1">{{ $reader['active_loans'] }}</div>
                                </div>
                                <div class="rounded-lg bg-[#0C3832] border border-white/10 p-3.5">
                                    <div class="font-mono text-[10px] uppercase tracking-[0.1em] text-[#A7C4BE]">Total Koleksi</div>
                                    <div class="font-serif text-2xl font-medium tabular-nums mt-1">{{ $summary['titles'] }} <span class="font-sans text-xs text-[#CBE2DC]">jdl</span></div>
                                </div>
                            </div>
                            <div class="pt-2 flex flex-wrap gap-2">
                                <a href="/admin/dashboard" wire:navigate class="min-h-[40px] flex-1 inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-semibold bg-[#FAF8F4] text-[#114B43] hover:bg-[#EFECE4] transition-colors">
                                    Buka Dashboard Admin &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                @elseif ($featured)
                    @php
                        $featuredTheme = $this->coverTheme($featured);
                    @endphp
                    <div class="lg:col-span-5">
                        <div
                            @dblclick="if (!$event.target.closest('button, a')) Livewire.navigate('{{ route('books.detail', $featured->id) }}')"
                            title="Klik 2x untuk membuka detail buku"
                            class="bg-white rounded-xl border border-[#E5E0D8] p-5 sm:p-6 space-y-4 cursor-pointer"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-[10px] uppercase tracking-[0.14em] text-[#5E5850]">
                                    Koleksi Terbaru
                                </span>
                                @if ($featured->category)
                                    <span class="font-mono text-[10px] px-2 py-0.5 rounded bg-[#F7F5F0] text-[#181615] border border-[#E5E0D8]">
                                        {{ $featured->category->rack_code }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex gap-4 items-start">
                                <!-- Tactile Mini Book Cover -->
                                <a href="{{ route('books.detail', $featured->id) }}" wire:navigate class="w-24 aspect-[3/4] rounded-md overflow-hidden shrink-0 border border-[#D6D0C6] group">
                                    @if ($featured->cover_image)
                                        <img
                                            src="{{ asset('storage/' . $featured->cover_image) }}"
                                            alt="Sampul {{ $featured->title }}"
                                            class="w-full h-full object-cover group-hover:scale-[1.03] motion-reduce:transform-none transition-transform duration-300"
                                        >
                                    @else
                                        <div class="w-full h-full {{ $featuredTheme['bg'] }} {{ $featuredTheme['title'] }} p-2.5 flex flex-col justify-between border-l-4 border-black/25">
                                            <span class="h-0.5 w-5 {{ $featuredTheme['accent'] }}"></span>
                                            <div class="font-serif text-[11px] font-semibold leading-tight line-clamp-4">
                                                {{ $featured->title }}
                                            </div>
                                            <div class="text-[9px] {{ $featuredTheme['subtitle'] }} truncate">
                                                {{ $featured->author }}
                                            </div>
                                        </div>
                                    @endif
                                </a>

                                <div class="min-w-0 flex-1 space-y-2">
                                    <h2 class="font-serif text-lg font-semibold text-[#181615] leading-snug line-clamp-2 [overflow-wrap:anywhere]">
                                        <a href="{{ route('books.detail', $featured->id) }}" wire:navigate class="hover:text-[#114B43] transition-colors">
                                            {{ $featured->title }}
                                        </a>
                                    </h2>
                                    <p class="text-xs text-[#5E5850] [overflow-wrap:anywhere]">{{ $featured->author }}</p>
                                    @if ($featured->synopsis)
                                        <p class="text-xs text-[#5E5850] leading-relaxed line-clamp-2 [overflow-wrap:anywhere]">
                                            {{ $featured->synopsis }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="pt-3 border-t border-[#EFECE4] flex items-center justify-between gap-3">
                                <a href="{{ route('books.detail', $featured->id) }}" wire:navigate class="text-xs font-semibold text-[#114B43] hover:text-[#0C3832] transition-colors">
                                    Lihat Buku &rarr;
                                </a>
                                <a href="/login" wire:navigate class="text-xs font-medium text-[#5E5850] hover:text-[#181615] transition-colors">
                                    Masuk untuk Meminjam
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- Main Catalog Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 space-y-6">
        <!-- Shelf Index Tabs & Sort Bar -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-[#E5E0D8]">
            <!-- Interactive Shelf Category Index -->
            <div class="flex flex-wrap items-center gap-2" role="tablist" aria-label="Filter rak kategori buku">
                <button
                    type="button"
                    wire:click="selectCategory('')"
                    role="tab"
                    aria-selected="{{ $category_id === '' ? 'true' : 'false' }}"
                    @class([
                        'min-h-[38px] shrink-0 inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43]',
                        'bg-[#181615] text-[#FAF8F4] border-[#181615]' => $category_id === '',
                        'bg-white text-[#5E5850] border-[#D6D0C6] hover:border-[#181615] hover:text-[#181615]' => $category_id !== '',
                    ])
                >
                    <span>Semua Rak</span>
                    <span class="font-mono text-[10px] opacity-75 tabular-nums">{{ $summary['titles'] }}</span>
                </button>

                @foreach ($categories as $category)
                    @php
                        $isSelected = (string) $category_id === (string) $category->id;
                    @endphp
                    <button
                        wire:key="shelf-tab-{{ $category->id }}"
                        type="button"
                        wire:click="selectCategory('{{ $category->id }}')"
                        role="tab"
                        aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                        @class([
                            'min-h-[38px] shrink-0 inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold border transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43]',
                            'bg-[#114B43] text-[#FAF8F4] border-[#114B43]' => $isSelected,
                            'bg-white text-[#181615] border-[#D6D0C6] hover:border-[#114B43] hover:text-[#114B43]' => ! $isSelected,
                        ])
                    >
                        <span>{{ $category->name }}</span>
                        <span @class([
                            'font-mono text-[10px] px-1.5 py-0.5 rounded tabular-nums',
                            'bg-white/15 text-[#FAF8F4]' => $isSelected,
                            'bg-[#F7F5F0] text-[#5E5850]' => ! $isSelected,
                        ])>
                            {{ $category->rack_code }}
                        </span>
                    </button>
                @endforeach
            </div>

            <!-- Accessible Select Dropdown + Sort + Reset -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <div class="sr-only">
                    <label for="catalog-category-select">Pilih Kategori Rak</label>
                    <select id="catalog-category-select" wire:model.live="category_id">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->rack_code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="catalog-sort-select" class="sr-only">Urutkan buku</label>
                    <select
                        id="catalog-sort-select"
                        wire:model.live="sortBy"
                        class="min-h-[38px] px-3 py-1.5 rounded-lg bg-white border border-[#D6D0C6] text-xs font-semibold text-[#181615] focus:outline-none focus:border-[#114B43] focus:ring-2 focus:ring-[#114B43]/15 cursor-pointer"
                    >
                        <option value="latest">Urutkan: Terbaru</option>
                        <option value="popular">Urutkan: Paling Sering Dipinjam</option>
                        <option value="title">Urutkan: Judul A–Z</option>
                    </select>
                </div>

                @if ($this->isFiltering())
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="min-h-[38px] inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#FEF2F2] text-[#991B1B] border border-[#FECACA] hover:bg-[#FEE2E2] transition-colors cursor-pointer"
                    >
                        <span>Reset</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Results Status Bar -->
        <div class="flex items-center justify-between text-xs text-[#5E5850]">
            <p class="tabular-nums">
                Menampilkan <span class="font-semibold text-[#181615]">{{ $books->count() }}</span> dari <span class="font-semibold text-[#181615]">{{ $books->total() }}</span> judul buku
                @if ($search !== '')
                    untuk kata kunci "<span class="font-semibold text-[#181615]">{{ $search }}</span>"
                @endif
            </p>
            <div wire:loading class="font-mono text-[11px] text-[#114B43]">
                Memuat rak buku...
            </div>
        </div>

        <!-- Book Grid -->
        @if ($books->isEmpty())
            <div class="bg-white rounded-xl border border-[#E5E0D8] px-6 py-14 text-center space-y-3">
                <div class="w-12 h-12 mx-auto rounded-full bg-[#F7F5F0] border border-[#E5E0D8] flex items-center justify-center text-[#5E5850]">
                    <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <h2 class="font-serif text-xl font-semibold text-[#181615]">Koleksi tidak ditemukan</h2>
                <p class="text-sm text-[#5E5850] max-w-md mx-auto">
                    Tidak ada judul buku yang cocok dengan pencarian atau filter rak yang sedang aktif.
                </p>
                <div class="pt-1">
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="min-h-[40px] inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold bg-[#114B43] text-[#FAF8F4] hover:bg-[#0C3832] transition-colors cursor-pointer"
                    >
                        Tampilkan Semua Koleksi
                    </button>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" wire:loading.class="opacity-60 transition-opacity">
                @foreach ($books as $book)
                    @php
                        $theme = $this->coverTheme($book);
                        $isBookmarked = in_array($book->id, $this->bookmarkedBookIds, true);
                    @endphp
                    <article
                        wire:key="catalog-book-{{ $book->id }}"
                        @dblclick="if (!$event.target.closest('button')) Livewire.navigate('{{ route('books.detail', $book->id) }}')"
                        title="Klik 2x untuk membuka detail buku"
                        class="bg-white rounded-xl border border-[#E5E0D8] hover:border-[#114B43]/60 transition-colors duration-200 flex flex-col overflow-hidden group cursor-pointer"
                    >
                        <!-- Book Cover Frame -->
                        <div class="relative p-4 pb-3 bg-[#FAF8F4] border-b border-[#EFECE4] flex items-center justify-center">
                            <a
                                href="{{ route('books.detail', $book->id) }}"
                                wire:navigate
                                class="block w-full max-w-[168px] aspect-[3/4] rounded-md overflow-hidden border border-[#D6D0C6]/80 shadow-sm relative focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43]"
                                aria-label="Buka detail {{ $book->title }}"
                            >
                                @if ($book->cover_image)
                                    <img
                                        src="{{ asset('storage/' . $book->cover_image) }}"
                                        alt="Sampul {{ $book->title }}"
                                        loading="lazy"
                                        class="w-full h-full object-cover group-hover:scale-[1.03] motion-reduce:transform-none transition-transform duration-300"
                                    >
                                @else
                                    <!-- Cloth-Bound Editorial Cover Fallback -->
                                    <div class="w-full h-full {{ $theme['bg'] }} {{ $theme['title'] }} p-4 flex flex-col justify-between border-l-[6px] border-black/25 relative">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-mono text-[9px] uppercase tracking-wider px-1.5 py-0.5 rounded {{ $theme['pill'] }}">
                                                {{ $book->category?->rack_code ?? 'UMUM' }}
                                            </span>
                                            <span class="h-1 w-6 rounded-full {{ $theme['accent'] }}" aria-hidden="true"></span>
                                        </div>

                                        <div class="space-y-1.5 my-auto py-2">
                                            <h3 class="font-serif text-base font-semibold leading-snug line-clamp-3">
                                                {{ $book->title }}
                                            </h3>
                                        </div>

                                        <div class="pt-2 border-t border-white/15">
                                            <p class="text-[11px] {{ $theme['subtitle'] }} truncate font-medium">
                                                {{ $book->author }}
                                            </p>
                                        </div>
                                    </div>
                                @endif
                            </a>

                            <!-- Quick Bookmark Ribbon Toggle (for logged-in users) -->
                            @auth
                                <button
                                    type="button"
                                    wire:click="toggleBookmark({{ $book->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleBookmark({{ $book->id }})"
                                    aria-label="{{ $isBookmarked ? 'Hapus dari favorit: ' : 'Simpan ke favorit: ' }}{{ $book->title }}"
                                    aria-pressed="{{ $isBookmarked ? 'true' : 'false' }}"
                                    @class([
                                        'absolute top-3 right-3 w-9 h-9 rounded-lg border flex items-center justify-center transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43]',
                                        'bg-[#FEF3C7] text-[#B45309] border-[#FDE68A]' => $isBookmarked,
                                        'bg-white/90 text-[#5E5850] border-[#E5E0D8] hover:text-[#114B43] hover:border-[#114B43]' => ! $isBookmarked,
                                    ])
                                >
                                    <svg class="w-4 h-4" width="16" height="16" fill="{{ $isBookmarked ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                    </svg>
                                </button>
                            @endauth
                        </div>

                        <!-- Book Body -->
                        <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between gap-4">
                            <div class="space-y-2">
                                <!-- Category + Rack Code + Stock Status -->
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-semibold text-[#114B43] truncate">
                                        {{ $book->category->name ?? 'Umum' }}
                                    </span>

                                    @if ($book->stock > 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded font-mono text-[11px] font-semibold bg-[#F0FDF4] text-[#166534] border border-[#BBF7D0] tabular-nums shrink-0">
                                            Tersedia: {{ $book->stock }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded font-mono text-[11px] font-semibold bg-[#FEF2F2] text-[#991B1B] border border-[#FECACA] shrink-0">
                                            Stok Habis
                                        </span>
                                    @endif
                                </div>

                                <!-- Title -->
                                <h3 class="font-serif text-lg font-semibold text-[#181615] leading-snug line-clamp-2 [overflow-wrap:anywhere] group-hover:text-[#114B43] transition-colors" title="{{ $book->title }}">
                                    <a
                                        href="{{ route('books.detail', $book->id) }}"
                                        wire:navigate
                                        @dblclick.prevent="Livewire.navigate('{{ route('books.detail', $book->id) }}')"
                                        class="focus-visible:outline-none focus-visible:underline"
                                    >
                                        {{ $book->title }}
                                    </a>
                                </h3>

                                <!-- Author + Rack -->
                                <div class="flex items-center justify-between gap-2 text-xs text-[#5E5850]">
                                    <span class="truncate">{{ $book->author }}</span>
                                    <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-[#F7F5F0] text-[#5E5850] border border-[#E5E0D8] shrink-0">
                                        {{ $book->category->rack_code ?? 'RAK-00' }}
                                    </span>
                                </div>

                                @if ($book->synopsis)
                                    <p class="text-xs text-[#5E5850] leading-relaxed line-clamp-2 [overflow-wrap:anywhere] pt-0.5">
                                        {{ $book->synopsis }}
                                    </p>
                                @endif
                            </div>

                            <!-- Action Bar -->
                            <div class="pt-3 border-t border-[#EFECE4] flex items-center justify-between gap-2">
                                <a
                                    href="{{ route('books.detail', $book->id) }}"
                                    wire:navigate
                                    class="min-h-[38px] w-full inline-flex items-center justify-between px-3.5 py-2 rounded-lg text-xs font-semibold bg-[#F7F5F0] text-[#181615] hover:bg-[#114B43] hover:text-[#FAF8F4] border border-[#E5E0D8] hover:border-[#114B43] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43]"
                                >
                                    <span>Lihat Detail</span>
                                    <span aria-hidden="true">&rarr;</span>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="pt-4">
                {{ $books->links() }}
            </div>
        @endif
    </div>
</div>
