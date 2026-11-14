<?php

use App\Models\Book;
use App\Models\Bookmark;
use App\Models\Borrowing;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Book $book;

    public int $duration = 7;

    public function mount(?string $id = null): void
    {
        if (! isset($this->book)) {
            $this->book = Book::findOrFail($id);
        }
    }

    #[Computed]
    public function isBookmarked(): bool
    {
        return auth()->check() && Bookmark::where('user_id', auth()->id())
            ->where('book_id', $this->book->id)->exists();
    }

    public function toggleBookmark(): void
    {
        abort_unless(auth()->check(), 403);

        DB::transaction(function (): void {
            Book::whereKey($this->book->id)->lockForUpdate()->firstOrFail();

            $bookmarks = Bookmark::where('user_id', auth()->id())
                ->where('book_id', $this->book->id);

            if ($bookmarks->exists()) {
                $bookmarks->delete();
            } else {
                Bookmark::create([
                    'user_id' => auth()->id(),
                    'book_id' => $this->book->id,
                ]);
            }
        });

        unset($this->isBookmarked);
    }

    public function borrowBook(): void
    {
        abort_unless(auth()->check(), 403);
        session()->forget('borrow_success');
        $this->resetValidation();
        $this->validate(['duration' => ['required', 'integer', 'in:3,7,14']]);

        $borrowCode = DB::transaction(function (): string {
            $this->book = Book::whereKey($this->book->id)->lockForUpdate()->firstOrFail();

            if ($this->book->stock <= 0) {
                throw ValidationException::withMessages(['borrowing' => 'Stok buku sudah habis.']);
            }

            if (Borrowing::where('user_id', auth()->id())->where('book_id', $this->book->id)
                ->where('status', 'dipinjam')->exists()) {
                throw ValidationException::withMessages(['borrowing' => 'Anda masih meminjam buku ini.']);
            }

            do {
                $borrowCode = 'PJM-'.rand(1000, 9999);
            } while (Borrowing::where('borrow_code', $borrowCode)->exists());

            Borrowing::create([
                'borrow_code' => $borrowCode,
                'user_id' => auth()->id(),
                'book_id' => $this->book->id,
                'borrow_date' => now(),
                'due_date' => now()->addDays($this->duration),
                'status' => 'dipinjam',
            ]);

            $this->book->decrement('stock');

            return $borrowCode;
        });

        session()->flash('borrow_success', 'Buku berhasil dipinjam. Kode pinjam: '.$borrowCode);
    }
};
?>

<div class="bg-[#FAF8F5] border border-stone-200/80 rounded-xl p-5 space-y-4">
    <!-- Top Reactive Stock & Circulation Header Row -->
    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-stone-200/80">
        <span class="font-mono text-[11px] uppercase tracking-[0.12em] text-stone-500">
            Sirkulasi &amp; Ketersediaan Koleksi
        </span>

        @if ($book->stock > 0)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-mono font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-600" aria-hidden="true"></span>
                Tersedia: {{ $book->stock }} Buku
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-mono font-semibold bg-red-50 text-red-700 border border-red-200">
                <span class="w-2 h-2 rounded-full bg-red-600" aria-hidden="true"></span>
                Stok Habis Dipinjam
            </span>
        @endif
    </div>

    @guest
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
            <p class="text-sm text-stone-600 leading-relaxed">
                Silakan masuk menggunakan akun siswa untuk meminjam buku atau menyimpan ke daftar favorit.
            </p>
            <a
                href="{{ route('login') }}"
                wire:navigate
                class="inline-flex items-center justify-center gap-2 bg-[#144533] hover:bg-[#0F3325] text-white px-5 py-2.5 rounded-lg text-sm font-medium transition shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#144533]"
            >
                Masuk untuk Meminjam atau Menyimpan Buku
            </a>
        </div>
    @endguest

    @auth
        @if (session('borrow_success'))
            @php
                $successMessage = session('borrow_success');
                $borrowCodeMatch = null;
                if (preg_match('/(PJM-[A-Z0-9-]+)/', $successMessage, $matches)) {
                    $borrowCodeMatch = $matches[1];
                }
            @endphp
            <div role="status" class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-emerald-800 flex flex-wrap items-center justify-between gap-2 text-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ $successMessage }}</span>
                </div>
                @if ($borrowCodeMatch)
                    <span class="font-mono text-xs font-semibold px-2.5 py-1 rounded bg-white text-emerald-900 border border-emerald-200">
                        {{ $borrowCodeMatch }}
                    </span>
                @endif
            </div>
        @endif

        @error('borrowing')
            <p role="alert" class="rounded-lg bg-red-50 border border-red-200 p-3.5 text-sm text-red-700">
                {{ $message }}
            </p>
        @enderror

        @error('duration')
            <p role="alert" class="rounded-lg bg-red-50 border border-red-200 p-3.5 text-sm text-red-700">
                {{ $message }}
            </p>
        @enderror

        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pt-1">
            <form wire:submit="borrowBook" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3 w-full sm:w-auto">
                <div class="w-full sm:w-auto">
                    <label for="duration-{{ $book->id }}" class="block font-mono text-[11px] uppercase tracking-wider text-stone-600 mb-1.5">
                        Durasi Pinjam
                    </label>
                    <select
                        id="duration-{{ $book->id }}"
                        wire:model="duration"
                        class="w-full sm:w-auto bg-white border border-stone-300 text-stone-800 text-sm rounded-lg px-3.5 py-2.5 focus:outline-none focus:border-[#144533] focus:ring-2 focus:ring-[#144533]/20"
                    >
                        <option value="3">3 Hari</option>
                        <option value="7">7 Hari</option>
                        <option value="14">14 Hari</option>
                    </select>
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    @disabled($book->stock <= 0)
                    class="w-full sm:w-auto inline-flex items-center justify-center bg-[#144533] hover:bg-[#0F3325] text-white px-5 py-2.5 rounded-lg text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#144533]"
                >
                    <span wire:loading.remove wire:target="borrowBook">Pinjam Sekarang</span>
                    <span wire:loading wire:target="borrowBook">Memproses…</span>
                </button>
            </form>

            <button
                type="button"
                wire:click="toggleBookmark"
                wire:loading.attr="disabled"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 border border-stone-300 bg-white hover:bg-stone-50 text-stone-700 px-4 py-2.5 rounded-lg text-sm font-medium transition disabled:opacity-50 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#144533]"
            >
                <svg class="w-4 h-4 {{ $this->isBookmarked ? 'text-[#B45309]' : 'text-stone-500' }}" fill="{{ $this->isBookmarked ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
                <span>{{ $this->isBookmarked ? 'Hapus Favorit' : 'Simpan ke Favorit' }}</span>
            </button>
        </div>
    @endauth
</div>
