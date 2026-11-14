<?php

use App\Models\Borrowing;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all'; // 'all', 'dipinjam', 'dikembalikan'

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    /**
     * Logika Kalkulator Denda Otomatis (Rp 2.000 / hari).
     *
     * @return array{is_overdue: bool, days_late: int, days_remaining: int, fine: int}
     */
    public function getBorrowingStats(Borrowing $borrowing): array
    {
        $today = now()->startOfDay();
        $dueDate = Carbon::parse($borrowing->due_date)->startOfDay();

        if ($borrowing->status === 'dipinjam') {
            if ($today->gt($dueDate)) {
                $daysLate = (int) $dueDate->diffInDays($today);

                return [
                    'is_overdue' => true,
                    'days_late' => $daysLate,
                    'days_remaining' => 0,
                    'fine' => $daysLate * 2000,
                ];
            }

            $daysRemaining = (int) $today->diffInDays($dueDate);

            return [
                'is_overdue' => false,
                'days_late' => 0,
                'days_remaining' => $daysRemaining,
                'fine' => 0,
            ];
        }

        return [
            'is_overdue' => false,
            'days_late' => 0,
            'days_remaining' => 0,
            'fine' => (int) ($borrowing->fine_amount ?? 0),
        ];
    }

    public function with(): array
    {
        $userId = auth()->id();

        if (! $userId) {
            return [
                'borrowings' => new LengthAwarePaginator([], 0, 10),
                'totalDipinjam' => 0,
                'totalTerlambat' => 0,
                'totalDenda' => 0,
            ];
        }

        // 3 Kartu Ringkasan Atas
        $allActiveBorrowings = Borrowing::where('user_id', $userId)
            ->where('status', 'dipinjam')
            ->get();

        $totalDipinjam = $allActiveBorrowings->count();
        $totalTerlambat = 0;
        $totalDenda = 0;
        $today = now()->startOfDay();

        foreach ($allActiveBorrowings as $b) {
            $dueDate = Carbon::parse($b->due_date)->startOfDay();
            if ($today->gt($dueDate)) {
                $totalTerlambat++;
                $daysLate = (int) $dueDate->diffInDays($today);
                $totalDenda += ($daysLate * 2000);
            }
        }

        // Filter & Pencarian Live
        $query = Borrowing::query()
            ->where('user_id', $userId)
            ->with(['book.category'])
            ->latest('borrow_date');

        if ($this->statusFilter === 'dipinjam') {
            $query->where('status', 'dipinjam');
        } elseif ($this->statusFilter === 'dikembalikan') {
            $query->where('status', 'dikembalikan');
        }

        if (! empty(trim($this->search))) {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('borrow_code', 'like', $term)
                    ->orWhereHas('book', function ($bq) use ($term) {
                        $bq->where('title', 'like', $term)
                            ->orWhere('author', 'like', $term);
                    });
            });
        }

        $borrowings = $query->paginate(10);

        return [
            'borrowings' => $borrowings,
            'totalDipinjam' => $totalDipinjam,
            'totalTerlambat' => $totalTerlambat,
            'totalDenda' => $totalDenda,
        ];
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
    <!-- Header Halaman -->
    <div class="mb-8 border-b border-stone-200/80 pb-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <span class="font-mono text-[11px] uppercase tracking-[0.12em] text-stone-500">
                    SIRKULASI ANGGOTA
                </span>
                <h1 class="font-serif text-3xl sm:text-4xl font-semibold text-stone-900 tracking-tight mt-1">
                    Pinjaman Saya
                </h1>
                <p class="mt-1 text-sm sm:text-base text-stone-600">
                    Pantau status peminjaman buku, batas waktu pengembalian, dan kalkulasi denda otomatis.
                </p>
            </div>
            <a
                href="/"
                wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border border-[#144533]/20 bg-[#144533]/5 text-[#144533] hover:bg-[#144533] hover:text-white rounded-xl transition shrink-0 self-start sm:self-auto"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                <span>Pinjam Buku Lainnya</span>
            </a>
        </div>
    </div>

    @guest
        <!-- State Pengunjung / Belum Login -->
        <div class="bg-white rounded-2xl shadow-sm border border-stone-200/80 p-8 sm:p-12 text-center max-w-xl mx-auto">
            <div class="w-14 h-14 bg-[#144533]/10 text-[#144533] rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="font-serif text-xl font-semibold text-stone-900 mb-2">Akses Terbatas</h2>
            <p class="text-stone-600 text-sm mb-6">
                Silakan masuk dengan akun siswa Anda terlebih dahulu untuk melihat daftar buku yang sedang Anda pinjam dan riwayat denda.
            </p>
            <a href="/login" wire:navigate class="inline-flex items-center px-6 py-2.5 bg-[#144533] hover:bg-[#0F3325] text-white text-sm font-semibold rounded-xl shadow-sm transition">
                Masuk Sekarang
            </a>
        </div>
    @else
        <!-- 3 Kartu Ringkasan Atas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
            <!-- Kartu 1: Total Buku Sedang Dipinjam -->
            <div class="bg-white rounded-xl p-5 border border-stone-200/80 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-semibold text-stone-600">Sedang Dipinjam</span>
                    <div class="w-10 h-10 rounded-lg bg-[#144533]/10 text-[#144533] flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="font-serif text-3xl font-semibold text-stone-900 tabular-nums">{{ $totalDipinjam }}</span>
                    <span class="text-sm font-medium text-stone-500">Buku</span>
                </div>
                <p class="mt-2 text-xs text-stone-500">Total buku aktif dalam masa pinjam siswa</p>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-[#144533]"></div>
            </div>

            <!-- Kartu 2: Jumlah Buku Terlambat -->
            <div class="bg-white rounded-xl p-5 border border-stone-200/80 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-semibold text-stone-600">Buku Terlambat</span>
                    <div class="w-10 h-10 rounded-lg bg-red-50 text-red-700 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="font-serif text-3xl font-semibold tabular-nums {{ $totalTerlambat > 0 ? 'text-red-700' : 'text-stone-900' }}">{{ $totalTerlambat }}</span>
                    <span class="text-sm font-medium text-stone-500">Buku</span>
                </div>
                <p class="mt-2 text-xs text-stone-500">Peminjaman yang melewati tanggal batas kembali</p>
                <div class="absolute bottom-0 left-0 right-0 h-1 {{ $totalTerlambat > 0 ? 'bg-red-600' : 'bg-stone-200' }}"></div>
            </div>

            <!-- Kartu 3: Total Estimasi Denda Saat Ini -->
            <div class="bg-white rounded-xl p-5 border border-stone-200/80 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-semibold text-stone-600">Estimasi Denda Berjalan</span>
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="font-serif text-3xl font-semibold tabular-nums {{ $totalDenda > 0 ? 'text-red-700' : 'text-emerald-700' }}">
                        Rp {{ number_format($totalDenda, 0, ',', '.') }}
                    </span>
                </div>
                <p class="mt-2 text-xs text-stone-500">Tarif otomatis Rp 2.000 / hari keterlambatan</p>
                <div class="absolute bottom-0 left-0 right-0 h-1 {{ $totalDenda > 0 ? 'bg-red-600' : 'bg-emerald-600' }}"></div>
            </div>
        </div>

        <!-- Filter & Pencarian Live -->
        <div class="bg-white rounded-xl p-4 border border-stone-200/80 shadow-sm mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Tab Filter Status -->
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <button
                    type="button"
                    wire:click="setFilter('all')"
                    @class([
                        'flex-1 sm:flex-none px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold border transition cursor-pointer',
                        'bg-[#144533] text-white border-[#144533]' => $statusFilter === 'all',
                        'bg-[#FAF8F5] text-stone-700 border-stone-200/80 hover:border-[#144533]/40 hover:text-[#144533]' => $statusFilter !== 'all',
                    ])
                >
                    Semua
                </button>
                <button
                    type="button"
                    wire:click="setFilter('dipinjam')"
                    @class([
                        'flex-1 sm:flex-none px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold border transition cursor-pointer',
                        'bg-[#144533] text-white border-[#144533]' => $statusFilter === 'dipinjam',
                        'bg-[#FAF8F5] text-stone-700 border-stone-200/80 hover:border-[#144533]/40 hover:text-[#144533]' => $statusFilter !== 'dipinjam',
                    ])
                >
                    Sedang Dipinjam
                </button>
                <button
                    type="button"
                    wire:click="setFilter('dikembalikan')"
                    @class([
                        'flex-1 sm:flex-none px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold border transition cursor-pointer',
                        'bg-[#144533] text-white border-[#144533]' => $statusFilter === 'dikembalikan',
                        'bg-[#FAF8F5] text-stone-700 border-stone-200/80 hover:border-[#144533]/40 hover:text-[#144533]' => $statusFilter !== 'dikembalikan',
                    ])
                >
                    Sudah Dikembalikan
                </button>
            </div>

            <!-- Input Pencarian Live -->
            <div class="relative w-full sm:w-80">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari kode pinjam atau judul buku..."
                    class="w-full pl-10 pr-9 py-2 bg-[#FAF8F5] border border-stone-300 rounded-lg text-sm text-stone-900 placeholder-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#144533]/20 focus:border-[#144533] transition"
                >
                @if(!empty($search))
                    <button
                        type="button"
                        wire:click="$set('search', '')"
                        aria-label="Hapus kata kunci"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-400 hover:text-stone-700 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        <!-- Daftar Riwayat Pinjaman: Card List (HP & Tablet) + Tabel (Desktop) -->
        <div class="bg-white rounded-xl border border-stone-200/80 shadow-sm overflow-hidden">
            <!-- Mobile & Tablet Card View (< 1024px) -->
            <div class="lg:hidden grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
                @forelse($borrowings as $borrowing)
                    @php
                        $stats = $this->getBorrowingStats($borrowing);
                    @endphp
                    <div wire:key="my-borrow-card-{{ $borrowing->id }}" class="rounded-xl border border-stone-200/90 bg-[#FAF8F5]/60 p-4 flex flex-col justify-between gap-3.5 min-w-0">
                        <div class="space-y-3 min-w-0">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded font-mono text-xs font-semibold bg-stone-100 border border-stone-200 text-stone-700">
                                    {{ $borrowing->borrow_code }}
                                </span>

                                @if($borrowing->status === 'dipinjam')
                                    @if($stats['is_overdue'])
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-600 shrink-0" aria-hidden="true"></span>
                                            Terlambat {{ $stats['days_late'] }} Hr
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shrink-0" aria-hidden="true"></span>
                                            Sisa {{ $stats['days_remaining'] }} Hr
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-stone-100 text-stone-700 border border-stone-200">
                                        Sudah Dikembalikan
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-start gap-3 min-w-0">
                                <div class="w-11 h-14 bg-stone-100 rounded border border-stone-200 shrink-0 overflow-hidden flex items-center justify-center text-[#144533]">
                                    @if($borrowing->book?->cover_image)
                                        <img
                                            src="{{ str_starts_with($borrowing->book->cover_image, 'http') ? $borrowing->book->cover_image : asset('storage/' . $borrowing->book->cover_image) }}"
                                            alt="{{ $borrowing->book->title }}"
                                            class="w-full h-full object-cover"
                                        >
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                        </svg>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <a href="/books/{{ $borrowing->book_id }}" wire:navigate class="font-serif text-base font-semibold text-stone-900 hover:text-[#144533] line-clamp-2 [overflow-wrap:anywhere] transition">
                                        {{ $borrowing->book?->title ?? 'Buku Tidak Diketahui' }}
                                    </a>
                                    <p class="text-xs text-stone-500 mt-0.5 [overflow-wrap:anywhere]">
                                        {{ $borrowing->book?->author ?? '-' }}
                                    </p>
                                    @if($borrowing->book?->category)
                                        <span class="inline-block mt-1 font-mono text-[10px] font-medium text-stone-700 bg-stone-100 border border-stone-200 px-2 py-0.5 rounded">
                                            {{ $borrowing->book->category->name }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs bg-white rounded-lg p-2.5 border border-stone-200/70">
                                <div>
                                    <div class="text-[10px] font-mono uppercase text-stone-400">Tgl Pinjam</div>
                                    <div class="font-mono text-stone-700 mt-0.5">{{ \Carbon\Carbon::parse($borrowing->borrow_date)->translatedFormat('d M Y') }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-mono uppercase text-stone-400">Batas Kembali</div>
                                    <div class="font-mono font-semibold text-stone-800 mt-0.5">{{ \Carbon\Carbon::parse($borrowing->due_date)->translatedFormat('d M Y') }}</div>
                                </div>
                                <div class="col-span-2 pt-1.5 border-t border-stone-100 flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <span class="text-[10px] font-mono uppercase text-stone-400">Dikembalikan: </span>
                                        @if($borrowing->return_date)
                                            <span class="font-mono text-emerald-700 font-medium">{{ \Carbon\Carbon::parse($borrowing->return_date)->translatedFormat('d M Y') }}</span>
                                        @else
                                            <span class="italic text-stone-400">Belum</span>
                                        @endif
                                    </div>

                                    @if($borrowing->status === 'dipinjam' && $stats['is_overdue'])
                                        <span class="font-mono font-semibold text-red-700">
                                            Denda Rp {{ number_format($stats['fine'], 0, ',', '.') }}
                                        </span>
                                    @elseif(($borrowing->fine_amount ?? 0) > 0)
                                        <span class="font-mono font-semibold text-stone-700">
                                            Denda Rp {{ number_format($borrowing->fine_amount, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="md:col-span-2 py-12 px-4 text-center">
                        <div class="max-w-xs mx-auto">
                            <div class="w-12 h-12 rounded-xl bg-stone-100 text-stone-400 flex items-center justify-center mx-auto mb-3 border border-stone-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="font-serif text-base font-semibold text-stone-900 mb-1">Tidak Ada Data Pinjaman</h3>
                            <p class="text-xs text-stone-500">
                                @if(!empty($search))
                                    Tidak ditemukan pinjaman yang cocok dengan kata kunci "{{ $search }}".
                                @else
                                    Anda belum memiliki riwayat peminjaman pada filter ini.
                                @endif
                            </p>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Desktop Table View (>= 1024px) -->
            <div class="hidden lg:block overflow-x-auto lg:overflow-x-visible">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#FAF8F5] border-b border-stone-200/80 text-[11px] font-mono font-semibold text-stone-600 uppercase tracking-wider">
                            <th class="px-4 py-3.5 whitespace-nowrap">Kode Pinjam</th>
                            <th class="px-4 py-3.5">Buku</th>
                            <th class="px-4 py-3.5 whitespace-nowrap">Tgl Pinjam</th>
                            <th class="px-4 py-3.5 whitespace-nowrap">Batas Kembali</th>
                            <th class="px-4 py-3.5 whitespace-nowrap">Tgl Dikembalikan</th>
                            <th class="px-4 py-3.5 text-right whitespace-nowrap">Status &amp; Denda</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200/70 text-sm">
                        @forelse($borrowings as $borrowing)
                            @php
                                $stats = $this->getBorrowingStats($borrowing);
                            @endphp
                            <tr wire:key="my-borrow-row-{{ $borrowing->id }}" class="hover:bg-[#FAF8F5]/80 transition-colors">
                                <!-- Kode Pinjam -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded font-mono text-xs font-semibold bg-stone-100 border border-stone-200 text-stone-700">
                                        {{ $borrowing->borrow_code }}
                                    </span>
                                </td>

                                <!-- Info Buku -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3 max-w-[260px] xl:max-w-xs">
                                        <div class="w-9 h-12 bg-stone-100 rounded border border-stone-200 shrink-0 overflow-hidden flex items-center justify-center text-[#144533]">
                                            @if($borrowing->book?->cover_image)
                                                <img src="{{ str_starts_with($borrowing->book->cover_image, 'http') ? $borrowing->book->cover_image : asset('storage/' . $borrowing->book->cover_image) }}" alt="{{ $borrowing->book->title }}" class="w-full h-full object-cover">
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <a href="/books/{{ $borrowing->book_id }}" wire:navigate class="font-serif font-semibold text-stone-900 hover:text-[#144533] line-clamp-1 [overflow-wrap:anywhere] transition">
                                                {{ $borrowing->book?->title ?? 'Buku Tidak Diketahui' }}
                                            </a>
                                            <p class="text-xs text-stone-500 line-clamp-1">
                                                {{ $borrowing->book?->author ?? '-' }}
                                            </p>
                                            @if($borrowing->book?->category)
                                                <span class="inline-block mt-1 font-mono text-[10px] font-medium text-stone-700 bg-stone-100 border border-stone-200 px-2 py-0.5 rounded">
                                                    {{ $borrowing->book->category->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Tanggal Pinjam -->
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono text-xs text-stone-600">
                                    {{ \Carbon\Carbon::parse($borrowing->borrow_date)->translatedFormat('d M Y') }}
                                </td>

                                <!-- Batas Kembali (due_date) -->
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono text-xs font-medium text-stone-800">
                                    {{ \Carbon\Carbon::parse($borrowing->due_date)->translatedFormat('d M Y') }}
                                </td>

                                <!-- Tanggal Dikembalikan -->
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono text-xs text-stone-600">
                                    @if($borrowing->return_date)
                                        <span class="inline-flex items-center gap-1 text-stone-700 font-medium">
                                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            {{ \Carbon\Carbon::parse($borrowing->return_date)->translatedFormat('d M Y') }}
                                        </span>
                                    @else
                                        <span class="font-sans text-xs text-stone-400 italic">Belum dikembalikan</span>
                                    @endif
                                </td>

                                <!-- Badge Status Dinamis -->
                                <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                    @if($borrowing->status === 'dipinjam')
                                        @if($stats['is_overdue'])
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-600 shrink-0" aria-hidden="true"></span>
                                                Terlambat {{ $stats['days_late'] }} Hari - Denda Rp {{ number_format($stats['fine'], 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 shrink-0" aria-hidden="true"></span>
                                                Dipinjam - Sisa {{ $stats['days_remaining'] }} Hari
                                            </span>
                                        @endif
                                    @else
                                        <div class="inline-flex flex-col items-end">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-stone-100 text-stone-700 border border-stone-200">
                                                <svg class="w-3.5 h-3.5 text-stone-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Sudah Dikembalikan
                                            </span>
                                            @if(($borrowing->fine_amount ?? 0) > 0)
                                                <span class="font-mono text-[11px] text-stone-600 mt-1 font-medium">
                                                    Denda: Rp {{ number_format($borrowing->fine_amount, 0, ',', '.') }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 px-4 text-center">
                                    <div class="max-w-xs mx-auto">
                                        <div class="w-12 h-12 rounded-xl bg-stone-100 text-stone-400 flex items-center justify-center mx-auto mb-3 border border-stone-200">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="font-serif text-base font-semibold text-stone-900 mb-1">Tidak Ada Data Pinjaman</h3>
                                        <p class="text-xs text-stone-500">
                                            @if(!empty($search))
                                                Tidak ditemukan pinjaman yang cocok dengan kata kunci "{{ $search }}".
                                            @else
                                                Anda belum memiliki riwayat peminjaman pada filter ini.
                                            @endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($borrowings->hasPages())
                <div class="px-4 py-4 border-t border-stone-200/80 bg-[#FAF8F5]">
                    {{ $borrowings->links() }}
                </div>
            @endif
        </div>
    @endguest
</div>
