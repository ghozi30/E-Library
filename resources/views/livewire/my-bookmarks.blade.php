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

@php
    $coverThemes = [
        0 => [
            'bg' => 'bg-[#114B43]',
            'title' => 'text-[#FAF8F4]',
            'subtitle' => 'text-[#D7EDE8]',
            'accent' => 'bg-[#FDE68A]',
            'pill' => 'bg-white/15 text-[#FAF8F4]',
        ],
        1 => [
            'bg' => 'bg-[#7C2D12]',
            'title' => 'text-[#FFF7ED]',
            'subtitle' => 'text-[#FED7AA]',
            'accent' => 'bg-[#FDBA74]',
            'pill' => 'bg-white/15 text-[#FFF7ED]',
        ],
        2 => [
            'bg' => 'bg-[#1E293B]',
            'title' => 'text-[#F8FAFC]',
            'subtitle' => 'text-[#CBD5E1]',
            'accent' => 'bg-[#93C5FD]',
            'pill' => 'bg-white/15 text-[#F8FAFC]',
        ],
        3 => [
            'bg' => 'bg-[#3F3A36]',
            'title' => 'text-[#FAF8F4]',
            'subtitle' => 'text-[#E7E2DC]',
            'accent' => 'bg-[#E7D3B0]',
            'pill' => 'bg-white/15 text-[#FAF8F4]',
        ],
        4 => [
            'bg' => 'bg-[#5B1D35]',
            'title' => 'text-[#FDF2F8]',
            'subtitle' => 'text-[#F5D0E0]',
            'accent' => 'bg-[#FBCFE8]',
            'pill' => 'bg-white/15 text-[#FDF2F8]',
        ],
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
    <!-- Editorial Page Header -->
    <div class="border-b border-stone-200/80 pb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-stone-100 text-stone-700 border border-stone-200 font-mono text-[11px] font-semibold uppercase tracking-[0.12em]">
                KOLEKSI TERSIMPAN
            </span>
            <h1 class="font-serif text-3xl sm:text-4xl font-semibold text-stone-900 tracking-tight mt-2">
                Buku Favorit Saya
            </h1>
            <p class="mt-1 text-sm sm:text-base text-stone-600">
                Daftar literatur dan referensi pilihan yang Anda simpan untuk dipinjam kembali.
            </p>
        </div>

        <a
            href="/"
            wire:navigate
            class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-semibold border border-[#144533]/20 bg-[#144533]/5 text-[#144533] hover:bg-[#144533] hover:text-white rounded-xl transition shrink-0 self-start sm:self-auto"
        >
            <span>Jelajahi Katalog</span>
            <span aria-hidden="true">&rarr;</span>
        </a>
    </div>

    @guest
        <div class="bg-white rounded-xl border border-stone-200/80 p-8 sm:p-10 text-center max-w-lg mx-auto space-y-4 shadow-sm">
            <div class="w-12 h-12 mx-auto rounded-xl bg-[#144533]/10 text-[#144533] flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
            </div>
            <h2 class="font-serif text-xl font-semibold text-stone-900">Masuk untuk Melihat Koleksi Tersimpan</h2>
            <p class="text-sm text-stone-600">
                Daftar buku favorit Anda tersimpan aman di kartu anggota perpustakaan.
            </p>
            <div>
                <a
                    href="{{ route('login') }}"
                    wire:navigate
                    class="inline-flex items-center justify-center rounded-lg bg-[#144533] hover:bg-[#0F3325] px-5 py-2.5 text-sm font-semibold text-white transition"
                >
                    Login untuk Melihat Favorit
                </a>
            </div>
        </div>
    @endguest

    @auth
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($this->bookmarks as $bookmark)
                @php
                    $theme = $coverThemes[((int) ($bookmark->book->category_id ?? $bookmark->book->id)) % count($coverThemes)];
                @endphp
                <article
                    wire:key="bookmark-{{ $bookmark->id }}"
                    class="bg-white rounded-xl border border-stone-200/80 hover:border-[#144533]/60 transition-colors duration-200 flex flex-col overflow-hidden group"
                >
                    <!-- Book Cover Frame -->
                    <div class="relative p-5 pb-4 bg-[#FAF8F5] border-b border-stone-200/70 flex items-center justify-center">
                        <a
                            href="{{ route('books.detail', ['id' => $bookmark->book->id]) }}"
                            wire:navigate
                            class="block w-full max-w-[168px] aspect-[3/4] rounded-md overflow-hidden border border-stone-300/80 shadow-sm relative focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#144533]"
                            aria-label="Buka detail {{ $bookmark->book->title }}"
                        >
                            @if ($bookmark->book->cover_image)
                                <img
                                    src="{{ asset('storage/'.$bookmark->book->cover_image) }}"
                                    alt="Sampul {{ $bookmark->book->title }}"
                                    loading="lazy"
                                    class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-300"
                                >
                            @else
                                <!-- Cloth-Bound Editorial Cover Fallback -->
                                <div class="w-full h-full {{ $theme['bg'] }} {{ $theme['title'] }} p-4 flex flex-col justify-between border-l-[6px] border-black/25 relative">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-mono text-[9px] uppercase tracking-wider px-1.5 py-0.5 rounded {{ $theme['pill'] }}">
                                            {{ $bookmark->book->category?->rack_code ?? 'UMUM' }}
                                        </span>
                                        <span class="h-1 w-6 rounded-full {{ $theme['accent'] }}" aria-hidden="true"></span>
                                    </div>

                                    <div class="space-y-1.5 my-auto py-2">
                                        <h3 class="font-serif text-base font-semibold leading-snug line-clamp-3">
                                            {{ $bookmark->book->title }}
                                        </h3>
                                    </div>

                                    <div class="pt-2 border-t border-white/15">
                                        <p class="text-[11px] {{ $theme['subtitle'] }} truncate font-medium">
                                            {{ $bookmark->book->author }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </a>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between gap-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-[#144533] truncate">
                                    {{ $bookmark->book->category?->name ?? 'Umum' }}
                                </span>

                                @if ($bookmark->book->stock > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded font-mono text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                        Tersedia: {{ $bookmark->book->stock }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded font-mono text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200 shrink-0">
                                        Stok Habis
                                    </span>
                                @endif
                            </div>

                            <h2 class="font-serif text-lg font-semibold text-stone-900 leading-snug line-clamp-2 [overflow-wrap:anywhere] group-hover:text-[#144533] transition-colors">
                                <a href="{{ route('books.detail', ['id' => $bookmark->book->id]) }}" wire:navigate>
                                    {{ $bookmark->book->title }}
                                </a>
                            </h2>

                            <div class="flex items-center justify-between gap-2 text-xs text-stone-600">
                                <span class="truncate">{{ $bookmark->book->author }}</span>
                                <span class="bg-stone-100 text-stone-700 border border-stone-200 font-mono text-[11px] px-2 py-0.5 rounded shrink-0">
                                    {{ $bookmark->book->category?->rack_code ?? 'Belum tersedia' }}
                                </span>
                            </div>
                        </div>

                        <!-- Bottom Action Row -->
                        <div class="pt-3 border-t border-stone-200/70 flex items-center gap-2.5">
                            <a
                                href="{{ route('books.detail', ['id' => $bookmark->book->id]) }}"
                                wire:navigate
                                class="min-h-[38px] flex-1 inline-flex items-center justify-between px-3.5 py-2 rounded-lg text-xs font-semibold bg-[#FAF8F5] text-stone-900 hover:bg-[#144533] hover:text-white border border-stone-200/90 hover:border-[#144533] transition-colors"
                            >
                                <span>Lihat Detail</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>

                            <button
                                type="button"
                                wire:click="removeBookmark({{ $bookmark->id }})"
                                wire:loading.attr="disabled"
                                aria-label="Hapus {{ $bookmark->book->title }} dari favorit"
                                class="min-h-[38px] inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold text-red-600 bg-white hover:bg-red-50 border border-red-200 transition-colors disabled:opacity-50 cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>Hapus</span>
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full bg-white rounded-xl border border-stone-200/80 px-6 py-14 text-center space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-full bg-[#FAF8F5] border border-stone-200/80 flex items-center justify-center text-stone-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                    </div>
                    <h2 class="font-serif text-xl font-semibold text-stone-900">Belum Ada Buku Favorit</h2>
                    <p class="text-sm text-stone-600 max-w-md mx-auto">
                        Belum ada buku favorit. Simpan buku dari halaman katalog atau detail buku untuk menambahkannya di sini.
                    </p>
                    <div class="pt-1">
                        <a
                            href="/"
                            wire:navigate
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs font-semibold bg-[#144533] hover:bg-[#0F3325] text-white transition"
                        >
                            Jelajahi Katalog Buku
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    @endauth
</div>
