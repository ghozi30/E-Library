<?php

use App\Models\Bookmark;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /** @return Collection<int, Bookmark> */
    #[Computed]
    public function bookmarks(): Collection
    {
        if (auth()->guest()) {
            return new Collection;
        }

        return Bookmark::where('user_id', auth()->id())
            ->whereHas('book')->with('book.category')->latest()->get();
    }

    public function removeBookmark(int $bookmarkId): void
    {
        abort_unless(auth()->check(), 403);

        Bookmark::where('user_id', auth()->id())->whereKey($bookmarkId)->delete();
        unset($this->bookmarks);
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Halaman Buku Favorit</h1>

    @guest
        <a href="/login" wire:navigate class="text-blue-600 underline">Masuk untuk melihat buku favorit Anda.</a>
    @endguest

    @auth
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($this->bookmarks as $bookmark)
                <article wire:key="bookmark-{{ $bookmark->id }}" class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    @if ($bookmark->book->cover_image)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($bookmark->book->cover_image) }}" alt="Sampul {{ $bookmark->book->title }}" loading="lazy" class="h-64 w-full object-contain bg-slate-100">
                    @else
                        <div class="flex h-64 items-center justify-center bg-slate-100 text-slate-500">Sampul belum tersedia</div>
                    @endif
                    <div class="flex flex-1 flex-col gap-3 p-5">
                        <h2 class="text-lg font-bold text-slate-800">{{ $bookmark->book->title }}</h2>
                        <p class="text-sm text-slate-600">{{ $bookmark->book->author }}</p>
                        <p class="text-sm text-slate-500">Kode rak: {{ $bookmark->book->category?->rack_code ?? 'Belum ditentukan' }}</p>
                        <span @class(['self-start rounded-full px-3 py-1 text-xs font-semibold', 'bg-green-100 text-green-800' => $bookmark->book->stock > 0, 'bg-red-100 text-red-800' => $bookmark->book->stock <= 0])>Sisa stok: {{ $bookmark->book->stock }}</span>
                        <div class="mt-auto flex flex-col gap-2 pt-3">
                            <a href="{{ route('books.show', $bookmark->book->id) }}" wire:navigate class="rounded-lg bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white">Detail Buku</a>
                            <button type="button" wire:click="removeBookmark({{ $bookmark->id }})" wire:loading.attr="disabled" wire:target="removeBookmark({{ $bookmark->id }})" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 disabled:opacity-50">Hapus dari Favorit</button>
                        </div>
                    </div>
                </article>
            @empty
                <p class="col-span-full rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500">Belum ada buku favorit. Simpan buku dari halaman aksi buku.</p>
            @endforelse
        </div>
    @endauth
</div>
