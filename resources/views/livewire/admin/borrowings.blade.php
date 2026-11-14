<?php

use App\Models\Borrowing;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'Semua';

    public function mount(): void
    {
        if (! auth()->check() || auth()->user()->role !== 'admin') {
            abort(403);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function confirmReturn(int $id): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);

        $msg = null;

        DB::transaction(function () use ($id, &$msg): void {
            $borrowing = Borrowing::with('book')
                ->lockForUpdate()
                ->findOrFail($id);

            if ($borrowing->status !== 'dipinjam') {
                return;
            }

            $today = now()->startOfDay();
            $dueDate = Carbon::parse($borrowing->due_date)->startOfDay();
            $daysLate = $today->greaterThan($dueDate) ? (int) $dueDate->diffInDays($today) : 0;
            $finalFine = max((int) $borrowing->fine_amount, $daysLate * 2000);

            $borrowing->update([
                'status' => 'dikembalikan',
                'return_date' => now()->toDateString(),
                'fine_amount' => $finalFine,
            ]);

            $borrowing->book?->increment('stock');

            $msg = "Pengembalian buku '{$borrowing->book?->title}' berhasil dikonfirmasi.";
            if ($finalFine > 0) {
                $msg .= " Keterlambatan {$daysLate} hari, total denda: Rp ".number_format($finalFine, 0, ',', '.').'.';
            } else {
                $msg .= ' Pengembalian tepat waktu tanpa denda.';
            }
        });

        if ($msg !== null) {
            session()->flash('message', $msg);
        }
    }

    #[Computed]
    public function borrowings()
    {
        return Borrowing::with(['user', 'book'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('borrow_code', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', function ($uq) {
                            $uq->where('name', 'like', '%'.$this->search.'%')
                                ->orWhere('nis', 'like', '%'.$this->search.'%');
                        })
                        ->orWhereHas('book', function ($bq) {
                            $bq->where('title', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->statusFilter !== 'Semua', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function totalDipinjamCount(): int
    {
        return Borrowing::where('status', 'dipinjam')->count();
    }

    #[Computed]
    public function totalDikembalikanCount(): int
    {
        return Borrowing::where('status', 'dikembalikan')->count();
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 space-y-6">
    <!-- Header Editorial -->
    <div class="border-b border-stone-200/80 pb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="/admin/dashboard" wire:navigate class="text-xs font-semibold text-stone-500 hover:text-[#144533] transition inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Dashboard
                </a>
                <span class="text-xs text-stone-300">/</span>
                <span class="text-xs font-semibold text-[#144533]">Peminjaman</span>
            </div>
            <div class="font-mono text-[11px] uppercase tracking-[0.14em] text-stone-500 mt-2">
                MEJA PUSTAKAWAN / BUKU CATATAN SIRKULASI
            </div>
            <h1 class="font-serif text-3xl sm:text-4xl font-semibold text-stone-900 tracking-tight mt-1">
                Data Peminjaman &amp; Sirkulasi
            </h1>
            <p class="text-sm text-stone-600 mt-1">
                Kelola transaksi peminjaman siswa, pantau denda berjalan, dan lakukan konfirmasi pengembalian buku 1-klik.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <span class="px-3.5 py-1.5 text-xs font-mono font-semibold rounded-lg bg-amber-50 text-amber-800 border border-amber-200 tabular-nums">
                Dipinjam: {{ $this->totalDipinjamCount }}
            </span>
            <span class="px-3.5 py-1.5 text-xs font-mono font-semibold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 tabular-nums">
                Dikembalikan: {{ $this->totalDikembalikanCount }}
            </span>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    @if(session()->has('message'))
        <div role="status" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium">{{ session('message') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" aria-label="Tutup notifikasi" class="text-emerald-700/70 hover:text-emerald-900 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- Toolbar: Search & Filter Status -->
    <div class="bg-white p-4 rounded-2xl border border-stone-200/80 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
        <!-- Live Search Input -->
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                autocomplete="off"
                placeholder="Cari kode pinjam (misal: PJM-...), nama siswa, atau NIS..."
                class="w-full pl-10 pr-9 py-2.5 text-sm bg-[#FAF8F5] rounded-xl border border-stone-300 text-stone-900 placeholder-stone-400 focus:bg-white focus:border-[#144533] focus:ring-2 focus:ring-[#144533]/20 focus:outline-none transition"
            >
            @if($search)
                <button
                    type="button"
                    wire:click="$set('search', '')"
                    aria-label="Hapus kata kunci"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-400 hover:text-stone-700 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            @endif
        </div>

        <!-- Filter Status (Semua, dipinjam, dikembalikan) -->
        <div class="flex items-center justify-center sm:justify-start gap-1 p-1 bg-[#FAF8F5] border border-stone-200/80 rounded-xl text-xs font-semibold w-full md:w-auto overflow-x-auto shrink-0">
            <button
                type="button"
                wire:click="$set('statusFilter', 'Semua')"
                class="flex-1 sm:flex-none text-center px-3.5 py-2 rounded-lg transition cursor-pointer {{ $statusFilter === 'Semua' ? 'bg-[#144533] text-white shadow-sm' : 'text-stone-600 hover:text-stone-900' }}"
            >
                Semua
            </button>
            <button
                type="button"
                wire:click="$set('statusFilter', 'dipinjam')"
                class="flex-1 sm:flex-none text-center px-3.5 py-2 rounded-lg transition cursor-pointer {{ $statusFilter === 'dipinjam' ? 'bg-[#144533] text-white shadow-sm' : 'text-stone-600 hover:text-stone-900' }}"
            >
                Dipinjam
            </button>
            <button
                type="button"
                wire:click="$set('statusFilter', 'dikembalikan')"
                class="flex-1 sm:flex-none text-center px-3.5 py-2 rounded-lg transition cursor-pointer {{ $statusFilter === 'dikembalikan' ? 'bg-[#144533] text-white shadow-sm' : 'text-stone-600 hover:text-stone-900' }}"
            >
                Dikembalikan
            </button>
        </div>
    </div>

    <!-- Daftar Data Peminjaman: Card Grid (HP & Tablet) + Tabel (Desktop) -->
    <div class="bg-white rounded-2xl border border-stone-200/80 shadow-sm overflow-hidden">
        <!-- Mobile & Tablet Card View (< 1280px) -->
        <div class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
            @forelse($this->borrowings as $borrowing)
                @php
                    $today = now()->startOfDay();
                    $dueDate = \Carbon\Carbon::parse($borrowing->due_date)->startOfDay();
                    $isOverdue = $borrowing->status === 'dipinjam' && $today->greaterThan($dueDate);
                    $lateDays = $isOverdue ? (int) $dueDate->diffInDays($today) : 0;
                    $runningFine = max((int) $borrowing->fine_amount, $lateDays * 2000);
                @endphp
                <div wire:key="borrow-card-{{ $borrowing->id }}" class="rounded-xl border border-stone-200/90 bg-[#FAF8F5]/60 p-4 flex flex-col justify-between gap-3.5 min-w-0">
                    <div class="space-y-2.5 min-w-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-mono text-xs font-semibold text-[#144533] bg-[#144533]/5 border border-[#144533]/20 px-2.5 py-0.5 rounded-md">
                                {{ $borrowing->borrow_code }}
                            </span>
                            @if($borrowing->status === 'dipinjam')
                                @if($isOverdue)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                        Terlambat {{ $lateDays }} hari
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                        Dipinjam
                                    </span>
                                @endif
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Dikembalikan
                                </span>
                            @endif
                        </div>

                        <div class="min-w-0">
                            <div class="font-serif text-base font-semibold text-stone-900 leading-snug [overflow-wrap:anywhere]">
                                {{ $borrowing->book?->title ?? 'Buku Dihapus' }}
                            </div>
                            <div class="text-xs text-stone-500 mt-0.5 [overflow-wrap:anywhere]">
                                {{ $borrowing->book?->author ?? '-' }}
                            </div>
                        </div>

                        <div class="pt-2 border-t border-stone-200/70 text-xs">
                            <div class="font-semibold text-stone-900 [overflow-wrap:anywhere]">{{ $borrowing->user?->name ?? 'Pengguna Dihapus' }}</div>
                            <div class="font-mono text-[11px] text-stone-500 mt-0.5">
                                NIS: {{ $borrowing->user?->nis ?? '-' }}
                                @if($borrowing->user?->class_name)
                                    &bull; <span class="font-sans">{{ $borrowing->user->class_name }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs bg-white rounded-lg p-2.5 border border-stone-200/70">
                            <div>
                                <div class="text-[10px] font-mono uppercase text-stone-400">Pinjam</div>
                                <div class="font-mono font-medium text-stone-800 mt-0.5">{{ $borrowing->borrow_date?->translatedFormat('d M Y') ?? $borrowing->borrow_date }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] font-mono uppercase text-stone-400">Jatuh Tempo</div>
                                <div class="font-mono font-semibold mt-0.5 {{ $isOverdue ? 'text-red-700' : 'text-stone-800' }}">{{ $borrowing->due_date?->translatedFormat('d M Y') ?? $borrowing->due_date }}</div>
                            </div>
                            @if($borrowing->return_date)
                                <div class="col-span-2 pt-1 border-t border-stone-100 text-emerald-700 font-medium">
                                    Kembali: <span class="font-mono">{{ $borrowing->return_date?->translatedFormat('d M Y') ?? $borrowing->return_date }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="pt-2 border-t border-stone-200/70 flex flex-wrap items-center justify-between gap-2">
                        @if($borrowing->status === 'dipinjam')
                            @if($isOverdue)
                                <div class="font-mono text-xs font-semibold text-red-700">
                                    Denda: Rp {{ number_format($runningFine, 0, ',', '.') }}
                                </div>
                            @else
                                <span class="text-xs text-stone-500">Belum jatuh tempo</span>
                            @endif

                            <button
                                type="button"
                                wire:click="confirmReturn({{ $borrowing->id }})"
                                wire:confirm="Konfirmasi pengembalian buku '{{ $borrowing->book?->title }}'? {{ $isOverdue ? 'Denda keterlambatan sebesar Rp ' . number_format($runningFine, 0, ',', '.') . ' akan dicatat final.' : 'Buku dikembalikan tepat waktu.' }}"
                                wire:loading.attr="disabled"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 bg-[#144533] hover:bg-[#0F3325] text-white px-4 py-2 rounded-lg text-xs font-semibold transition shadow-sm disabled:opacity-50 cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Konfirmasi Kembali
                            </button>
                        @else
                            @if($borrowing->fine_amount > 0)
                                <div class="font-mono text-xs text-red-700 font-semibold">
                                    Denda: Rp {{ number_format($borrowing->fine_amount, 0, ',', '.') }}
                                </div>
                            @else
                                <span class="text-xs text-emerald-700 font-medium">Bebas denda</span>
                            @endif
                            <span class="inline-flex items-center gap-1 text-xs text-stone-500 font-medium">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Selesai
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="md:col-span-2 py-12 px-4 text-center text-stone-500">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-12 h-12 rounded-full bg-stone-100 text-stone-400 border border-stone-200 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                        <p class="font-serif text-base font-semibold text-stone-800">Tidak ada data peminjaman yang ditemukan.</p>
                        <p class="text-xs text-stone-500 mt-0.5">Sesuaikan kata kunci pencarian atau ubah filter status di atas.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Desktop Table View (>= 1280px) -->
        <div class="hidden xl:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#FAF8F5] border-b border-stone-200 text-[11px] font-mono font-semibold uppercase tracking-wider text-stone-500">
                        <th class="py-3.5 px-6">Transaksi</th>
                        <th class="py-3.5 px-6">Siswa Peminjam</th>
                        <th class="py-3.5 px-6">Buku</th>
                        <th class="py-3.5 px-6">Jadwal Pinjam</th>
                        <th class="py-3.5 px-6">Status &amp; Denda</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200/70 text-sm">
                    @forelse($this->borrowings as $borrowing)
                        @php
                            $today = now()->startOfDay();
                            $dueDate = \Carbon\Carbon::parse($borrowing->due_date)->startOfDay();
                            $isOverdue = $borrowing->status === 'dipinjam' && $today->greaterThan($dueDate);
                            $lateDays = $isOverdue ? (int) $dueDate->diffInDays($today) : 0;
                            $runningFine = $lateDays * 2000;
                        @endphp
                        <tr class="hover:bg-[#FAF8F5]/80 transition-colors">
                            <!-- Transaksi / Kode Pinjam -->
                            <td class="py-4 px-6 whitespace-nowrap">
                                <div class="font-mono text-xs font-semibold text-[#144533] bg-[#144533]/5 border border-[#144533]/20 px-2.5 py-1 rounded-md inline-block">
                                    {{ $borrowing->borrow_code }}
                                </div>
                                <div class="text-[11px] text-stone-500 mt-1">
                                    {{ $borrowing->created_at?->diffForHumans() }}
                                </div>
                            </td>

                            <!-- Siswa Peminjam -->
                            <td class="py-4 px-6">
                                <div class="font-semibold text-stone-900">{{ $borrowing->user?->name ?? 'Pengguna Dihapus' }}</div>
                                <div class="font-mono text-xs text-stone-500 mt-0.5">
                                    NIS: <span class="font-medium text-stone-700">{{ $borrowing->user?->nis ?? '-' }}</span>
                                    @if($borrowing->user?->class_name)
                                        <span class="text-stone-300">&bull;</span>
                                        <span class="font-sans text-stone-500">{{ $borrowing->user->class_name }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Buku -->
                            <td class="py-4 px-6 max-w-xs">
                                <div class="font-serif text-base font-semibold text-stone-900 leading-snug line-clamp-1" title="{{ $borrowing->book?->title }}">
                                    {{ $borrowing->book?->title ?? 'Buku Dihapus' }}
                                </div>
                                <div class="text-xs text-stone-500 mt-0.5">
                                    {{ $borrowing->book?->author }}
                                </div>
                            </td>

                            <!-- Jadwal Pinjam -->
                            <td class="py-4 px-6 whitespace-nowrap text-xs">
                                <div class="text-stone-600">
                                    Pinjam: <span class="font-mono font-medium text-stone-800">{{ $borrowing->borrow_date?->translatedFormat('d M Y') ?? $borrowing->borrow_date }}</span>
                                </div>
                                <div class="mt-0.5 text-stone-600">
                                    Tempo:
                                    <span class="font-mono font-semibold {{ $isOverdue ? 'text-red-700' : 'text-stone-800' }}">
                                        {{ $borrowing->due_date?->translatedFormat('d M Y') ?? $borrowing->due_date }}
                                    </span>
                                </div>
                                @if($borrowing->return_date)
                                    <div class="text-emerald-700 font-medium mt-0.5">
                                        Kembali: <span class="font-mono">{{ $borrowing->return_date?->translatedFormat('d M Y') ?? $borrowing->return_date }}</span>
                                    </div>
                                @endif
                            </td>

                            <!-- Status & Denda -->
                            <td class="py-4 px-6 whitespace-nowrap">
                                @if($borrowing->status === 'dipinjam')
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                            Dipinjam
                                        </span>
                                        @if($isOverdue)
                                            <div class="text-xs font-semibold text-red-700 flex items-center gap-1.5 mt-0.5">
                                                <span>Denda:</span>
                                                <span class="font-mono px-2 py-0.5 rounded bg-red-50 text-red-700 border border-red-200">
                                                    Rp {{ number_format($runningFine, 0, ',', '.') }}
                                                </span>
                                            </div>
                                            <span class="font-mono text-[10px] text-red-600 font-medium">(Terlambat {{ $lateDays }} hari @ Rp 2.000/hari)</span>
                                        @else
                                            <span class="text-[11px] text-stone-500">Belum jatuh tempo</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Dikembalikan
                                        </span>
                                        @if($borrowing->fine_amount > 0)
                                            <div class="font-mono text-xs text-red-700 font-semibold mt-0.5">
                                                Denda: Rp {{ number_format($borrowing->fine_amount, 0, ',', '.') }}
                                            </div>
                                        @else
                                            <span class="text-[11px] text-emerald-700 font-medium">Bebas denda</span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <!-- Aksi Konfirmasi 1-Klik -->
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                @if($borrowing->status === 'dipinjam')
                                    <button
                                        type="button"
                                        wire:click="confirmReturn({{ $borrowing->id }})"
                                        wire:confirm="Konfirmasi pengembalian buku '{{ $borrowing->book?->title }}'? {{ $isOverdue ? 'Denda keterlambatan sebesar Rp ' . number_format($runningFine, 0, ',', '.') . ' akan dicatat final.' : 'Buku dikembalikan tepat waktu.' }}"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-1.5 bg-[#144533] hover:bg-[#0F3325] text-white px-4 py-2 rounded-lg text-xs font-medium transition shadow-sm disabled:opacity-50 cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Konfirmasi Kembali
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs text-stone-500 font-medium">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Selesai
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 px-6 text-center text-stone-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-full bg-stone-100 text-stone-400 border border-stone-200 flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                        </svg>
                                    </div>
                                    <p class="font-serif text-base font-semibold text-stone-800">Tidak ada data peminjaman yang ditemukan.</p>
                                    <p class="text-xs text-stone-500 mt-0.5">Sesuaikan kata kunci pencarian atau ubah filter status di atas.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->borrowings->hasPages())
            <div class="p-4 border-t border-stone-200/80 bg-[#FAF8F5]">
                {{ $this->borrowings->links() }}
            </div>
        @endif
    </div>
</div>
