<?php

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard Admin - E-Library Sekolah')] class extends Component
{
    public string $circulationFilter = 'all';

    public string $search = '';

    public function mount(): void
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    public function setCirculationFilter(string $filter): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
        $allowed = ['all', 'overdue', 'active', 'returned'];
        $this->circulationFilter = in_array($filter, $allowed, true) ? $filter : 'all';
    }

    public function resetLedgerFilters(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
        $this->reset(['circulationFilter', 'search']);
    }

    public function confirmReturn(int $borrowingId): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);

        $flashMessage = null;

        DB::transaction(function () use ($borrowingId, &$flashMessage): void {
            $borrowing = Borrowing::with('book')
                ->lockForUpdate()
                ->findOrFail($borrowingId);

            if ($borrowing->status !== 'dipinjam') {
                return;
            }

            $today = now()->startOfDay();
            $dueDate = $borrowing->due_date->copy()->startOfDay();
            $daysLate = $today->greaterThan($dueDate) ? (int) $dueDate->diffInDays($today) : 0;
            $finalFine = max((int) $borrowing->fine_amount, $daysLate * 2000);

            $borrowing->update([
                'status' => 'dikembalikan',
                'return_date' => now()->toDateString(),
                'fine_amount' => $finalFine,
            ]);

            $borrowing->book?->increment('stock');

            $title = $borrowing->book?->title ?? 'Buku';
            $flashMessage = "Pengembalian '{$title}' ({$borrowing->borrow_code}) dikonfirmasi.";

            if ($finalFine > 0) {
                $flashMessage .= ' Denda keterlambatan: Rp '.number_format($finalFine, 0, ',', '.').'.';
            }
        });

        if ($flashMessage !== null) {
            session()->flash('dashboard_notice', $flashMessage);
        }

        unset(
            $this->totalStock,
            $this->borrowedBooksCount,
            $this->returnedBooksCount,
            $this->outOfStockCount,
            $this->activeCirculationSnapshot,
            $this->overdueCount,
            $this->onTimeBorrowingsCount,
            $this->runningOverdueFineAmount,
            $this->collectedFineAmount,
            $this->totalFineAmount,
            $this->recentBorrowings,
            $this->circulationRows,
            $this->dailyActivity,
            $this->categoryBreakdown,
            $this->lowStockBooks,
            $this->popularBooks,
        );
    }

    /**
     * Evaluate live overdue status and fine for a borrowing record.
     *
     * @return array{is_overdue: bool, days_late: int, days_remaining: int, fine: int}
     */
    public function evaluateBorrowing(Borrowing $borrowing): array
    {
        $today = now()->startOfDay();
        $dueDate = $borrowing->due_date->copy()->startOfDay();

        if ($borrowing->status === 'dipinjam') {
            if ($today->greaterThan($dueDate)) {
                $daysLate = (int) $dueDate->diffInDays($today);

                return [
                    'is_overdue' => true,
                    'days_late' => $daysLate,
                    'days_remaining' => 0,
                    'fine' => max((int) $borrowing->fine_amount, $daysLate * 2000),
                ];
            }

            return [
                'is_overdue' => false,
                'days_late' => 0,
                'days_remaining' => (int) $today->diffInDays($dueDate),
                'fine' => (int) $borrowing->fine_amount,
            ];
        }

        return [
            'is_overdue' => false,
            'days_late' => 0,
            'days_remaining' => 0,
            'fine' => (int) ($borrowing->fine_amount ?? 0),
        ];
    }

    /**
     * @return array{titles: int, stock: int, out_of_stock: int}
     */
    #[Computed]
    public function bookAggregateSnapshot(): array
    {
        $row = Book::query()
            ->selectRaw('COUNT(*) as titles, COALESCE(SUM(stock), 0) as stock, SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as out_of_stock')
            ->first();

        return [
            'titles' => (int) ($row?->titles ?? 0),
            'stock' => (int) ($row?->stock ?? 0),
            'out_of_stock' => (int) ($row?->out_of_stock ?? 0),
        ];
    }

    /**
     * @return array{borrowed: int, returned: int}
     */
    #[Computed]
    public function borrowingAggregateSnapshot(): array
    {
        $row = Borrowing::query()
            ->selectRaw("SUM(CASE WHEN status = 'dipinjam' THEN 1 ELSE 0 END) as borrowed, SUM(CASE WHEN status = 'dikembalikan' THEN 1 ELSE 0 END) as returned")
            ->first();

        return [
            'borrowed' => (int) ($row?->borrowed ?? 0),
            'returned' => (int) ($row?->returned ?? 0),
        ];
    }

    #[Computed]
    public function totalBookTitles(): int
    {
        return $this->bookAggregateSnapshot['titles'];
    }

    #[Computed]
    public function totalStock(): int
    {
        return $this->bookAggregateSnapshot['stock'];
    }

    #[Computed]
    public function borrowedBooksCount(): int
    {
        return $this->borrowingAggregateSnapshot['borrowed'];
    }

    #[Computed]
    public function returnedBooksCount(): int
    {
        return $this->borrowingAggregateSnapshot['returned'];
    }

    #[Computed]
    public function totalCategoriesCount(): int
    {
        return Category::count();
    }

    #[Computed]
    public function totalStudentsCount(): int
    {
        return User::where('role', 'siswa')->count();
    }

    #[Computed]
    public function outOfStockCount(): int
    {
        return $this->bookAggregateSnapshot['out_of_stock'];
    }

    /**
     * Snapshot active circulation counts and live fines in a single query.
     *
     * @return array{overdue_count: int, on_time_count: int, running_fine: int}
     */
    #[Computed]
    public function activeCirculationSnapshot(): array
    {
        $today = now()->startOfDay();
        $overdueCount = 0;
        $onTimeCount = 0;
        $runningFine = 0;

        $activeLoans = Borrowing::where('status', 'dipinjam')
            ->get(['id', 'due_date', 'fine_amount']);

        foreach ($activeLoans as $loan) {
            $dueDate = $loan->due_date->copy()->startOfDay();

            if ($today->greaterThan($dueDate)) {
                $overdueCount++;
                $daysLate = (int) $dueDate->diffInDays($today);
                $runningFine += max((int) $loan->fine_amount, $daysLate * 2000);
            } else {
                $onTimeCount++;
                $runningFine += (int) $loan->fine_amount;
            }
        }

        return [
            'overdue_count' => $overdueCount,
            'on_time_count' => $onTimeCount,
            'running_fine' => $runningFine,
        ];
    }

    #[Computed]
    public function overdueCount(): int
    {
        return $this->activeCirculationSnapshot['overdue_count'];
    }

    #[Computed]
    public function onTimeBorrowingsCount(): int
    {
        return $this->activeCirculationSnapshot['on_time_count'];
    }

    #[Computed]
    public function runningOverdueFineAmount(): int
    {
        return $this->activeCirculationSnapshot['running_fine'];
    }

    #[Computed]
    public function collectedFineAmount(): int
    {
        return (int) Borrowing::where('status', 'dikembalikan')->sum('fine_amount');
    }

    #[Computed]
    public function totalFineAmount(): int
    {
        return $this->collectedFineAmount + $this->runningOverdueFineAmount;
    }

    /**
     * @return Collection<int, Borrowing>
     */
    #[Computed]
    public function recentBorrowings(): Collection
    {
        return Borrowing::with(['user', 'book.category'])
            ->latest()
            ->take(5)
            ->get();
    }

    /**
     * @return Collection<int, Borrowing>
     */
    #[Computed]
    public function circulationRows(): Collection
    {
        $today = now()->toDateString();
        $term = trim($this->search);

        return Borrowing::with(['user', 'book.category'])
            ->when($this->circulationFilter === 'overdue', function ($query) use ($today): void {
                $query->where('status', 'dipinjam')
                    ->whereDate('due_date', '<', $today)
                    ->orderBy('due_date');
            })
            ->when($this->circulationFilter === 'active', function ($query): void {
                $query->where('status', 'dipinjam')
                    ->orderBy('due_date');
            })
            ->when($this->circulationFilter === 'returned', function ($query): void {
                $query->where('status', 'dikembalikan')
                    ->latest('return_date')
                    ->latest();
            })
            ->when($this->circulationFilter === 'all', function ($query): void {
                $query->latest();
            })
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($subQuery) use ($term): void {
                    $subQuery->where('borrow_code', 'like', "%{$term}%")
                        ->orWhereHas('user', function ($userQuery) use ($term): void {
                            $userQuery->where('name', 'like', "%{$term}%")
                                ->orWhere('nis', 'like', "%{$term}%");
                        })
                        ->orWhereHas('book', function ($bookQuery) use ($term): void {
                            $bookQuery->where('title', 'like', "%{$term}%");
                        });
                });
            })
            ->take(8)
            ->get();
    }

    /**
     * 14-day borrowing vs return activity timeline (SQLite & MySQL compatible).
     *
     * @return array{
     *     days: array<int, array{date: string, day_num: string, label: string, borrowed: int, returned: int, is_today: bool}>,
     *     max_count: int,
     *     total_borrowed: int,
     *     total_returned: int
     * }
     */
    #[Computed]
    public function dailyActivity(): array
    {
        $startDate = now()->subDays(13)->startOfDay();
        $startString = $startDate->toDateString();

        $borrowCounts = Borrowing::whereDate('borrow_date', '>=', $startString)
            ->get(['borrow_date'])
            ->groupBy(fn (Borrowing $item): string => $item->borrow_date->toDateString())
            ->map(fn ($group): int => $group->count());

        $returnCounts = Borrowing::whereNotNull('return_date')
            ->whereDate('return_date', '>=', $startString)
            ->get(['return_date'])
            ->groupBy(fn (Borrowing $item): string => $item->return_date->toDateString())
            ->map(fn ($group): int => $group->count());

        $days = [];
        $maxCount = 1;
        $totalBorrowed = 0;
        $totalReturned = 0;
        $todayString = now()->toDateString();

        for ($offset = 0; $offset < 14; $offset++) {
            $cursor = $startDate->copy()->addDays($offset);
            $dateKey = $cursor->toDateString();
            $borrowed = (int) ($borrowCounts->get($dateKey) ?? 0);
            $returned = (int) ($returnCounts->get($dateKey) ?? 0);

            $maxCount = max($maxCount, $borrowed, $returned);
            $totalBorrowed += $borrowed;
            $totalReturned += $returned;

            $days[] = [
                'date' => $dateKey,
                'day_num' => $cursor->format('d'),
                'label' => $cursor->copy()->locale('id')->translatedFormat('d M Y'),
                'borrowed' => $borrowed,
                'returned' => $returned,
                'is_today' => $dateKey === $todayString,
            ];
        }

        return [
            'days' => $days,
            'max_count' => $maxCount,
            'total_borrowed' => $totalBorrowed,
            'total_returned' => $totalReturned,
        ];
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categoryBreakdown(): Collection
    {
        return Category::withCount('books')
            ->withSum('books', 'stock')
            ->orderByDesc('books_sum_stock')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Book>
     */
    #[Computed]
    public function lowStockBooks(): Collection
    {
        return Book::with('category')
            ->where('stock', '<=', 2)
            ->orderBy('stock')
            ->orderBy('title')
            ->take(5)
            ->get();
    }

    /**
     * @return Collection<int, Book>
     */
    #[Computed]
    public function popularBooks(): Collection
    {
        return Book::with('category')
            ->has('borrowings')
            ->withCount(['borrowings', 'bookmarks'])
            ->orderByDesc('borrowings_count')
            ->orderByDesc('bookmarks_count')
            ->take(5)
            ->get();
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 space-y-8">
    @if (session()->has('dashboard_notice'))
        <div
            x-data="{ visible: true }"
            x-show="visible"
            role="status"
            aria-live="polite"
            class="flex items-center justify-between gap-4 px-4 py-3.5 rounded-xl bg-[#F0FDF4] border border-[#BBF7D0] text-[#166534] text-sm"
        >
            <div class="flex items-center gap-3 min-w-0">
                <svg class="w-5 h-5 shrink-0 text-[#15803D]" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium">{{ session('dashboard_notice') }}</span>
            </div>
            <button
                type="button"
                @click="visible = false"
                aria-label="Tutup notifikasi"
                class="p-1.5 rounded-lg text-[#166534]/70 hover:text-[#166534] hover:bg-[#DCFCE7] transition-colors cursor-pointer shrink-0"
            >
                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- Editorial Masthead -->
    <header class="border-b border-[#E5E0D8] pb-6 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
        <div class="space-y-2 max-w-2xl">
            <div class="flex flex-wrap items-center gap-2.5 text-[11px] font-mono uppercase tracking-[0.14em] text-[#5E5850]">
                <span>Meja Pustakawan</span>
                <span aria-hidden="true" class="text-[#C8C0B4]">/</span>
                <span>{{ now()->copy()->locale('id')->translatedFormat('l, d F Y') }}</span>
            </div>

            <h1 class="font-serif text-3xl sm:text-4xl font-semibold tracking-tight text-[#181615] leading-tight">
                Ringkasan Sirkulasi &amp; Koleksi
            </h1>

            <p class="text-sm sm:text-[15px] text-[#5E5850] leading-relaxed">
                @if ($this->overdueCount > 0)
                    Terdapat <span class="font-semibold text-[#991B1B] tabular-nums">{{ $this->overdueCount }} pinjaman melewati jatuh tempo</span>
                    dengan estimasi denda berjalan <span class="font-mono font-medium text-[#181615] tabular-nums">Rp {{ number_format($this->runningOverdueFineAmount, 0, ',', '.') }}</span> yang memerlukan konfirmasi pengembalian.
                @elseif ($this->borrowedBooksCount > 0)
                    Sirkulasi berjalan normal dengan <span class="font-semibold text-[#181615] tabular-nums">{{ $this->borrowedBooksCount }} buku</span> sedang dipinjam dan seluruhnya masih dalam masa tenggat.
                @else
                    Seluruh koleksi siap dipinjam. Belum ada sirkulasi aktif yang menunggu pengembalian hari ini.
                @endif
            </p>
        </div>

        <!-- Desk Quick Actions: 1 clear primary CTA, subordinate secondary links -->
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <a
                href="/admin/categories"
                wire:navigate
                class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold text-[#181615] bg-white border border-[#D6D0C6] hover:border-[#114B43] hover:text-[#114B43] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43]"
            >
                <svg class="w-4 h-4 text-[#5E5850]" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span>Kelola Rak</span>
            </a>

            <a
                href="/admin/borrowings"
                wire:navigate
                class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold text-[#181615] bg-white border border-[#D6D0C6] hover:border-[#114B43] hover:text-[#114B43] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43]"
            >
                <svg class="w-4 h-4 text-[#5E5850]" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span>Data Peminjaman</span>
            </a>

            <a
                href="/admin/books"
                wire:navigate
                class="min-h-[44px] inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold text-[#FAF8F4] bg-[#114B43] hover:bg-[#0C3832] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43] focus-visible:ring-offset-2 focus-visible:ring-offset-[#F7F5F0]"
            >
                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4" />
                </svg>
                <span>Kelola Buku</span>
            </a>
        </div>
    </header>

    <!-- Asymmetric Focal Band: Circulation Anchor (5 cols) + Ledger Strip & 14-Day Pulse (7 cols) -->
    <section aria-label="Ikhtisar Sirkulasi dan Inventaris" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        <!-- Left 5 Cols: Deep Botanical Focal Panel -->
        <div class="lg:col-span-5 bg-[#114B43] text-[#FAF8F4] rounded-xl p-6 sm:p-7 flex flex-col justify-between border border-[#0C3832]">
            <div>
                <div class="flex items-center justify-between gap-2">
                    <span class="font-mono text-[11px] uppercase tracking-[0.14em] text-[#B7D4CE]">
                        Sirkulasi Aktif
                    </span>

                    @if ($this->overdueCount > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-[#FEE2E2] text-[#991B1B] font-mono text-[11px] font-semibold tabular-nums">
                            <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                            {{ $this->overdueCount }} Terlambat
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-white/10 text-[#D7EDE8] font-mono text-[11px] font-medium">
                            Terkendali
                        </span>
                    @endif
                </div>

                <!-- Hero Number -->
                <div class="mt-5 flex items-baseline gap-3">
                    <span class="font-serif text-5xl sm:text-6xl font-medium tracking-tight tabular-nums leading-none">
                        {{ number_format($this->borrowedBooksCount) }}
                    </span>
                    <span class="text-sm text-[#CBE2DC] leading-snug">
                        eksemplar sedang dipinjam siswa
                    </span>
                </div>

                <!-- Segmented Proportion Bar (Accessible: always paired with exact labeled counts) -->
                @php
                    $totalTransactions = max(1, $this->onTimeBorrowingsCount + $this->overdueCount + $this->returnedBooksCount);
                    $onTimePct = round(($this->onTimeBorrowingsCount / $totalTransactions) * 100, 1);
                    $overduePct = round(($this->overdueCount / $totalTransactions) * 100, 1);
                    $returnedPct = max(0, round(100 - $onTimePct - $overduePct, 1));
                    $hasAnyTransactions = ($this->onTimeBorrowingsCount + $this->overdueCount + $this->returnedBooksCount) > 0;
                @endphp

                <div class="mt-6 space-y-2.5">
                    <div class="h-2.5 w-full rounded-full bg-[#0A312B] overflow-hidden flex" role="img" aria-label="Proporsi transaksi: {{ $this->onTimeBorrowingsCount }} dalam tenggat, {{ $this->overdueCount }} terlambat, {{ $this->returnedBooksCount }} dikembalikan">
                        @if ($hasAnyTransactions)
                            @if ($this->onTimeBorrowingsCount > 0)
                                <div class="h-full bg-[#5EEAD4]" style="width: {{ $onTimePct }}%"></div>
                            @endif
                            @if ($this->overdueCount > 0)
                                <div class="h-full bg-[#F87171]" style="width: {{ $overduePct }}%"></div>
                            @endif
                            @if ($this->returnedBooksCount > 0)
                                <div class="h-full bg-[#2E7D72]" style="width: {{ $returnedPct }}%"></div>
                            @endif
                        @endif
                    </div>

                    <div class="grid grid-cols-3 gap-2 pt-1 text-xs">
                        <div>
                            <div class="flex items-center gap-1.5 text-[#CBE2DC]">
                                <span class="w-2 h-2 rounded-sm bg-[#5EEAD4] inline-block shrink-0" aria-hidden="true"></span>
                                <span>Dalam Tenggat</span>
                            </div>
                            <div class="font-mono font-semibold text-sm tabular-nums mt-0.5">{{ $this->onTimeBorrowingsCount }}</div>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 text-[#CBE2DC]">
                                <span class="w-2 h-2 rounded-sm bg-[#F87171] inline-block shrink-0" aria-hidden="true"></span>
                                <span>Terlambat</span>
                            </div>
                            <div class="font-mono font-semibold text-sm tabular-nums mt-0.5">{{ $this->overdueCount }}</div>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 text-[#CBE2DC]">
                                <span class="w-2 h-2 rounded-sm bg-[#2E7D72] border border-white/20 inline-block shrink-0" aria-hidden="true"></span>
                                <span>Selesai</span>
                            </div>
                            <div class="font-mono font-semibold text-sm tabular-nums mt-0.5">{{ $this->returnedBooksCount }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fine & Direct Filter Action Footer -->
            <div class="mt-6 pt-5 border-t border-white/15 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="font-mono text-[10px] uppercase tracking-[0.12em] text-[#A7C4BE]">Denda Berjalan</div>
                        <div class="font-mono text-base font-semibold text-[#FDE68A] tabular-nums mt-0.5">
                            Rp {{ number_format($this->runningOverdueFineAmount, 0, ',', '.') }}
                        </div>
                    </div>
                    <div>
                        <div class="font-mono text-[10px] uppercase tracking-[0.12em] text-[#A7C4BE]">Denda Lunas</div>
                        <div class="font-mono text-base font-semibold text-[#FAF8F4] tabular-nums mt-0.5">
                            Rp {{ number_format($this->collectedFineAmount, 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                @if ($this->overdueCount > 0)
                    <button
                        type="button"
                        wire:click="setCirculationFilter('overdue')"
                        class="min-h-[40px] inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold bg-[#FAF8F4] text-[#114B43] hover:bg-[#EFECE4] transition-colors cursor-pointer shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                    >
                        <span>Filter Terlambat</span>
                        <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        <!-- Right 7 Cols: Ledger Metric Strip + 14-Day Circulation Pulse -->
        <div class="lg:col-span-7 bg-white rounded-xl border border-[#E5E0D8] flex flex-col justify-between">
            <!-- Hairline-Divided 4-Cell Editorial Index Strip -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-px bg-[#E5E0D8] border-b border-[#E5E0D8] rounded-t-xl overflow-hidden">
                <a href="/admin/books" wire:navigate class="bg-white p-4 sm:p-5 hover:bg-[#FAF8F4] transition-colors group">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono text-[10px] uppercase tracking-[0.12em] text-[#5E5850]">Judul Katalog</span>
                        <span class="text-[#5E5850] group-hover:text-[#114B43] group-hover:translate-x-0.5 motion-reduce:transform-none transition-all text-xs" aria-hidden="true">&rarr;</span>
                    </div>
                    <div class="font-serif text-3xl font-semibold text-[#181615] tabular-nums mt-2 leading-none">
                        {{ number_format($this->totalBookTitles) }}
                    </div>
                    <div class="text-xs text-[#5E5850] mt-2">
                        Di <span class="font-medium text-[#181615] tabular-nums">{{ $this->totalCategoriesCount }}</span> rak kategori
                    </div>
                </a>

                <a href="/admin/books" wire:navigate class="bg-white p-4 sm:p-5 hover:bg-[#FAF8F4] transition-colors group">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono text-[10px] uppercase tracking-[0.12em] text-[#5E5850]">Eksemplar Fisik</span>
                        <span class="text-[#5E5850] group-hover:text-[#114B43] group-hover:translate-x-0.5 motion-reduce:transform-none transition-all text-xs" aria-hidden="true">&rarr;</span>
                    </div>
                    <div class="font-serif text-3xl font-semibold text-[#181615] tabular-nums mt-2 leading-none">
                        {{ number_format($this->totalStock) }}
                    </div>
                    <div class="text-xs mt-2 {{ $this->outOfStockCount > 0 ? 'text-[#991B1B] font-medium' : 'text-[#166534]' }}">
                        {{ $this->outOfStockCount > 0 ? "{$this->outOfStockCount} judul stok habis" : 'Seluruh judul tersedia' }}
                    </div>
                </a>

                <div class="bg-white p-4 sm:p-5">
                    <div class="font-mono text-[10px] uppercase tracking-[0.12em] text-[#5E5850]">Anggota Siswa</div>
                    <div class="font-serif text-3xl font-semibold text-[#181615] tabular-nums mt-2 leading-none">
                        {{ number_format($this->totalStudentsCount) }}
                    </div>
                    <div class="text-xs text-[#5E5850] mt-2">
                        Akun siswa terdaftar
                    </div>
                </div>

                <a href="/admin/borrowings" wire:navigate class="bg-white p-4 sm:p-5 hover:bg-[#FAF8F4] transition-colors group">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono text-[10px] uppercase tracking-[0.12em] text-[#5E5850]">Akumulasi Denda</span>
                        <span class="text-[#5E5850] group-hover:text-[#114B43] group-hover:translate-x-0.5 motion-reduce:transform-none transition-all text-xs" aria-hidden="true">&rarr;</span>
                    </div>
                    <div class="font-mono text-lg sm:text-2xl font-semibold text-[#181615] tabular-nums mt-2.5 leading-none break-words">
                        Rp {{ number_format($this->totalFineAmount, 0, ',', '.') }}
                    </div>
                    <div class="text-xs text-[#5E5850] mt-2">
                        Lunas &amp; denda berjalan
                    </div>
                </a>
            </div>

            <!-- 14-Day Circulation Activity Chart (Fits all screen sizes without horizontal scroll) -->
            @php
                $activity = $this->dailyActivity;
            @endphp
            <div class="p-4 sm:p-6 flex-1 flex flex-col justify-between" x-data="{ hoveredDate: null }">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-[#181615]">Denyut Sirkulasi 14 Hari Terakhir</h2>
                        <p class="text-xs text-[#5E5850] mt-0.5 tabular-nums">
                            Total periode: <span class="font-medium text-[#181615]">{{ $activity['total_borrowed'] }} pinjam</span> &middot; <span class="font-medium text-[#181615]">{{ $activity['total_returned'] }} kembali</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-[#5E5850]">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-sm bg-[#114B43]" aria-hidden="true"></span>
                            Peminjaman
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-sm bg-[#B45309]" aria-hidden="true"></span>
                            Pengembalian
                        </span>
                    </div>
                </div>

                <!-- 14-Day Bar Plot with Fixed-Height Bottom Baseline -->
                <div class="w-full grid gap-[2px] sm:gap-1.5 items-end pt-4" style="grid-template-columns: repeat(14, minmax(0, 1fr));">
                    @foreach ($activity['days'] as $day)
                        @php
                            $borrowPct = $day['borrowed'] > 0 ? max(16, (int) round(($day['borrowed'] / $activity['max_count']) * 100)) : 4;
                            $returnPct = $day['returned'] > 0 ? max(16, (int) round(($day['returned'] / $activity['max_count']) * 100)) : 4;
                            $tooltipPosition = $loop->index <= 2
                                ? 'left-0'
                                : ($loop->index >= 11 ? 'right-0' : 'left-1/2 -translate-x-1/2');
                        @endphp
                        <div
                            wire:key="activity-{{ $day['date'] }}"
                            class="relative flex flex-col items-center justify-end h-36 group"
                            @mouseenter="hoveredDate = '{{ $day['date'] }}'"
                            @mouseleave="hoveredDate = null"
                        >
                            <!-- Tooltip -->
                            <div
                                x-show="hoveredDate === '{{ $day['date'] }}'"
                                x-cloak
                                class="absolute -top-7 {{ $tooltipPosition }} z-20 whitespace-nowrap rounded-md bg-[#181615] text-[#FAF8F4] px-2 py-1 text-[10px] font-mono pointer-events-none shadow-sm"
                            >
                                {{ $day['label'] }}: {{ $day['borrowed'] }}P / {{ $day['returned'] }}K
                            </div>

                            <button
                                type="button"
                                @focus="hoveredDate = '{{ $day['date'] }}'"
                                @blur="hoveredDate = null"
                                aria-label="{{ $day['label'] }}: {{ $day['borrowed'] }} peminjaman, {{ $day['returned'] }} pengembalian"
                                class="h-28 w-full flex items-end justify-center gap-[1px] sm:gap-1 border-b border-stone-200 pb-2 px-0 sm:px-0.5 rounded-t hover:bg-[#FAF8F5] focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-[#144533]"
                            >
                                <span
                                    @class([
                                        'flex-1 max-w-[6px] sm:max-w-[8px] rounded-t-[2px] transition-all duration-200',
                                        'bg-[#114B43]' => $day['borrowed'] > 0,
                                        'bg-[#EAE6DF]' => $day['borrowed'] === 0,
                                    ])
                                    style="height: {{ $borrowPct }}%"
                                ></span>
                                <span
                                    @class([
                                        'flex-1 max-w-[6px] sm:max-w-[8px] rounded-t-[2px] transition-all duration-200',
                                        'bg-[#B45309]' => $day['returned'] > 0,
                                        'bg-[#EAE6DF]' => $day['returned'] === 0,
                                    ])
                                    style="height: {{ $returnPct }}%"
                                ></span>
                            </button>

                            <span
                                @class([
                                    'pt-1.5 text-center font-mono text-[9px] sm:text-[10px] leading-none tabular-nums',
                                    'text-[#114B43] font-bold' => $day['is_today'],
                                    'text-[#5E5850]' => ! $day['is_today'],
                                ])
                            >
                                {{ $day['day_num'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- Main Operational Split: Loan Ledger (8 Cols) + Shelf & Stock Watchlist (4 Cols) -->
    <section aria-label="Aktivitas Sirkulasi dan Kapasitas Rak" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left 8 Cols: Interactive Circulation Ledger -->
        <div class="lg:col-span-8 bg-white rounded-xl border border-[#E5E0D8] overflow-hidden">
            <!-- Header & Filter Bar -->
            <div class="p-5 sm:p-6 border-b border-[#E5E0D8] space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="font-serif text-xl font-semibold text-[#181615]">Buku Catatan Sirkulasi</h2>
                        <p class="text-xs text-[#5E5850] mt-0.5">Pantau tenggat pengembalian dan konfirmasi pengembalian buku langsung dari meja kerja.</p>
                    </div>
                    <a href="/admin/borrowings" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#114B43] hover:text-[#0C3832] transition-colors shrink-0">
                        <span>Kelola Semua</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>

                <!-- Segmented Tabs + Search Input -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-1.5 bg-[#F7F5F0] p-1 rounded-lg border border-[#E5E0D8]" role="tablist" aria-label="Filter status peminjaman">
                        <button
                            type="button"
                            wire:click="setCirculationFilter('all')"
                            role="tab"
                            aria-selected="{{ $circulationFilter === 'all' ? 'true' : 'false' }}"
                            @class([
                                'px-3 py-1.5 rounded-md text-xs font-semibold transition-colors cursor-pointer',
                                'bg-white text-[#181615] shadow-sm border border-[#E5E0D8]' => $circulationFilter === 'all',
                                'text-[#5E5850] hover:text-[#181615]' => $circulationFilter !== 'all',
                            ])
                        >
                            Terbaru
                        </button>

                        <button
                            type="button"
                            wire:click="setCirculationFilter('overdue')"
                            role="tab"
                            aria-selected="{{ $circulationFilter === 'overdue' ? 'true' : 'false' }}"
                            @class([
                                'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors cursor-pointer',
                                'bg-white text-[#991B1B] shadow-sm border border-[#FECACA]' => $circulationFilter === 'overdue',
                                'text-[#5E5850] hover:text-[#991B1B]' => $circulationFilter !== 'overdue',
                            ])
                        >
                            <span>Terlambat</span>
                            @if ($this->overdueCount > 0)
                                <span class="px-1.5 py-0.5 rounded bg-[#FEE2E2] text-[#991B1B] font-mono text-[10px] leading-none tabular-nums">
                                    {{ $this->overdueCount }}
                                </span>
                            @endif
                        </button>

                        <button
                            type="button"
                            wire:click="setCirculationFilter('active')"
                            role="tab"
                            aria-selected="{{ $circulationFilter === 'active' ? 'true' : 'false' }}"
                            @class([
                                'px-3 py-1.5 rounded-md text-xs font-semibold transition-colors cursor-pointer',
                                'bg-white text-[#181615] shadow-sm border border-[#E5E0D8]' => $circulationFilter === 'active',
                                'text-[#5E5850] hover:text-[#181615]' => $circulationFilter !== 'active',
                            ])
                        >
                            Dipinjam
                        </button>

                        <button
                            type="button"
                            wire:click="setCirculationFilter('returned')"
                            role="tab"
                            aria-selected="{{ $circulationFilter === 'returned' ? 'true' : 'false' }}"
                            @class([
                                'px-3 py-1.5 rounded-md text-xs font-semibold transition-colors cursor-pointer',
                                'bg-white text-[#181615] shadow-sm border border-[#E5E0D8]' => $circulationFilter === 'returned',
                                'text-[#5E5850] hover:text-[#181615]' => $circulationFilter !== 'returned',
                            ])
                        >
                            Dikembalikan
                        </button>
                    </div>

                    <div class="relative w-full md:w-64">
                        <label for="dashboard-ledger-search" class="sr-only">Cari peminjaman</label>
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#5E5850]">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            id="dashboard-ledger-search"
                            wire:model.live.debounce.300ms="search"
                            type="search"
                            placeholder="Cari kode PJM, siswa, buku..."
                            class="w-full h-9 pl-9 pr-3 rounded-lg bg-[#FAF8F4] border border-[#D6D0C6] text-xs text-[#181615] placeholder-[#666058] focus:outline-none focus:bg-white focus:border-[#114B43] focus:ring-2 focus:ring-[#114B43]/15 transition-colors"
                        >
                    </div>
                </div>
            </div>

            <!-- Mobile & Tablet Card List (< 1280px) -->
            <div class="xl:hidden divide-y divide-[#EFECE4]" wire:loading.class="opacity-60 transition-opacity">
                @forelse ($this->circulationRows as $borrowing)
                    @php
                        $meta = $this->evaluateBorrowing($borrowing);
                    @endphp
                    <div wire:key="ledger-card-{{ $borrowing->id }}" class="p-4 space-y-3 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <span class="font-mono text-xs font-semibold text-[#114B43] tabular-nums">
                                    {{ $borrowing->borrow_code }}
                                </span>
                                <span class="font-mono text-[11px] text-[#5E5850] tabular-nums ml-2">
                                    {{ $borrowing->borrow_date?->copy()->locale('id')->translatedFormat('d M Y') }}
                                </span>
                            </div>

                            @if ($borrowing->status === 'dikembalikan')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-[#F0FDF4] text-[#166534] border border-[#BBF7D0]">
                                    Dikembalikan
                                </span>
                            @elseif ($meta['is_overdue'])
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-semibold bg-[#FEF2F2] text-[#991B1B] border border-[#FECACA] tabular-nums">
                                    Terlambat {{ $meta['days_late'] }} hr
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-[#FEF3C7] text-[#92400E] border border-[#FDE68A] tabular-nums">
                                    {{ $meta['days_remaining'] === 0 ? 'Jatuh tempo hari ini' : 'Sisa ' . $meta['days_remaining'] . ' hr' }}
                                </span>
                            @endif
                        </div>

                        <div class="min-w-0">
                            <div class="font-medium text-[#181615] text-sm leading-snug [overflow-wrap:anywhere]">
                                {{ $borrowing->book?->title ?? 'Buku Dihapus' }}
                            </div>
                            <div class="text-xs text-[#5E5850] mt-0.5 [overflow-wrap:anywhere]">
                                Siswa: <span class="font-semibold text-[#181615]">{{ $borrowing->user?->name ?? '-' }}</span>
                                @if ($borrowing->user?->class_name)
                                    &middot; {{ $borrowing->user->class_name }}
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                            <div class="font-mono text-xs tabular-nums">
                                @if ($meta['fine'] > 0)
                                    <span class="font-semibold {{ $borrowing->status === 'dipinjam' ? 'text-[#991B1B]' : 'text-[#181615]' }}">
                                        Denda: Rp {{ number_format($meta['fine'], 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-[#5E5850]">
                                        Tempo {{ $borrowing->due_date?->copy()->locale('id')->translatedFormat('d M Y') }}
                                    </span>
                                @endif
                            </div>

                            @if ($borrowing->status === 'dipinjam')
                                <button
                                    type="button"
                                    wire:click="confirmReturn({{ $borrowing->id }})"
                                    wire:confirm="Konfirmasi pengembalian buku '{{ $borrowing->book?->title }}' oleh {{ $borrowing->user?->name }}?"
                                    wire:loading.attr="disabled"
                                    wire:target="confirmReturn({{ $borrowing->id }})"
                                    class="min-h-[36px] inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold bg-[#114B43] text-[#FAF8F4] hover:bg-[#0C3832] disabled:opacity-50 transition-colors cursor-pointer"
                                >
                                    <span>Kembalikan</span>
                                </button>
                            @else
                                <span class="font-mono text-[11px] text-[#5E5850]">Tercatat</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-12 px-6 text-center">
                        <div class="max-w-sm mx-auto space-y-2">
                            <p class="font-serif text-base font-medium text-[#181615]">Tidak ada catatan ditemukan</p>
                            <p class="text-xs text-[#5E5850]">
                                @if ($circulationFilter !== 'all' || $search !== '')
                                    Tidak ada transaksi yang cocok dengan filter aktif saat ini.
                                @else
                                    Belum ada data transaksi peminjaman pada sistem perpustakaan.
                                @endif
                            </p>
                            @if ($circulationFilter !== 'all' || $search !== '')
                                <button
                                    type="button"
                                    wire:click="resetLedgerFilters"
                                    class="mt-2 inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#F7F5F0] text-[#181615] border border-[#D6D0C6] hover:border-[#114B43] cursor-pointer"
                                >
                                    Reset Filter
                                </button>
                            @endif
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Desktop Table (>= 1280px) -->
            <div class="hidden xl:block overflow-x-auto" wire:loading.class="opacity-60 transition-opacity">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#FAF8F4] border-b border-[#E5E0D8] font-mono text-[10px] font-semibold uppercase tracking-[0.1em] text-[#5E5850]">
                            <th class="py-3 px-4">Kode &amp; Pinjam</th>
                            <th class="py-3 px-4">Siswa</th>
                            <th class="py-3 px-4">Koleksi Buku</th>
                            <th class="py-3 px-4">Tenggat &amp; Status</th>
                            <th class="py-3 px-4 text-right">Denda</th>
                            <th class="py-3 px-4 text-right whitespace-nowrap">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EFECE4] text-sm">
                        @forelse ($this->circulationRows as $borrowing)
                            @php
                                $meta = $this->evaluateBorrowing($borrowing);
                            @endphp
                            <tr wire:key="ledger-row-{{ $borrowing->id }}" class="hover:bg-[#FAF8F4]/90 transition-colors">
                                <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                    <div class="font-mono text-xs font-semibold text-[#114B43] tabular-nums">
                                        {{ $borrowing->borrow_code }}
                                    </div>
                                    <div class="font-mono text-[11px] text-[#5E5850] tabular-nums mt-0.5">
                                        {{ $borrowing->borrow_date?->copy()->locale('id')->translatedFormat('d M Y') }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 align-top">
                                    <div class="font-semibold text-[#181615] text-[13px] leading-snug">
                                        {{ $borrowing->user?->name ?? '-' }}
                                    </div>
                                    <div class="font-mono text-[11px] text-[#5E5850] mt-0.5">
                                        {{ $borrowing->user?->nis ? 'NIS ' . $borrowing->user->nis : '' }}
                                        {{ $borrowing->user?->class_name ? ($borrowing->user?->nis ? '• ' : '') . $borrowing->user->class_name : '' }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 align-top min-w-[200px] lg:max-w-[300px]">
                                    <div class="font-medium text-[#181615] text-[13px] leading-snug line-clamp-2" title="{{ $borrowing->book?->title }}">
                                        {{ $borrowing->book?->title ?? 'Buku Dihapus' }}
                                    </div>
                                    @if ($borrowing->book?->category)
                                        <span class="inline-block mt-1 font-mono text-[10px] px-1.5 py-0.5 rounded bg-[#F7F5F0] text-[#5E5850] border border-[#E5E0D8]">
                                            {{ $borrowing->book->category->rack_code }}
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                    @if ($borrowing->status === 'dikembalikan')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-[#F0FDF4] text-[#166534] border border-[#BBF7D0]">
                                            <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Dikembalikan
                                        </span>
                                        <div class="font-mono text-[11px] text-[#5E5850] tabular-nums mt-1">
                                            {{ $borrowing->return_date?->copy()->locale('id')->translatedFormat('d M Y') ?? '-' }}
                                        </div>
                                    @elseif ($meta['is_overdue'])
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-semibold bg-[#FEF2F2] text-[#991B1B] border border-[#FECACA] tabular-nums">
                                            <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Terlambat {{ $meta['days_late'] }} hr
                                        </span>
                                        <div class="font-mono text-[11px] text-[#991B1B] tabular-nums mt-1">
                                            Tempo {{ $borrowing->due_date?->copy()->locale('id')->translatedFormat('d M Y') }}
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium bg-[#FEF3C7] text-[#92400E] border border-[#FDE68A] tabular-nums">
                                            <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ $meta['days_remaining'] === 0 ? 'Jatuh tempo hari ini' : 'Sisa ' . $meta['days_remaining'] . ' hr' }}
                                        </span>
                                        <div class="font-mono text-[11px] text-[#5E5850] tabular-nums mt-1">
                                            Tempo {{ $borrowing->due_date?->copy()->locale('id')->translatedFormat('d M Y') }}
                                        </div>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 align-top text-right whitespace-nowrap font-mono text-xs tabular-nums">
                                    @if ($meta['fine'] > 0)
                                        <span class="font-semibold {{ $borrowing->status === 'dipinjam' ? 'text-[#991B1B]' : 'text-[#181615]' }}">
                                            Rp {{ number_format($meta['fine'], 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-[#5E5850]">Rp 0</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 align-top text-right whitespace-nowrap">
                                    @if ($borrowing->status === 'dipinjam')
                                        <button
                                            type="button"
                                            wire:click="confirmReturn({{ $borrowing->id }})"
                                            wire:confirm="Konfirmasi pengembalian buku '{{ $borrowing->book?->title }}' oleh {{ $borrowing->user?->name }}?"
                                            wire:loading.attr="disabled"
                                            wire:target="confirmReturn({{ $borrowing->id }})"
                                            class="min-h-[36px] inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold bg-[#114B43] text-[#FAF8F4] hover:bg-[#0C3832] disabled:opacity-50 transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43]"
                                        >
                                            <span>Kembalikan</span>
                                        </button>
                                    @else
                                        <span class="font-mono text-[11px] text-[#5E5850]">Tercatat</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 px-6 text-center">
                                    <div class="max-w-sm mx-auto space-y-2">
                                        <p class="font-serif text-base font-medium text-[#181615]">Tidak ada catatan ditemukan</p>
                                        <p class="text-xs text-[#5E5850]">
                                            @if ($circulationFilter !== 'all' || $search !== '')
                                                Tidak ada transaksi yang cocok dengan filter aktif saat ini.
                                            @else
                                                Belum ada data transaksi peminjaman pada sistem perpustakaan.
                                            @endif
                                        </p>
                                        @if ($circulationFilter !== 'all' || $search !== '')
                                            <button
                                                type="button"
                                                wire:click="resetLedgerFilters"
                                                class="mt-2 inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#F7F5F0] text-[#181615] border border-[#D6D0C6] hover:border-[#114B43] cursor-pointer"
                                            >
                                                Reset Filter
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right 4 Cols: Shelf Distribution & Watchlist Panels -->
        <aside class="lg:col-span-4 space-y-6">
            <!-- Panel 1: Shelf Distribution -->
            <div class="bg-white rounded-xl border border-[#E5E0D8] p-5 sm:p-6">
                <div class="flex items-center justify-between gap-2 pb-4 border-b border-[#EFECE4]">
                    <div>
                        <h2 class="font-serif text-lg font-semibold text-[#181615]">Kapasitas Rak Koleksi</h2>
                        <p class="text-xs text-[#5E5850] mt-0.5">Sebaran judul dan stok di setiap rak.</p>
                    </div>
                    <a href="/admin/categories" wire:navigate class="text-xs font-semibold text-[#114B43] hover:text-[#0C3832] shrink-0">
                        Atur Rak &rarr;
                    </a>
                </div>

                @php
                    $totalRackStock = max(1, (int) $this->categoryBreakdown->sum(fn ($c) => (int) ($c->books_sum_stock ?? 0)));
                @endphp

                <div class="mt-4 space-y-4">
                    @forelse ($this->categoryBreakdown as $category)
                        @php
                            $catStock = (int) ($category->books_sum_stock ?? 0);
                            $catPct = (int) round(($catStock / $totalRackStock) * 100);
                        @endphp
                        <div wire:key="rack-breakdown-{{ $category->id }}" class="space-y-1.5">
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="font-semibold text-[#181615] truncate">{{ $category->name }}</span>
                                    <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-[#F7F5F0] text-[#5E5850] border border-[#E5E0D8] shrink-0">
                                        {{ $category->rack_code }}
                                    </span>
                                </div>
                                <div class="font-mono text-[11px] text-[#5E5850] tabular-nums shrink-0">
                                    <span class="font-semibold text-[#181615]">{{ $catStock }}</span> eks ({{ $category->books_count }} jdl)
                                </div>
                            </div>
                            <div class="h-2 w-full rounded-full bg-[#EFECE4] overflow-hidden">
                                <div
                                    class="h-full rounded-full bg-[#114B43]"
                                    style="width: {{ max($catStock > 0 ? 6 : 0, $catPct) }}%"
                                ></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-[#5E5850] py-4 text-center">Belum ada kategori rak terdaftar.</p>
                    @endforelse
                </div>
            </div>

            <!-- Panel 2: Stock Watchlist & Popular Books (Alpine Switcher) -->
            <div class="bg-white rounded-xl border border-[#E5E0D8] p-5 sm:p-6" x-data="{ tab: 'stock' }">
                <div class="flex items-center justify-between gap-2 pb-4 border-b border-[#EFECE4]">
                    <div class="flex items-center gap-1 bg-[#F7F5F0] p-1 rounded-lg border border-[#E5E0D8]" role="tablist" aria-label="Pantauan Koleksi">
                        <button
                            type="button"
                            role="tab"
                            @click="tab = 'stock'"
                            :aria-selected="(tab === 'stock').toString()"
                            :class="tab === 'stock' ? 'bg-white text-[#181615] shadow-sm border border-[#E5E0D8]' : 'text-[#5E5850] hover:text-[#181615]'"
                            class="px-2.5 py-1 rounded-md text-xs font-semibold transition-colors cursor-pointer"
                        >
                            Stok Menipis
                        </button>
                        <button
                            type="button"
                            role="tab"
                            @click="tab = 'popular'"
                            :aria-selected="(tab === 'popular').toString()"
                            :class="tab === 'popular' ? 'bg-white text-[#181615] shadow-sm border border-[#E5E0D8]' : 'text-[#5E5850] hover:text-[#181615]'"
                            class="px-2.5 py-1 rounded-md text-xs font-semibold transition-colors cursor-pointer"
                        >
                            Sering Dipinjam
                        </button>
                    </div>

                    <a href="/admin/books" wire:navigate class="text-xs font-semibold text-[#114B43] hover:text-[#0C3832] shrink-0">
                        Katalog &rarr;
                    </a>
                </div>

                <!-- Tab 1: Low Stock Books -->
                <div x-show="tab === 'stock'" class="mt-4 space-y-3">
                    @forelse ($this->lowStockBooks as $book)
                        <div wire:key="low-stock-{{ $book->id }}" class="flex items-start justify-between gap-3 py-2 border-b border-[#F3EFE8] last:border-b-0 last:pb-0">
                            <div class="min-w-0">
                                <div class="text-[13px] font-semibold text-[#181615] leading-snug line-clamp-1" title="{{ $book->title }}">
                                    {{ $book->title }}
                                </div>
                                <div class="text-[11px] text-[#5E5850] mt-0.5 flex items-center gap-1.5">
                                    <span class="truncate">{{ $book->author }}</span>
                                    @if ($book->category)
                                        <span aria-hidden="true">&middot;</span>
                                        <span class="font-mono text-[10px]">{{ $book->category->rack_code }}</span>
                                    @endif
                                </div>
                            </div>

                            @if ($book->stock <= 0)
                                <span class="px-2 py-0.5 rounded font-mono text-[11px] font-semibold bg-[#FEF2F2] text-[#991B1B] border border-[#FECACA] shrink-0">
                                    Habis
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded font-mono text-[11px] font-semibold bg-[#FEF3C7] text-[#92400E] border border-[#FDE68A] tabular-nums shrink-0">
                                    Sisa {{ $book->stock }}
                                </span>
                            @endif
                        </div>
                    @empty
                        <div class="py-5 text-center space-y-1">
                            <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-[#F0FDF4] text-[#166534] mx-auto">
                                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="text-xs font-medium text-[#181615]">Seluruh stok aman</p>
                            <p class="text-[11px] text-[#5E5850]">Semua buku memiliki lebih dari 2 eksemplar tersedia.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Tab 2: Popular Books -->
                <div x-show="tab === 'popular'" x-cloak class="mt-4 space-y-3">
                    @forelse ($this->popularBooks as $book)
                        <div wire:key="popular-{{ $book->id }}" class="flex items-start justify-between gap-3 py-2 border-b border-[#F3EFE8] last:border-b-0 last:pb-0">
                            <div class="min-w-0">
                                <div class="text-[13px] font-semibold text-[#181615] leading-snug line-clamp-1" title="{{ $book->title }}">
                                    {{ $book->title }}
                                </div>
                                <div class="text-[11px] text-[#5E5850] mt-0.5">
                                    {{ $book->category?->name ?? 'Umum' }} &middot; {{ $book->bookmarks_count }} favorit
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded font-mono text-[11px] font-semibold bg-[#EAF2F0] text-[#114B43] tabular-nums shrink-0">
                                {{ $book->borrowings_count }}&times; pinjam
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-[#5E5850] py-4 text-center">Belum ada riwayat peminjaman buku.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </section>
</div>
