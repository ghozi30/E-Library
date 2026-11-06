<?php

use App\Models\Book;
use App\Models\Bookmark;
use App\Models\Borrowing;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    public Book $book;

    public $duration = 7;

    #[Locked]
    public ?string $successMessage = null;

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
            $query = Bookmark::where('user_id', auth()->id())->where('book_id', $this->book->id);

            if ($query->exists()) {
                $query->delete();
            } else {
                Bookmark::create(['user_id' => auth()->id(), 'book_id' => $this->book->id]);
            }
        });

        unset($this->isBookmarked);
    }

    public function borrowBook(): void
    {
        abort_unless(auth()->check(), 403);
        $this->successMessage = null;
        $this->resetValidation();
        $this->validate(['duration' => ['required', 'integer', 'in:3,7,14']]);

        $borrowCode = DB::transaction(function (): string {
            $this->book = Book::whereKey($this->book->id)->lockForUpdate()->firstOrFail();

            if ($this->book->stock <= 0) {
                throw ValidationException::withMessages(['borrowing' => 'Stok buku sudah habis.']);
            }

            if (Borrowing::where('user_id', auth()->id())->where('book_id', $this->book->id)
                ->where('status', 'dipinjam')->exists()) {
                throw ValidationException::withMessages(['borrowing' => 'Anda masih meminjam buku ini. Kembalikan terlebih dahulu.']);
            }

            $borrowCode = null;

            for ($attempt = 0; $attempt < 100; $attempt++) {
                $candidate = 'PJM-'.rand(1000, 9999);

                if (! Borrowing::where('borrow_code', $candidate)->exists()) {
                    $borrowCode = $candidate;
                    break;
                }
            }

            if ($borrowCode === null) {
                throw ValidationException::withMessages(['borrowing' => 'Kode pinjam belum tersedia. Silakan coba kembali.']);
            }

            $borrowDate = now();
            Borrowing::create([
                'borrow_code' => $borrowCode,
                'user_id' => auth()->id(),
                'book_id' => $this->book->id,
                'borrow_date' => $borrowDate,
                'due_date' => $borrowDate->copy()->addDays((int) $this->duration),
                'status' => 'dipinjam',
            ]);

            $this->book->decrement('stock');

            return $borrowCode;
        });

        $this->successMessage = 'Peminjaman berhasil! Kode pinjam Anda: '.$borrowCode;
    }
};
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Halaman Aksi Buku</h1>
            <h2 class="mt-2 text-xl font-semibold text-slate-800">{{ $book->title }}</h2>
            <p class="text-slate-500 mt-2">{{ $book->author }} · Sisa stok: {{ $book->stock }}</p>
        </div>

        @guest
            <a href="/login" wire:navigate class="inline-flex rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white">Masuk untuk Meminjam atau Menyimpan Buku</a>
        @endguest

        @auth
            @if ($successMessage)
                <div role="status" class="rounded-lg bg-green-50 p-4 text-green-800">{{ $successMessage }}</div>
            @endif

            <button type="button" wire:click="toggleBookmark" wire:loading.attr="disabled" class="rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-700 disabled:opacity-50">
                {{ $this->isBookmarked ? 'Hapus Favorit' : 'Simpan Favorit' }}
            </button>

            <form wire:submit="borrowBook" class="space-y-3">
                <label for="borrow-duration-{{ $book->id }}" class="block font-medium text-slate-700">Durasi pinjam</label>
                <select id="borrow-duration-{{ $book->id }}" wire:model="duration" class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="3">3 Hari</option>
                    <option value="7">7 Hari</option>
                    <option value="14">14 Hari</option>
                </select>
                @error('duration') <p role="alert" class="text-sm text-red-600">{{ $message }}</p> @enderror
                @error('borrowing') <p role="alert" class="text-sm text-red-600">{{ $message }}</p> @enderror
                <button type="submit" wire:loading.attr="disabled" @disabled($book->stock <= 0) class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white disabled:opacity-50">
                    <span wire:loading.remove wire:target="borrowBook">Pinjam Sekarang</span>
                    <span wire:loading wire:target="borrowBook">Memproses...</span>
                </button>
            </form>
        @endauth
    </div>
</div>
