<?php

use App\Models\Book;
use App\Models\Borrowing;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public function mount(): void
    {
        if (! auth()->check() || auth()->user()->role !== 'admin') {
            abort(403);
        }
    }

    #[Computed]
    public function totalBookTitles(): int
    {
        return Book::count();
    }

    #[Computed]
    public function totalStock(): int
    {
        return (int) Book::sum('stock');
    }

    #[Computed]
    public function borrowedBooksCount(): int
    {
        return Borrowing::where('status', 'dipinjam')->count();
    }

    #[Computed]
    public function totalFineAmount(): int
    {
        return (int) Borrowing::sum('fine_amount');
    }

    #[Computed]
    public function recentBorrowings()
    {
        return Borrowing::with(['user', 'book'])->latest()->take(5)->get();
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">Modul Pustakawan</span>
                <span class="text-xs text-slate-400">•</span>
                <span class="text-xs text-slate-500">Perpustakaan Sekolah</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Dashboard Admin</h1>
            <p class="text-sm text-slate-500 mt-0.5">Ringkasan statistik koleksi, aktivitas sirkulasi peminjaman, dan tindakan cepat.</p>
        </div>

        <!-- Tombol Pintasan Cepat -->
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="/admin/categories" wire:navigate class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 hover:text-slate-900 rounded-xl transition shadow-sm">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Kelola Rak
            </a>
            <a href="/admin/books" wire:navigate class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition shadow-sm shadow-indigo-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                Kelola Buku
            </a>
            <a href="/admin/borrowings" wire:navigate class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-200 rounded-xl transition shadow-sm">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                Data Peminjaman
            </a>
        </div>
    </div>

    <!-- 4 Kartu Statistik Utama -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Kartu 1: Total Judul Buku -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Judul</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-black text-slate-900">{{ number_format($this->totalBookTitles) }}</div>
                <div class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                    <span class="font-medium text-blue-600">Judul Buku</span> terdaftar dalam katalog
                </div>
            </div>
        </div>

        <!-- Kartu 2: Total Eksemplar Stok Tersedia -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Stok Eksemplar</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-black text-slate-900">{{ number_format($this->totalStock) }}</div>
                <div class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                    <span class="font-medium text-emerald-600">Eksemplar Fisik</span> siap dipinjam siswa
                </div>
            </div>
        </div>

        <!-- Kartu 3: Buku Sedang Dipinjam -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Sedang Dipinjam</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-black text-slate-900">{{ number_format($this->borrowedBooksCount) }}</div>
                <div class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                    <span class="font-medium text-amber-600">Buku Aktif</span> di tangan pembaca
                </div>
            </div>
        </div>

        <!-- Kartu 4: Total Denda Terkumpul -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Denda Terkumpul</span>
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl sm:text-3xl font-black text-slate-900">Rp {{ number_format($this->totalFineAmount, 0, ',', '.') }}</div>
                <div class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                    <span class="font-medium text-rose-600">Akumulasi</span> dari denda keterlambatan
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel 5 Transaksi Peminjaman Terbaru -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900">5 Transaksi Peminjaman Terbaru</h2>
                <p class="text-xs text-slate-500">Aktivitas peminjaman dan sirkulasi buku terkini oleh siswa.</p>
            </div>
            <a href="/admin/borrowings" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700 transition">
                <span>Lihat Semua Peminjaman</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">Kode Pinjam</th>
                        <th class="py-3.5 px-6">Siswa</th>
                        <th class="py-3.5 px-6">Judul Buku</th>
                        <th class="py-3.5 px-6">Tgl Pinjam</th>
                        <th class="py-3.5 px-6">Jatuh Tempo</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Denda</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($this->recentBorrowings as $borrowing)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-4 px-6 font-mono font-medium text-xs text-indigo-700">
                                {{ $borrowing->borrow_code }}
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-semibold text-slate-900">{{ $borrowing->user?->name ?? '-' }}</div>
                                <div class="text-xs text-slate-400">
                                    {{ $borrowing->user?->nis ? 'NIS: ' . $borrowing->user->nis : ($borrowing->user?->class_name ?? 'Siswa') }}
                                </div>
                            </td>
                            <td class="py-4 px-6 max-w-xs truncate font-medium text-slate-800" title="{{ $borrowing->book?->title }}">
                                {{ $borrowing->book?->title ?? '-' }}
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-600 whitespace-nowrap">
                                {{ $borrowing->borrow_date?->translatedFormat('d M Y') ?? $borrowing->borrow_date }}
                            </td>
                            <td class="py-4 px-6 text-xs whitespace-nowrap">
                                <span class="{{ $borrowing->status === 'dipinjam' && now()->startOfDay()->greaterThan($borrowing->due_date) ? 'text-rose-600 font-bold' : 'text-slate-600' }}">
                                    {{ $borrowing->due_date?->translatedFormat('d M Y') ?? $borrowing->due_date }}
                                </span>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap">
                                @if($borrowing->status === 'dipinjam')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Dipinjam
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Dikembalikan
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                @if($borrowing->fine_amount > 0)
                                    <span class="font-semibold text-rose-600">Rp {{ number_format($borrowing->fine_amount, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-xs text-slate-400">Rp 0</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-sm font-medium">Belum ada aktivitas transaksi peminjaman.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
