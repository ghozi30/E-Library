<?php

use App\Models\Bookmark;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    /** @return Collection<int, Bookmark> */
    #[Computed]
    public function bookmarks(): Collection
    {
        if (auth()->guest()) {
            return new Collection;
        }

        return Bookmark::where('user_id', auth()->id())
            ->with('book.category')->latest()->get();
    }

    public function removeBookmark(int $bookmarkId): void
    {
        abort_unless(auth()->check(), 403);

        Bookmark::where('user_id', auth()->id())->whereKey($bookmarkId)->delete();
        unset($this->bookmarks);
    }
};
?>

<div class="mx-auto max-w-7xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-extrabold text-slate-900">Buku Favorit</h1>
    @guest
        <a href="{{ route('login') }}" wire:navigate class="inline-flex rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white">Login untuk Melihat Favorit</a>
    @endguest
    @auth
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($this->bookmarks as $bookmark)
                <article wire:key="bookmark-{{ $bookmark->id }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    @if ($bookmark->book->cover_image)
                        <img src="{{ asset('storage/'.$bookmark->book->cover_image) }}" alt="Sampul {{ $bookmark->book->title }}" loading="lazy" class="h-64 w-full object-contain bg-slate-50">
                    @else
                        <div class="flex h-64 items-center justify-center bg-slate-100 text-slate-500">Sampul belum tersedia</div>
                    @endif
                    <div class="space-y-3 p-5">
                        <h2 class="text-lg font-bold text-slate-900">{{ $bookmark->book->title }}</h2>
                        <p class="text-sm text-slate-600">{{ $bookmark->book->author }}</p>
                        <p class="text-sm text-slate-600">Kode rak: {{ $bookmark->book->category?->rack_code ?? 'Belum tersedia' }}</p>
                        <span @class(['inline-flex rounded-full px-3 py-1 text-xs font-semibold', 'bg-green-100 text-green-800' => $bookmark->book->stock > 0, 'bg-red-100 text-red-800' => $bookmark->book->stock <= 0])>Sisa stok: {{ $bookmark->book->stock }}</span>
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('books.detail', ['id' => $bookmark->book->id]) }}" wire:navigate class="rounded-lg bg-blue-600 px-4 py-2 text-center font-semibold text-white">Detail Buku</a>
                            <button type="button" wire:click="removeBookmark({{ $bookmark->id }})" wire:loading.attr="disabled" class="rounded-lg border border-red-200 px-4 py-2 font-semibold text-red-700 disabled:opacity-50">Hapus dari Favorit</button>
                        </div>
                    </div>
                </article>
            @empty
                <p class="text-slate-500 sm:col-span-2 lg:col-span-3 xl:col-span-4">Belum ada buku favorit. Simpan buku dari halaman buku untuk menambahkannya di sini.</p>
            @endforelse
        </div>
    @endauth
</div>
