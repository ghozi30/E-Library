<?php

use App\Models\Borrowing;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403);
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function confirmReturn($id)
    {
        $borrowing = Borrowing::with('book')->findOrFail($id);

        if ($borrowing->status !== 'dipinjam') {
            session()->flash('error', 'Transaksi peminjaman ini sudah dikembalikan sebelumnya.');
            return;
        }

        // Hitung denda final jika terlambat (Rp 2.000 x hari terlambat jika now() melewati due_date)
        $dueDate = Carbon::parse($borrowing->due_date)->startOfDay();
        $today = now()->startOfDay();
        $lateDays = $today->greaterThan($dueDate) ? (int) $dueDate->diffInDays($today) : 0;
        $finalFine = $lateDays * 2000;

        // Update status, return_date, fine_amount
        $borrowing->update([
            'status' => 'dikembalikan',
            'return_date' => now(),
            'fine_amount' => $finalFine,
        ]);

        // Tambahkan kembali stok buku
        if ($borrowing->book) {
            $borrowing->book->increment('stock');
        }

        $msg = "Pengembalian buku '{$borrowing->book?->title}' berhasil dikonfirmasi.";
        if ($finalFine > 0) {
            $msg .= " Denda keterlambatan ({$lateDays} hari): Rp " . number_format($finalFine, 0, ',', '.') . '.';
        } else {
            $msg .= " Buku dikembalikan tepat waktu.";
        }

        session()->flash('success', $msg);
    }

    public function render()
    {
        $borrowings = Borrowing::with(['user', 'book.category'])
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->where('borrow_code', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($uQuery) {
                            $uQuery->where('name', 'like', '%' . $this->search . '%')
                                   ->orWhere('nis', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('book', function ($bQuery) {
                            $bQuery->where('title', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->latest('id')
            ->paginate(10);

        return $this->view([
            'borrowings' => $borrowings,
        ]);
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                    Sirkulasi & Pengembalian
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Data Peminjaman Siswa</h1>
            <p class="text-slate-500 text-sm mt-0.5">Kelola sirkulasi peminjaman, pantau jatuh tempo, perhitungan denda otomatis, dan konfirmasi pengembalian 1-klik.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}" wire:navigate
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs sm:text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
                ← Kembali ke Dashboard
            </a>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session()->has('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" wire:click="$set('success', null)" class="text-emerald-600 hover:text-emerald-900 text-xs font-bold">×</button>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" wire:click="$set('error', null)" class="text-rose-600 hover:text-rose-900 text-xs font-bold">×</button>
        </div>
    @endif

    {{-- Toolbar Filter & Pencarian Live --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col md:flex-row items-center justify-between gap-3">
        {{-- Live Search --}}
        <div class="relative w-full md:w-96">
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Cari kode transaksi, nama siswa, atau NIS..."
                   class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder:text-slate-400">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>

        {{-- Filter Status Peminjaman --}}
        <div class="flex items-center gap-2 w-full md:w-auto">
            <label for="statusFilter" class="text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Status:</label>
            <select id="statusFilter" wire:model.live="statusFilter"
                    class="w-full md:w-48 px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Semua Status</option>
                <option value="dipinjam">Sedang Dipinjam</option>
                <option value="dikembalikan">Sudah Dikembalikan</option>
            </select>
        </div>
    </div>

    {{-- Tabel Sirkulasi & Konfirmasi Pengembalian --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50/80 text-xs font-semibold uppercase text-slate-500 tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Kode Transaksi</th>
                        <th class="px-5 py-3.5">Siswa / Anggota</th>
                        <th class="px-5 py-3.5">Judul Buku & Rak</th>
                        <th class="px-5 py-3.5">Masa Pinjam</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Denda (Rp 2.000/hr)</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($borrowings as $borrowing)
                        @php
                            $dueDate = $borrowing->due_date ? Carbon::parse($borrowing->due_date)->startOfDay() : null;
                            $today = now()->startOfDay();
                            $isOverdue = false;
                            $lateDays = 0;
                            $runningFine = 0;

                            if ($borrowing->status === 'dipinjam' && $dueDate) {
                                if ($today->greaterThan($dueDate)) {
                                    $isOverdue = true;
                                    $lateDays = (int) $dueDate->diffInDays($today);
                                    $runningFine = $lateDays * 2000;
                                }
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors {{ $isOverdue ? 'bg-rose-50/30' : '' }}">
                            {{-- Kode Pinjam --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="font-mono font-bold text-xs text-indigo-600 bg-indigo-50 px-2 py-1 rounded-md border border-indigo-200/50">
                                    {{ $borrowing->borrow_code }}
                                </span>
                            </td>

                            {{-- Peminjam (Nama + NIS + Kelas) --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="font-bold text-slate-900">{{ $borrowing->user->name ?? 'Pengguna Dihapus' }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    NIS: <span class="font-medium text-slate-700">{{ $borrowing->user->nis ?? '-' }}</span>
                                    • {{ $borrowing->user->class_name ?? '-' }}
                                </div>
                            </td>

                            {{-- Buku & Rak --}}
                            <td class="px-5 py-4">
                                <div class="font-medium text-slate-900 max-w-xs truncate" title="{{ $borrowing->book->title ?? '-' }}">
                                    {{ $borrowing->book->title ?? 'Buku Dihapus' }}
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    Kategori: <span class="text-slate-600">{{ $borrowing->book->category->name ?? '-' }}</span>
                                    <span class="font-mono text-indigo-600 font-medium">({{ $borrowing->book->category->rack_code ?? '-' }})</span>
                                </div>
                            </td>

                            {{-- Masa Pinjam: Tanggal Pinjam & Jatuh Tempo --}}
                            <td class="px-5 py-4 whitespace-nowrap text-xs">
                                <div><span class="text-slate-400">Pinjam:</span> <span class="font-medium text-slate-700">{{ $borrowing->borrow_date ? $borrowing->borrow_date->format('d M Y') : '-' }}</span></div>
                                <div class="mt-0.5">
                                    <span class="text-slate-400">Tempo:</span>
                                    <span class="font-semibold {{ $isOverdue ? 'text-rose-600' : 'text-slate-800' }}">
                                        {{ $borrowing->due_date ? $borrowing->due_date->format('d M Y') : '-' }}
                                    </span>
                                </div>
                            </td>

                            {{-- Status Sirkulasi --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($borrowing->status === 'dipinjam')
                                    @if($isOverdue)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                                            Terlambat {{ $lateDays }} Hari
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            Dipinjam
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Dikembalikan
                                    </span>
                                    @if($borrowing->return_date)
                                        <div class="text-[11px] text-slate-400 mt-0.5">
                                            {{ $borrowing->return_date->format('d M Y') }}
                                        </div>
                                    @endif
                                @endif
                            </td>

                            {{-- Denda Berjalan / Denda Final --}}
                            <td class="px-5 py-4 whitespace-nowrap text-xs">
                                @if($borrowing->status === 'dipinjam')
                                    @if($isOverdue)
                                        <div class="font-bold text-rose-600 text-sm">
                                            Rp {{ number_format($runningFine, 0, ',', '.') }}
                                        </div>
                                        <div class="text-[11px] text-rose-500 font-medium">
                                            Estimasi denda berjalan
                                        </div>
                                    @else
                                        <span class="text-emerald-600 font-medium bg-emerald-50 px-2 py-0.5 rounded text-xs">
                                            Rp 0 (Tepat Waktu)
                                        </span>
                                    @endif
                                @else
                                    @if($borrowing->fine_amount > 0)
                                        <div class="font-bold text-slate-800">
                                            Rp {{ number_format($borrowing->fine_amount, 0, ',', '.') }}
                                        </div>
                                        <div class="text-[11px] text-slate-400">Denda diselesaikan</div>
                                    @else
                                        <span class="text-slate-400 font-normal">Rp 0 (Tanpa Denda)</span>
                                    @endif
                                @endif
                            </td>

                            {{-- Aksi: Konfirmasi Pengembalian 1-Klik --}}
                            <td class="px-5 py-4 whitespace-nowrap text-right text-xs">
                                @if($borrowing->status === 'dipinjam')
                                    <button type="button"
                                            wire:click="confirmReturn({{ $borrowing->id }})"
                                            wire:confirm="Konfirmasi pengembalian buku '{{ $borrowing->book?->title }}' oleh {{ $borrowing->user?->name }}? Stok buku akan ditambah 1{{ $isOverdue ? ' dan denda Rp ' . number_format($runningFine, 0, ',', '.') . ' akan dicatat' : '' }}."
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-xs">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Konfirmasi Kembali
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1 text-slate-400 font-medium text-xs">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Selesai
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <p class="font-medium text-slate-500">Tidak ada riwayat atau transaksi peminjaman.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($borrowings->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $borrowings->links() }}
            </div>
        @endif
    </div>
</div>
