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

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 space-y-8">
    <!-- Back Button & Breadcrumbs -->
    <div class="flex items-center justify-between">
        <a
            href="/"
            wire:navigate
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-indigo-600 transition group"
        >
            <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Kembali ke Katalog</span>
        </a>

        <div class="hidden sm:flex items-center gap-2 text-xs text-slate-500">
            <a href="/" wire:navigate class="hover:text-indigo-600">Katalog</a>
            <span>/</span>
            <span>{{ $book->category->name ?? 'Kategori' }}</span>
            <span>/</span>
            <span class="text-slate-800 font-medium truncate max-w-xs">{{ $book->title }}</span>
        </div>
    </div>

    <!-- Main Detail Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 sm:p-8 lg:p-10">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 lg:gap-12 items-start">
            <!-- Left Column: Sampul & Status Ketersediaan -->
            <div class="md:col-span-5 lg:col-span-4 flex flex-col items-center">
                <div class="w-full max-w-[280px] aspect-[3/4] rounded-2xl overflow-hidden shadow-lg border border-slate-200 relative group">
                    @if($book->cover_image)
                        <img
                            src="{{ asset('storage/' . $book->cover_image) }}"
                            alt="{{ $book->title }}"
                            class="w-full h-full object-cover"
                        >
                    @else
                        <!-- Fallback Sampul Artistik -->
                        <div class="w-full h-full bg-gradient-to-br from-indigo-700 via-indigo-800 to-purple-900 p-7 flex flex-col justify-between text-white relative">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold tracking-wider uppercase px-2.5 py-1 rounded bg-white/20 backdrop-blur-md">
                                    {{ $book->category->name ?? 'Buku' }}
                                </span>
                                <svg class="w-6 h-6 text-indigo-200" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-lg sm:text-xl leading-snug drop-shadow-md">
                                    {{ $book->title }}
                                </h3>
                                <p class="text-xs text-indigo-200 mt-2 font-medium">
                                    {{ $book->author }}
                                </p>
                            </div>
                            <div class="h-1.5 w-12 bg-amber-400 rounded-full"></div>
                        </div>
                    @endif
                </div>

                <!-- Status Ketersediaan Stok -->
                <div class="w-full max-w-[280px] mt-4">
                    @if($book->stock > 0)
                        <div class="w-full py-2.5 px-4 rounded-xl text-center bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center justify-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-sm font-semibold">Tersedia: {{ $book->stock }} Buku</span>
                        </div>
                    @else
                        <div class="w-full py-2.5 px-4 rounded-xl text-center bg-rose-50 text-rose-800 border border-rose-200 flex items-center justify-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <span class="text-sm font-semibold">Stok Habis Dipinjam</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Informasi Lengkap & Sinopsis -->
            <div class="md:col-span-7 lg:col-span-8 space-y-6">
                <!-- Badges Kategori & Rak -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                        {{ $book->category->name ?? 'Kategori Umum' }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Rak: {{ $book->category->rack_code ?? 'RAK-00' }}
                    </span>
                </div>

                <!-- Judul Buku -->
                <h1 class="text-2xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    {{ $book->title }}
                </h1>

                <!-- Penulis -->
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <span class="text-slate-400">Penulis:</span>
                    <span class="font-semibold text-slate-800">{{ $book->author }}</span>
                </div>

                <!-- Informasi Spesifikasi Buku Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-4 rounded-2xl bg-slate-50 border border-slate-100 text-sm">
                    <div>
                        <div class="text-xs text-slate-500">Kategori</div>
                        <div class="font-semibold text-slate-800 mt-0.5">{{ $book->category->name ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Lokasi Rak</div>
                        <div class="font-semibold text-slate-800 mt-0.5">{{ $book->category->rack_code ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Sisa Stok Fisik</div>
                        <div class="font-semibold {{ $book->stock > 0 ? 'text-emerald-700' : 'text-rose-700' }} mt-0.5">
                            {{ $book->stock }} eksemplar
                        </div>
                    </div>
                </div>

                <!-- Sinopsis Buku -->
                <div class="space-y-2 pt-2">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Sinopsis Buku</h3>
                    <div class="prose prose-slate max-w-none text-slate-700 text-sm leading-relaxed whitespace-pre-line bg-slate-50/50 p-5 rounded-2xl border border-slate-100">
                        {{ $book->synopsis ?: 'Belum ada sinopsis untuk buku ini.' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Pemanggilan Komponen Aksi Buku (Orang 2) -->
        <div class="mt-10 pt-8 border-t border-slate-200">
            <livewire:book-action :book="$book" />
        </div>
    </div>
</div>
