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

<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="space-y-6 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-extrabold text-slate-900">{{ $book->title }}</h1>
        @guest
            <a href="{{ route('login') }}" wire:navigate class="inline-flex rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white">Login untuk Meminjam atau Menyimpan Buku</a>
        @endguest
        @auth
            @if (session('borrow_success'))
                <p role="status" class="rounded-lg bg-green-50 p-4 text-green-800">{{ session('borrow_success') }}</p>
            @endif
            @error('borrowing')
                <p role="alert" class="rounded-lg bg-red-50 p-4 text-red-700">{{ $message }}</p>
            @enderror
            <button type="button" wire:click="toggleBookmark" wire:loading.attr="disabled" class="rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-700 disabled:opacity-50">
                {{ $this->isBookmarked ? 'Hapus Favorit' : 'Simpan Favorit' }}
            </button>
            <form wire:submit="borrowBook" class="space-y-3">
                <p class="text-sm text-slate-600">Sisa stok: {{ $book->stock }}</p>
                <label for="duration-{{ $book->id }}" class="block font-medium text-slate-700">Durasi pinjam</label>
                <select id="duration-{{ $book->id }}" wire:model="duration" class="rounded-lg border border-slate-300 px-3 py-2">
                    <option value="3">3 Hari</option>
                    <option value="7">7 Hari</option>
                    <option value="14">14 Hari</option>
                </select>
                @error('duration')
                    <p role="alert" class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                <button type="submit" wire:loading.attr="disabled" @disabled($book->stock <= 0) class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white disabled:opacity-50">
                    <span wire:loading.remove wire:target="borrowBook">Pinjam Sekarang</span>
                    <span wire:loading wire:target="borrowBook">Memproses…</span>
                </button>
            </form>
        @endauth
    </div>
</div>
