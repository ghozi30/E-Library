<?php

use App\Models\Book;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail Buku - E-Library Sekolah')] class extends Component {
    public Book $book;

    public function mount(Book|int|string|null $book = null, Book|int|string|null $id = null): void
    {
        $target = $book ?? $id;

        if ($target instanceof Book) {
            $this->book = $target->loadMissing('category');
        } else {
            $this->book = Book::with('category')->findOrFail($target);
        }
    }
};
?>

@php
    $themes = [
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
    $theme = $themes[((int) ($book->category_id ?? $book->id)) % count($themes)];
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 space-y-6">
    <!-- Back Button & Breadcrumbs -->
    <div class="flex items-center justify-between gap-4">
        <a
            href="/"
            wire:navigate
            class="inline-flex items-center gap-2 text-sm font-semibold text-stone-600 hover:text-[#144533] transition group"
        >
            <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Kembali ke Katalog</span>
        </a>

        <div class="hidden sm:flex items-center gap-2 text-xs text-stone-500">
            <a href="/" wire:navigate class="hover:text-[#144533] transition-colors">Katalog</a>
            <span aria-hidden="true">/</span>
            <span class="font-mono">{{ $book->category->name ?? 'Kategori' }}</span>
            <span aria-hidden="true">/</span>
            <span class="text-stone-800 font-medium truncate max-w-xs">{{ $book->title }}</span>
        </div>
    </div>

    <!-- Main Detail Card (Single Cohesive View) -->
    <div class="bg-white rounded-2xl border border-stone-200/80 shadow-sm overflow-hidden p-4 sm:p-8 lg:p-10">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 sm:gap-8 lg:gap-10 items-start">
            <!-- Left Column: Sampul Buku & Plat Nomor Rak -->
            <div class="md:col-span-5 lg:col-span-4 min-w-0">
                <div class="bg-[#FAF8F5] border border-stone-200/80 rounded-xl p-4 sm:p-6 flex flex-col items-center gap-4">
                    <div class="w-full max-w-[200px] sm:max-w-[240px] aspect-[3/4] rounded-md overflow-hidden shadow-sm border border-stone-300/80 relative">
                        @if ($book->cover_image)
                            <img
                                src="{{ asset('storage/' . $book->cover_image) }}"
                                alt="Sampul {{ $book->title }}"
                                class="w-full h-full object-cover"
                            >
                        @else
                            <!-- Cloth-Bound Editorial Cover Fallback -->
                            <div class="w-full h-full {{ $theme['bg'] }} {{ $theme['title'] }} p-6 flex flex-col justify-between border-l-[6px] border-black/25 relative">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-mono text-[10px] uppercase tracking-wider px-2 py-0.5 rounded {{ $theme['pill'] }}">
                                        {{ $book->category->rack_code ?? 'UMUM' }}
                                    </span>
                                    <span class="h-1 w-7 rounded-full {{ $theme['accent'] }}" aria-hidden="true"></span>
                                </div>

                                <div class="my-auto py-3">
                                    <h3 class="font-serif text-xl font-semibold leading-snug line-clamp-4">
                                        {{ $book->title }}
                                    </h3>
                                </div>

                                <div class="pt-3 border-t border-white/15">
                                    <p class="text-xs {{ $theme['subtitle'] }} truncate font-medium">
                                        {{ $book->author }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="w-full max-w-[240px] flex items-center justify-between px-3 py-2 rounded-md bg-stone-100 text-stone-700 border border-stone-200 font-mono text-xs">
                        <span class="text-stone-500">Kode Rak</span>
                        <span class="font-semibold text-stone-800">{{ $book->category->rack_code ?? 'RAK-00' }}</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Informasi Lengkap, Sinopsis, & Aksi Peminjaman Terintegrasi -->
            <div class="md:col-span-7 lg:col-span-8 space-y-6 min-w-0">
                <!-- Badges Kategori & Rak -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold bg-[#144533]/10 text-[#144533] border border-[#144533]/20">
                        {{ $book->category->name ?? 'Kategori Umum' }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-mono font-semibold bg-stone-100 text-stone-700 border border-stone-200">
                        <svg class="w-3.5 h-3.5 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Rak: {{ $book->category->rack_code ?? 'RAK-00' }}
                    </span>
                </div>

                <!-- Judul & Penulis Buku -->
                <div class="space-y-2 min-w-0">
                    <h1 class="font-serif text-2xl sm:text-4xl font-semibold text-stone-900 tracking-tight leading-tight [overflow-wrap:anywhere]">
                        {{ $book->title }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-2 text-sm text-stone-600">
                        <span class="text-stone-500">Penulis:</span>
                        <span class="font-semibold text-stone-800 [overflow-wrap:anywhere]">{{ $book->author }}</span>
                    </div>
                </div>

                <!-- Spesifikasi Koleksi -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 rounded-xl bg-[#FAF8F5] border border-stone-200/80 text-sm">
                    <div>
                        <div class="font-mono text-[11px] uppercase tracking-wider text-stone-500">Kategori Koleksi</div>
                        <div class="font-semibold text-stone-800 mt-1">{{ $book->category->name ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="font-mono text-[11px] uppercase tracking-wider text-stone-500">Kode Rak Fisik</div>
                        <div class="font-mono font-semibold text-stone-800 mt-1">{{ $book->category->rack_code ?? '-' }}</div>
                    </div>
                </div>

                <!-- Sinopsis Buku -->
                <div class="space-y-2 min-w-0">
                    <h2 class="font-mono text-xs font-semibold uppercase tracking-[0.12em] text-stone-500">
                        SINOPSIS BUKU
                    </h2>
                    <div class="text-stone-700 text-sm sm:text-base leading-relaxed whitespace-pre-line [overflow-wrap:anywhere] bg-[#FAF8F5]/60 p-4 sm:p-5 rounded-xl border border-stone-200/80">
                        {{ $book->synopsis ?: 'Belum ada sinopsis untuk buku ini.' }}
                    </div>
                </div>

                <!-- Komponen Aksi Buku Terintegrasi (Peminjaman, Favorit & Stok Reaktif) -->
                <div class="pt-2">
                    <livewire:book-action :book="$book" />
                </div>
            </div>
        </div>
    </div>
</div>
