<?php

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use Livewire\Component;

new class extends Component
{
    public function mount()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403);
        }
    }

    public function render()
    {
        return $this->view([
            'totalBookTitles' => Book::count(),
            'totalAvailableStock' => Book::sum('stock') ?? 0,
            'totalActiveBorrowings' => Borrowing::where('status', 'dipinjam')->count(),
            'totalFinesCollected' => Borrowing::sum('fine_amount') ?? 0,
            'recentBorrowings' => Borrowing::with(['user', 'book'])
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    {{-- Header & Quick Navigation --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                    Panel Admin / Pustakawan
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Dashboard Perpustakaan</h1>
            <p class="text-slate-500 text-sm mt-0.5">Ringkasan statistik koleksi buku, ketersediaan stok, dan sirkulasi peminjaman siswa.</p>
        </div>

        {{-- Pintasan Tombol Navigasi Cepat (wire:navigate) --}}
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.categories') }}" wire:navigate
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 hover:text-indigo-600 hover:border-indigo-300 transition-all shadow-sm">
                <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Kelola Rak
            </a>
            <a href="{{ route('admin.books') }}" wire:navigate
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 hover:text-indigo-600 hover:border-indigo-300 transition-all shadow-sm">
                <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                Kelola Buku
            </a>
            <a href="{{ route('admin.borrowings') }}" wire:navigate
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-all shadow-sm shadow-indigo-200">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                Data Peminjaman
            </a>
        </div>
    </div>

    {{-- 4 Kartu Statistik Utama --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        {{-- Card 1: Total Judul Buku --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="absolute -right-3 -bottom-3 w-20 h-20 bg-indigo-50 rounded-full group-hover:scale-125 transition-transform"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Judul Buku</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ number_format($totalBookTitles, 0, ',', '.') }}</p>
                    <p class="text-xs text-indigo-600 font-medium mt-1">Judul terdaftar di katalog</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-100/80 flex items-center justify-center text-indigo-600 text-xl shadow-inner">
                    📖
                </div>
            </div>
        </div>

        {{-- Card 2: Total Eksemplar Stok Tersedia --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="absolute -right-3 -bottom-3 w-20 h-20 bg-emerald-50 rounded-full group-hover:scale-125 transition-transform"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Stok Tersedia</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ number_format($totalAvailableStock, 0, ',', '.') }}</p>
                    <p class="text-xs text-emerald-600 font-medium mt-1">Eksemplar fisik siap dipinjam</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-100/80 flex items-center justify-center text-emerald-600 text-xl shadow-inner">
                    📦
                </div>
            </div>
        </div>

        {{-- Card 3: Buku Sedang Dipinjam --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="absolute -right-3 -bottom-3 w-20 h-20 bg-amber-50 rounded-full group-hover:scale-125 transition-transform"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sedang Dipinjam</p>
                    <p class="text-3xl font-extrabold text-amber-600 mt-2">{{ number_format($totalActiveBorrowings, 0, ',', '.') }}</p>
                    <p class="text-xs text-amber-600 font-medium mt-1">Sirkulasi aktif oleh siswa</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-100/80 flex items-center justify-center text-amber-600 text-xl shadow-inner">
                    ⏳
                </div>
            </div>
        </div>

        {{-- Card 4: Total Denda Terkumpul --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="absolute -right-3 -bottom-3 w-20 h-20 bg-rose-50 rounded-full group-hover:scale-125 transition-transform"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Denda Terkumpul</p>
                    <p class="text-2xl sm:text-3xl font-extrabold text-rose-600 mt-2">Rp {{ number_format($totalFinesCollected, 0, ',', '.') }}</p>
                    <p class="text-xs text-rose-600 font-medium mt-1">Denda keterlambatan sirkulasi</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-100/80 flex items-center justify-center text-rose-600 text-xl shadow-inner">
                    💰
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel 5 Transaksi Peminjaman Terbaru --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-lg font-bold text-slate-900">5 Transaksi Peminjaman Terbaru</h2>
                <p class="text-xs text-slate-500 mt-0.5">Pantau status transaksi sirkulasi siswa terkini.</p>
            </div>
            <a href="{{ route('admin.borrowings') }}" wire:navigate
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                Buka Semua Data Peminjaman
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50/80 text-xs font-semibold uppercase text-slate-500 tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Kode Pinjam</th>
                        <th class="px-6 py-3.5">Nama Siswa</th>
                        <th class="px-6 py-3.5">Judul Buku</th>
                        <th class="px-6 py-3.5">Tgl Pinjam</th>
                        <th class="px-6 py-3.5">Jatuh Tempo</th>
                        <th class="px-6 py-3.5">Denda</th>
                        <th class="px-6 py-3.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentBorrowings as $borrowing)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-6 py-4 font-mono font-semibold text-xs text-indigo-600 whitespace-nowrap">
                                {{ $borrowing->borrow_code }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-slate-900">{{ $borrowing->user->name ?? '-' }}</div>
                                <div class="text-xs text-slate-400">NIS: {{ $borrowing->user->nis ?? '-' }} • {{ $borrowing->user->class_name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 max-w-xs truncate font-medium text-slate-800" title="{{ $borrowing->book->title ?? '-' }}">
                                {{ $borrowing->book->title ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                {{ $borrowing->borrow_date ? $borrowing->borrow_date->format('d M Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                {{ $borrowing->due_date ? $borrowing->due_date->format('d M Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs font-medium text-slate-700">
                                @if($borrowing->fine_amount > 0)
                                    <span class="text-rose-600 font-bold">Rp {{ number_format($borrowing->fine_amount, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-slate-400">Rp 0</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($borrowing->status === 'dipinjam')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Dipinjam
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Dikembalikan
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <p class="font-medium text-slate-500">Belum ada transaksi peminjaman.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
