<?php

use App\Models\Borrowing;
use Carbon\Carbon;
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
        $borrowing = Borrowing::with('book')->findOrFail($id);

        if ($borrowing->status === 'dipinjam') {
            $today = now()->startOfDay();
            $dueDate = Carbon::parse($borrowing->due_date)->startOfDay();
            $daysLate = $today->greaterThan($dueDate) ? (int) $dueDate->diffInDays($today) : 0;
            $finalFine = $daysLate * 2000;

            $borrowing->update([
                'status' => 'dikembalikan',
                'return_date' => now(),
                'fine_amount' => $finalFine,
            ]);

            $borrowing->book?->increment('stock');

            $msg = "Pengembalian buku '{$borrowing->book?->title}' berhasil dikonfirmasi.";
            if ($finalFine > 0) {
                $msg .= " Keterlambatan {$daysLate} hari, total denda: Rp ".number_format($finalFine, 0, ',', '.').'.';
            } else {
                $msg .= ' Pengembalian tepat waktu tanpa denda.';
            }

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

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="/admin/dashboard" wire:navigate class="text-xs font-semibold text-slate-400 hover:text-indigo-600 transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Dashboard
                </a>
                <span class="text-xs text-slate-300">/</span>
                <span class="text-xs font-semibold text-indigo-600">Peminjaman</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Data Peminjaman & Sirkulasi</h1>
            <p class="text-sm text-slate-500 mt-0.5">Kelola transaksi peminjaman siswa, pantau denda berjalan, dan lakukan konfirmasi pengembalian buku 1-klik.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1 text-xs font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                Dipinjam: {{ $this->totalDipinjamCount }}
            </span>
            <span class="px-3 py-1 text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                Dikembalikan: {{ $this->totalDikembalikanCount }}
            </span>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    @if(session()->has('message'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- Toolbar: Search & Filter Status -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
        <!-- Live Search Input -->
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari kode pinjam (misal: PJM-...), nama siswa, atau NIS..."
                class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none transition"
            >
            @if($search)
                <button
                    type="button"
                    wire:click="$set('search', '')"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            @endif
        </div>

        <!-- Filter Status (Semua, dipinjam, dikembalikan) -->
        <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl text-xs font-semibold">
            <button
                type="button"
                wire:click="$set('statusFilter', 'Semua')"
                class="px-3 py-1.5 rounded-lg transition {{ $statusFilter === 'Semua' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
            >
                Semua
            </button>
            <button
                type="button"
                wire:click="$set('statusFilter', 'dipinjam')"
                class="px-3 py-1.5 rounded-lg transition {{ $statusFilter === 'dipinjam' ? 'bg-white text-amber-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
            >
                Dipinjam
            </button>
            <button
                type="button"
                wire:click="$set('statusFilter', 'dikembalikan')"
                class="px-3 py-1.5 rounded-lg transition {{ $statusFilter === 'dikembalikan' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}"
            >
                Dikembalikan
            </button>
        </div>
    </div>

    <!-- Tabel Data Peminjaman -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">Transaksi</th>
                        <th class="py-3.5 px-6">Siswa Peminjam</th>
                        <th class="py-3.5 px-6">Buku</th>
                        <th class="py-3.5 px-6">Jadwal Pinjam</th>
                        <th class="py-3.5 px-6">Status & Denda</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($this->borrowings as $borrowing)
                        @php
                            $today = now()->startOfDay();
                            $dueDate = \Carbon\Carbon::parse($borrowing->due_date)->startOfDay();
                            $isOverdue = $borrowing->status === 'dipinjam' && $today->greaterThan($dueDate);
                            $lateDays = $isOverdue ? (int) $dueDate->diffInDays($today) : 0;
                            $runningFine = $lateDays * 2000;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- Transaksi / Kode Pinjam -->
                            <td class="py-4 px-6 whitespace-nowrap">
                                <div class="font-mono text-xs font-bold text-indigo-700 bg-indigo-50/80 px-2 py-0.5 rounded-md inline-block border border-indigo-100">
                                    {{ $borrowing->borrow_code }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-1">
                                    {{ $borrowing->created_at?->diffForHumans() }}
                                </div>
                            </td>

                            <!-- Siswa Peminjam -->
                            <td class="py-4 px-6">
                                <div class="font-bold text-slate-900">{{ $borrowing->user?->name ?? 'Pengguna Dihapus' }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    NIS: <span class="font-medium text-slate-700">{{ $borrowing->user?->nis ?? '-' }}</span>
                                    @if($borrowing->user?->class_name)
                                        <span class="text-slate-300">•</span>
                                        <span class="text-slate-500">{{ $borrowing->user->class_name }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Buku -->
                            <td class="py-4 px-6 max-w-xs">
                                <div class="font-semibold text-slate-900 leading-snug line-clamp-1" title="{{ $borrowing->book?->title }}">
                                    {{ $borrowing->book?->title ?? 'Buku Dihapus' }}
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    {{ $borrowing->book?->author }}
                                </div>
                            </td>

                            <!-- Jadwal Pinjam -->
                            <td class="py-4 px-6 whitespace-nowrap text-xs">
                                <div class="text-slate-600">
                                    Pinjam: <span class="font-medium text-slate-800">{{ $borrowing->borrow_date?->translatedFormat('d M Y') ?? $borrowing->borrow_date }}</span>
                                </div>
                                <div class="mt-0.5">
                                    Tempo:
                                    <span class="font-bold {{ $isOverdue ? 'text-rose-600' : 'text-slate-800' }}">
                                        {{ $borrowing->due_date?->translatedFormat('d M Y') ?? $borrowing->due_date }}
                                    </span>
                                </div>
                                @if($borrowing->return_date)
                                    <div class="text-emerald-700 font-medium mt-0.5">
                                        Kembali: {{ $borrowing->return_date?->translatedFormat('d M Y') ?? $borrowing->return_date }}
                                    </div>
                                @endif
                            </td>

                            <!-- Status & Denda -->
                            <td class="py-4 px-6 whitespace-nowrap">
                                @if($borrowing->status === 'dipinjam')
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            Dipinjam
                                        </span>
                                        @if($isOverdue)
                                            <div class="text-xs font-bold text-rose-600 flex items-center gap-1">
                                                <span>Estimasi Denda:</span>
                                                <span class="px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200">
                                                    Rp {{ number_format($runningFine, 0, ',', '.') }}
                                                </span>
                                            </div>
                                            <span class="text-[10px] text-rose-500 font-medium">(Terlambat {{ $lateDays }} hari @ Rp 2.000/hari)</span>
                                        @else
                                            <span class="text-[11px] text-slate-400">Belum jatuh tempo</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Dikembalikan
                                        </span>
                                        @if($borrowing->fine_amount > 0)
                                            <div class="text-xs text-rose-600 font-semibold">
                                                Denda: Rp {{ number_format($borrowing->fine_amount, 0, ',', '.') }}
                                            </div>
                                        @else
                                            <span class="text-[11px] text-emerald-600 font-medium">Bebas denda</span>
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
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition shadow-sm shadow-indigo-100 disabled:opacity-50"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Konfirmasi Kembali
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs text-slate-400 font-medium">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Selesai
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                    <p class="text-sm font-medium">Tidak ada data peminjaman yang ditemukan.</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Sesuaikan kata kunci pencarian atau ubah filter status di atas.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->borrowings->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $this->borrowings->links() }}
            </div>
        @endif
    </div>
</div>
