<?php

use App\Models\Borrowing;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'semua';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function calculateFine(Borrowing $borrowing): int
    {
        if ($borrowing->status === 'dipinjam') {
            $today = Carbon::now()->startOfDay();
            $dueDate = Carbon::parse($borrowing->due_date)->startOfDay();

            if ($today->gt($dueDate)) {
                $daysOverdue = (int) $dueDate->diffInDays($today);
                return $daysOverdue * 2000;
            }

            return 0;
        }

        return (int) ($borrowing->fine_amount ?? 0);
    }

    public function render()
    {
        $userId = auth()->id();

        if (! $userId) {
            return $this->view([
                'borrowings' => collect(),
                'totalDipinjam' => 0,
                'totalTerlambat' => 0,
                'totalEstimasiDenda' => 0,
            ]);
        }

        $allUserBorrowings = Borrowing::query()
            ->where('user_id', $userId)
            ->get();

        $today = Carbon::now()->startOfDay();

        $totalDipinjam = $allUserBorrowings
            ->where('status', 'dipinjam')
            ->count();

        $overdueBorrowings = $allUserBorrowings
            ->where('status', 'dipinjam')
            ->filter(function ($borrowing) use ($today) {
                return $today->gt(Carbon::parse($borrowing->due_date)->startOfDay());
            });

        $totalTerlambat = $overdueBorrowings->count();

        $totalEstimasiDenda = $overdueBorrowings->sum(function ($borrowing) use ($today) {
            $dueDate = Carbon::parse($borrowing->due_date)->startOfDay();
            $daysOverdue = (int) $dueDate->diffInDays($today);
            return $daysOverdue * 2000;
        });

        $query = Borrowing::query()
            ->with(['book.category'])
            ->where('user_id', $userId)
            ->latest('borrow_date');

        if ($this->statusFilter === 'dipinjam') {
            $query->where('status', 'dipinjam');
        } elseif ($this->statusFilter === 'dikembalikan') {
            $query->where('status', 'dikembalikan');
        }

        if (filled(trim($this->search))) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('borrow_code', 'like', "%{$term}%")
                    ->orWhereHas('book', function ($bq) use ($term) {
                        $bq->where('title', 'like', "%{$term}%")
                            ->orWhere('author', 'like', "%{$term}%");
                    });
            });
        }

        return $this->view([
            'borrowings' => $query->paginate(10),
            'totalDipinjam' => $totalDipinjam,
            'totalTerlambat' => $totalTerlambat,
            'totalEstimasiDenda' => $totalEstimasiDenda,
        ]);
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    {{-- Header Banner --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100 mb-3">
                <span>📖</span> Modul Pinjaman Siswa
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-800 tracking-tight">Halaman Pinjaman Saya</h1>
            <p class="text-slate-500 mt-1.5 text-sm sm:text-base leading-relaxed">
                Pantau sirkulasi peminjaman buku, batas tanggal kembali, serta kalkulasi denda otomatis keterlambatan secara real-time.
            </p>
        </div>

        @auth
            <div class="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 shrink-0">
                <div class="w-10 h-10 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center text-sm shadow-sm shadow-indigo-200">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div>
                    <div class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-slate-500">{{ auth()->user()->nis ? 'NIS: ' . auth()->user()->nis : auth()->user()->email }}</div>
                </div>
            </div>
        @endauth
    </div>

    @guest
        {{-- Banner jika belum login --}}
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center space-y-3">
            <div class="text-3xl">🔒</div>
            <h2 class="text-lg font-bold text-amber-900">Akses Terbatas</h2>
            <p class="text-sm text-amber-700 max-w-lg mx-auto">
                Silakan masuk terlebih dahulu dengan akun siswa Anda untuk melihat riwayat pinjaman buku dan pemantauan denda otomatis.
            </p>
            <div class="pt-2">
                <a href="{{ route('login') }}" wire:navigate class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-semibold text-sm shadow-sm transition-all">
                    Masuk ke Akun
                </a>
            </div>
        </div>
    @endguest

    {{-- 3 Kartu Ringkasan Atas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Kartu 1: Total Buku Sedang Dipinjam --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-indigo-50 rounded-full transition-transform group-hover:scale-110"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Kartu Ringkasan</p>
                    <h2 class="text-base font-bold text-slate-800 mt-1">Total Buku Sedang Dipinjam</h2>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl sm:text-4xl font-extrabold text-indigo-600 tracking-tight">
                            {{ $totalDipinjam }}
                        </span>
                        <span class="text-sm font-medium text-slate-500">Buku Aktif</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-2">Buku yang saat ini masih Anda pinjam.</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shrink-0 border border-indigo-100 shadow-sm">
                    📚
                </div>
            </div>
        </div>

        {{-- Kartu 2: Jumlah Buku Terlambat --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 {{ $totalTerlambat > 0 ? 'bg-rose-50' : 'bg-slate-50' }} rounded-full transition-transform group-hover:scale-110"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Status Keterlambatan</p>
                    <h2 class="text-base font-bold text-slate-800 mt-1">Jumlah Buku Terlambat</h2>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-3xl sm:text-4xl font-extrabold {{ $totalTerlambat > 0 ? 'text-rose-600' : 'text-slate-800' }} tracking-tight">
                            {{ $totalTerlambat }}
                        </span>
                        <span class="text-sm font-medium text-slate-500">Buku</span>
                    </div>
                    <p class="text-xs mt-2 {{ $totalTerlambat > 0 ? 'text-rose-600 font-medium' : 'text-slate-400' }}">
                        {{ $totalTerlambat > 0 ? 'Perlu segera dikembalikan ke perpustakaan!' : 'Semua pinjaman Anda tepat waktu.' }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl {{ $totalTerlambat > 0 ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-slate-100 text-slate-500 border-slate-200' }} flex items-center justify-center text-xl shrink-0 border shadow-sm">
                    ⚠️
                </div>
            </div>
        </div>

        {{-- Kartu 3: Total Estimasi Denda Saat Ini (Rp) --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-24 h-24 {{ $totalEstimasiDenda > 0 ? 'bg-rose-50' : 'bg-emerald-50' }} rounded-full transition-transform group-hover:scale-110"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Kalkulator Denda</p>
                    <h2 class="text-base font-bold text-slate-800 mt-1">Total Estimasi Denda Saat Ini (Rp)</h2>
                    <div class="mt-4 flex items-baseline gap-1.5">
                        <span class="text-2xl sm:text-3xl font-extrabold {{ $totalEstimasiDenda > 0 ? 'text-rose-600' : 'text-emerald-600' }} tracking-tight">
                            Rp {{ number_format($totalEstimasiDenda, 0, ',', '.') }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-2">Dihitung otomatis Rp 2.000 / hari keterlambatan.</p>
                </div>
                <div class="w-12 h-12 rounded-xl {{ $totalEstimasiDenda > 0 ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100' }} flex items-center justify-center text-xl shrink-0 border shadow-sm">
                    💰
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Tab & Pencarian Live --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-6 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            {{-- Tabs Filter Status --}}
            <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl border border-slate-200/80 self-start md:self-auto overflow-x-auto max-w-full">
                <button
                    type="button"
                    wire:click="setStatusFilter('semua')"
                    class="px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap {{ $statusFilter === 'semua' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50' }}">
                    Semua
                </button>
                <button
                    type="button"
                    wire:click="setStatusFilter('dipinjam')"
                    class="px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap {{ $statusFilter === 'dipinjam' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50' }}">
                    Sedang Dipinjam
                </button>
                <button
                    type="button"
                    wire:click="setStatusFilter('dikembalikan')"
                    class="px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap {{ $statusFilter === 'dikembalikan' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50' }}">
                    Sudah Dikembalikan
                </button>
            </div>

            {{-- Live Search Input --}}
            <div class="relative w-full md:w-80">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari kode pinjam atau judul buku..."
                    class="w-full pl-10 pr-9 py-2 rounded-xl text-sm border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-slate-50/50 placeholder:text-slate-400 text-slate-800 transition-all"
                >
                @if(!empty($search))
                    <button
                        type="button"
                        wire:click="$set('search', '')"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        {{-- Tabel Riwayat Pinjaman --}}
        <div class="overflow-hidden rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="py-3.5 px-4 text-left">Kode Pinjam</th>
                            <th scope="col" class="py-3.5 px-4 text-left">Info Buku</th>
                            <th scope="col" class="py-3.5 px-4 text-left">Tanggal Pinjam</th>
                            <th scope="col" class="py-3.5 px-4 text-left">Batas Kembali</th>
                            <th scope="col" class="py-3.5 px-4 text-left">Tanggal Dikembalikan</th>
                            <th scope="col" class="py-3.5 px-4 text-left">Status & Denda</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white text-sm">
                        @php
                            $today = \Illuminate\Support\Carbon::now()->startOfDay();
                        @endphp

                        @forelse($borrowings as $borrowing)
                            @php
                                $dueDate = \Illuminate\Support\Carbon::parse($borrowing->due_date)->startOfDay();
                                $isDipinjam = ($borrowing->status === 'dipinjam');
                                $isOverdue = $isDipinjam && $today->gt($dueDate);
                                $daysOverdue = $isOverdue ? (int) $dueDate->diffInDays($today) : 0;
                                $calculatedFine = $isDipinjam ? ($isOverdue ? $daysOverdue * 2000 : 0) : (int) ($borrowing->fine_amount ?? 0);
                                $daysRemaining = ($isDipinjam && ! $isOverdue) ? (int) $today->diffInDays($dueDate) : 0;
                            @endphp

                            <tr class="hover:bg-slate-50/70 transition-colors">
                                {{-- Kode Pinjam --}}
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                        {{ $borrowing->borrow_code }}
                                    </span>
                                </td>

                                {{-- Info Buku --}}
                                <td class="py-4 px-4 min-w-[240px]">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-14 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs shrink-0 overflow-hidden shadow-xs">
                                            @if($borrowing->book->cover_image)
                                                <img src="{{ asset('storage/' . $borrowing->book->cover_image) }}" alt="{{ $borrowing->book->title }}" class="w-full h-full object-cover">
                                            @else
                                                📖
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <a href="{{ url('/books/' . $borrowing->book_id) }}" wire:navigate class="font-semibold text-slate-900 hover:text-indigo-600 transition-colors line-clamp-2 leading-snug">
                                                {{ $borrowing->book->title }}
                                            </a>
                                            <p class="text-xs text-slate-500 mt-0.5">Penulis: {{ $borrowing->book->author }}</p>
                                            @if($borrowing->book->category)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200/60 mt-1">
                                                    {{ $borrowing->book->category->name }} ({{ $borrowing->book->category->rack_code }})
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Tanggal Pinjam --}}
                                <td class="py-4 px-4 whitespace-nowrap text-slate-700">
                                    <div class="font-medium">
                                        {{ \Illuminate\Support\Carbon::parse($borrowing->borrow_date)->format('d M Y') }}
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        {{ \Illuminate\Support\Carbon::parse($borrowing->borrow_date)->diffForHumans() }}
                                    </div>
                                </td>

                                {{-- Batas Kembali (due_date) --}}
                                <td class="py-4 px-4 whitespace-nowrap">
                                    <div class="font-semibold {{ $isOverdue ? 'text-rose-600' : 'text-slate-800' }}">
                                        {{ \Illuminate\Support\Carbon::parse($borrowing->due_date)->format('d M Y') }}
                                    </div>
                                    @if($isOverdue)
                                        <div class="text-xs text-rose-500 font-medium">Lewat {{ $daysOverdue }} hari</div>
                                    @else
                                        <div class="text-xs text-slate-400">Jatuh tempo</div>
                                    @endif
                                </td>

                                {{-- Tanggal Dikembalikan (return_date) --}}
                                <td class="py-4 px-4 whitespace-nowrap text-slate-700">
                                    @if($borrowing->return_date)
                                        <div class="font-medium text-slate-800">
                                            {{ \Illuminate\Support\Carbon::parse($borrowing->return_date)->format('d M Y') }}
                                        </div>
                                        <div class="text-xs text-emerald-600 font-medium">Sudah diterima</div>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium text-slate-400 bg-slate-50 border border-slate-200/50">
                                            Belum kembali
                                        </span>
                                    @endif
                                </td>

                                {{-- Badge Status Dinamis --}}
                                <td class="py-4 px-4 whitespace-nowrap">
                                    @if($borrowing->status === 'dipinjam')
                                        @if($isOverdue)
                                            {{-- Merah menyala: "Terlambat X Hari - Denda Rp Y" --}}
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-rose-500 text-white shadow-sm shadow-rose-200 ring-2 ring-rose-400/30">
                                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                                Terlambat {{ $daysOverdue }} Hari - Denda Rp {{ number_format($calculatedFine, 0, ',', '.') }}
                                            </span>
                                        @else
                                            {{-- Hijau: "Dipinjam - Sisa X Hari" --}}
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Dipinjam - Sisa {{ $daysRemaining }} Hari
                                            </span>
                                        @endif
                                    @else
                                        {{-- Abu-abu/Biru: "Sudah Dikembalikan" --}}
                                        <div class="flex flex-col items-start gap-1">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                Sudah Dikembalikan
                                            </span>
                                            @if($borrowing->fine_amount > 0)
                                                <span class="text-[11px] font-semibold text-rose-600 pl-1">
                                                    Denda Tercatat: Rp {{ number_format($borrowing->fine_amount, 0, ',', '.') }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 px-4 text-center">
                                    <div class="max-w-sm mx-auto text-center space-y-3">
                                        <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center text-2xl text-slate-400">
                                            📭
                                        </div>
                                        <h3 class="text-sm font-semibold text-slate-800">Tidak Ada Data Peminjaman</h3>
                                        <p class="text-xs text-slate-500">
                                            @if(!empty($search) || $statusFilter !== 'semua')
                                                Tidak ditemukan peminjaman yang cocok dengan filter atau pencarian Anda.
                                            @else
                                                Anda belum memiliki riwayat peminjaman buku di perpustakaan.
                                            @endif
                                        </p>
                                        @if(!empty($search) || $statusFilter !== 'semua')
                                            <button
                                                type="button"
                                                wire:click="$set('search', ''); $set('statusFilter', 'semua')"
                                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700 underline">
                                                Reset filter & pencarian
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Links --}}
            @if(method_exists($borrowings, 'hasPages') && $borrowings->hasPages())
                <div class="p-4 border-t border-slate-200 bg-slate-50">
                    {{ $borrowings->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
