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
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Pinjaman Saya</h1>
                <p class="mt-1 text-sm sm:text-base text-slate-500">
                    Pantau status peminjaman buku, batas waktu pengembalian, dan kalkulasi denda otomatis.
                </p>
            </div>
            <a href="/" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                Pinjam Buku Lainnya
            </a>
        </div>
    </div>

    @guest
        <!-- State Pengunjung / Belum Login -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 sm:p-12 text-center max-w-xl mx-auto">
            <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">Akses Terbatas</h2>
            <p class="text-slate-500 text-sm mb-6">Silakan masuk dengan akun siswa Anda terlebih dahulu untuk melihat daftar buku yang sedang Anda pinjam dan riwayat denda.</p>
            <a href="/login" wire:navigate class="inline-flex items-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                Masuk Sekarang
            </a>
        </div>
    @else
        <!-- 3 Kartu Ringkasan Atas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            <!-- Kartu 1: Total Buku Sedang Dipinjam -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm relative overflow-hidden group hover:border-indigo-300 transition">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-semibold text-slate-500">Sedang Dipinjam</span>
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:scale-110 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-slate-900">{{ $totalDipinjam }}</span>
                    <span class="text-sm font-medium text-slate-500">Buku</span>
                </div>
                <p class="mt-2 text-xs text-slate-400">Total buku aktif dalam masa pinjam siswa</p>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-indigo-500"></div>
            </div>

            <!-- Kartu 2: Jumlah Buku Terlambat -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm relative overflow-hidden group hover:border-rose-300 transition">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-semibold text-slate-500">Buku Terlambat</span>
                    <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center group-hover:scale-110 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold {{ $totalTerlambat > 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ $totalTerlambat }}</span>
                    <span class="text-sm font-medium text-slate-500">Buku</span>
                </div>
                <p class="mt-2 text-xs text-slate-400">Peminjaman yang melewati tanggal batas kembali</p>
                <div class="absolute bottom-0 left-0 right-0 h-1 {{ $totalTerlambat > 0 ? 'bg-rose-500' : 'bg-slate-200' }}"></div>
            </div>

            <!-- Kartu 3: Total Estimasi Denda Saat Ini -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm relative overflow-hidden group hover:border-amber-300 transition">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-semibold text-slate-500">Estimasi Denda Berjalan</span>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-110 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-3xl font-extrabold {{ $totalDenda > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                        Rp {{ number_format($totalDenda, 0, ',', '.') }}
                    </span>
                </div>
                <p class="mt-2 text-xs text-slate-400">Tarif otomatis Rp 2.000 / hari keterlambatan</p>
                <div class="absolute bottom-0 left-0 right-0 h-1 {{ $totalDenda > 0 ? 'bg-rose-500' : 'bg-emerald-500' }}"></div>
            </div>
        </div>

        <!-- Filter & Pencarian Live -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Tab Filter Status -->
            <div class="flex items-center bg-slate-100/80 p-1 rounded-xl w-full sm:w-auto">
                <button
                    type="button"
                    wire:click="setFilter('all')"
                    class="flex-1 sm:flex-none px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition {{ $statusFilter === 'all' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
                >
                    Semua
                </button>
                <button
                    type="button"
                    wire:click="setFilter('dipinjam')"
                    class="flex-1 sm:flex-none px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition {{ $statusFilter === 'dipinjam' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
                >
                    Sedang Dipinjam
                </button>
                <button
                    type="button"
                    wire:click="setFilter('dikembalikan')"
                    class="flex-1 sm:flex-none px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition {{ $statusFilter === 'dikembalikan' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
                >
                    Sudah Dikembalikan
                </button>
            </div>

            <!-- Input Pencarian Live -->
            <div class="relative w-full sm:w-80">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari kode pinjam atau judul buku..."
                    class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition"
                >
                @if(!empty($search))
                    <button
                        type="button"
                        wire:click="$set('search', '')"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        <!-- Tabel/Daftar Riwayat Pinjaman -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-4 sm:px-6">Kode Pinjam</th>
                            <th class="py-3.5 px-4 sm:px-6">Buku</th>
                            <th class="py-3.5 px-4 sm:px-6">Tgl Pinjam</th>
                            <th class="py-3.5 px-4 sm:px-6">Batas Kembali</th>
                            <th class="py-3.5 px-4 sm:px-6">Tgl Dikembalikan</th>
                            <th class="py-3.5 px-4 sm:px-6 text-right">Status & Denda</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($borrowings as $borrowing)
                            @php
                                $stats = $this->getBorrowingStats($borrowing);
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <!-- Kode Pinjam -->
                                <td class="py-4 px-4 sm:px-6 font-mono text-xs font-semibold text-slate-800 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-slate-100 border border-slate-200 text-slate-700">
                                        {{ $borrowing->borrow_code }}
                                    </span>
                                </td>

                                <!-- Info Buku -->
                                <td class="py-4 px-4 sm:px-6">
                                    <div class="flex items-center gap-3 min-w-[220px]">
                                        <div class="w-10 h-14 bg-slate-100 rounded-md overflow-hidden shrink-0 flex items-center justify-center border border-slate-200">
                                            @if($borrowing->book?->cover_image)
                                                <img src="{{ $borrowing->book->cover_image }}" alt="{{ $borrowing->book->title }}" class="w-full h-full object-cover">
                                            @else
                                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <a href="/books/{{ $borrowing->book_id }}" wire:navigate class="font-bold text-slate-900 hover:text-indigo-600 line-clamp-1 transition">
                                                {{ $borrowing->book?->title ?? 'Buku Tidak Diketahui' }}
                                            </a>
                                            <p class="text-xs text-slate-500 line-clamp-1">
                                                {{ $borrowing->book?->author ?? '-' }}
                                            </p>
                                            @if($borrowing->book?->category)
                                                <span class="inline-block mt-1 text-[11px] font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">
                                                    {{ $borrowing->book->category->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Tanggal Pinjam -->
                                <td class="py-4 px-4 sm:px-6 whitespace-nowrap text-slate-600">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        {{ \Carbon\Carbon::parse($borrowing->borrow_date)->translatedFormat('d M Y') }}
                                    </div>
                                </td>

                                <!-- Batas Kembali (due_date) -->
                                <td class="py-4 px-4 sm:px-6 whitespace-nowrap font-medium text-slate-700">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ \Carbon\Carbon::parse($borrowing->due_date)->translatedFormat('d M Y') }}
                                    </div>
                                </td>

                                <!-- Tanggal Dikembalikan -->
                                <td class="py-4 px-4 sm:px-6 whitespace-nowrap text-slate-600">
                                    @if($borrowing->return_date)
                                        <span class="inline-flex items-center gap-1 text-slate-700 font-medium">
                                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            {{ \Carbon\Carbon::parse($borrowing->return_date)->translatedFormat('d M Y') }}
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Belum dikembalikan</span>
                                    @endif
                                </td>

                                <!-- Badge Status Dinamis -->
                                <td class="py-4 px-4 sm:px-6 text-right whitespace-nowrap">
                                    @if($borrowing->status === 'dipinjam')
                                        @if($stats['is_overdue'])
                                            <!-- Merah Menyala: Terlambat X Hari - Denda Rp Y -->
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/30">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-pulse"></span>
                                                Terlambat {{ $stats['days_late'] }} Hari - Denda Rp {{ number_format($stats['fine'], 0, ',', '.') }}
                                            </span>
                                        @else
                                            <!-- Hijau: Dipinjam - Sisa X Hari -->
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                Dipinjam - Sisa {{ $stats['days_remaining'] }} Hari
                                            </span>
                                        @endif
                                    @else
                                        <!-- Abu-abu/Biru: Sudah Dikembalikan -->
                                        <div class="inline-flex flex-col items-end">
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-600/10">
                                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Sudah Dikembalikan
                                            </span>
                                            @if(($borrowing->fine_amount ?? 0) > 0)
                                                <span class="text-[11px] text-slate-500 mt-1 font-medium">
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
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-sm font-semibold text-slate-900 mb-1">Tidak Ada Data Pinjaman</h3>
                                        <p class="text-xs text-slate-500">
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
                <div class="px-4 sm:px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $borrowings->links() }}
                </div>
            @endif
        </div>
    @endguest
</div>
