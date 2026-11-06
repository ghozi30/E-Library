<?php

use App\Models\Book;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail Buku - E-Library Sekolah')] class extends Component
{
    public Book $book;

    public function mount(Book|int|string $book): void
    {
        if (! $book instanceof Book) {
            $this->book = Book::with('category')->findOrFail($book);
        } else {
            $this->book = $book->loadMissing('category');
        }
    }
};
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Breadcrumbs & Back Button --}}
    <div class="flex items-center justify-between gap-4 mb-6">
        <nav class="flex items-center text-xs text-slate-500 font-medium overflow-hidden">
            <a href="{{ route('books.index') }}" wire:navigate class="hover:text-indigo-600 transition-colors shrink-0">
                Katalog Buku
            </a>
            <svg class="w-3.5 h-3.5 mx-2 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-slate-800 font-semibold truncate">
                {{ $book->title }}
            </span>
        </nav>

        <a
            href="{{ route('books.index') }}"
            wire:navigate
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-100 border border-slate-200 shadow-sm transition-all shrink-0 cursor-pointer"
        >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali ke Katalog</span>
        </a>
    </div>

    {{-- Detail Card --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
        <div class="p-6 sm:p-10">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
                {{-- Cover Image Column --}}
                <div class="md:col-span-5 lg:col-span-4">
                    <div class="relative rounded-2xl overflow-hidden shadow-md border border-slate-200/70 bg-slate-50 aspect-[3/4]">
                        @if(!empty($book->cover_image))
                            <img
                                src="{{ asset('storage/' . $book->cover_image) }}"
                                alt="{{ $book->title }}"
                                class="w-full h-full object-cover"
                            >
                        @else
                            {{-- Fallback Cover Placeholder --}}
                            <div class="w-full h-full bg-gradient-to-br from-indigo-700 via-indigo-800 to-slate-950 flex flex-col items-center justify-between p-6 text-center text-white relative">
                                <div class="w-full text-right">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-mono uppercase tracking-wider bg-white/10 backdrop-blur border border-white/20 text-indigo-200">
                                        {{ $book->category->rack_code ?? 'RAK-00' }}
                                    </span>
                                </div>

                                <div class="my-auto">
                                    <div class="w-16 h-16 rounded-2xl bg-white/15 backdrop-blur border border-white/20 flex items-center justify-center text-3xl mx-auto mb-4 shadow-inner">
                                        📖
                                    </div>
                                    <span class="text-xs uppercase tracking-widest text-indigo-200 font-semibold block mb-1">
                                        {{ $book->category->name ?? 'Koleksi Buku' }}
                                    </span>
                                    <h2 class="text-base font-bold line-clamp-3 text-white px-2">
                                        {{ $book->title }}
                                    </h2>
                                </div>

                                <div class="w-full pt-4 border-t border-white/10 text-[11px] text-indigo-200 truncate">
                                    {{ $book->author }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Book Information Column --}}
                <div class="md:col-span-7 lg:col-span-8 flex flex-col justify-between">
                    <div>
                        {{-- Badges Row --}}
                        <div class="flex flex-wrap items-center gap-2 mb-4">
                            {{-- Kategori --}}
                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                {{ $book->category->name ?? 'Umum' }}
                            </span>

                            {{-- Kode Rak --}}
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-mono font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                🏷️ Rak: {{ $book->category->rack_code ?? '-' }}
                            </span>

                            {{-- Status Stok --}}
                            @if($book->stock > 0)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Tersedia: {{ $book->stock }} Eksemplar
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                    Stok Habis
                                </span>
                            @endif
                        </div>

                        {{-- Judul Buku --}}
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 leading-tight">
                            {{ $book->title }}
                        </h1>

                        {{-- Penulis --}}
                        <div class="mt-3 flex items-center gap-2 text-sm text-slate-600">
                            <span class="font-medium text-slate-400">Penulis:</span>
                            <span class="font-semibold text-slate-800">{{ $book->author }}</span>
                        </div>

                        {{-- Metadata Grid --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 my-6 p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs">
                            <div>
                                <span class="text-slate-400 block mb-0.5">ID Buku</span>
                                <span class="font-mono font-semibold text-slate-800">#{{ $book->id }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block mb-0.5">Lokasi Rak</span>
                                <span class="font-mono font-semibold text-slate-800">{{ $book->category->rack_code ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block mb-0.5">Ketersediaan</span>
                                <span class="font-semibold {{ $book->stock > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $book->stock > 0 ? $book->stock . ' Unit' : 'Kosong' }}
                                </span>
                            </div>
                        </div>

                        {{-- Sinopsis --}}
                        <div class="mt-6">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-2.5">
                                Sinopsis Buku
                            </h3>
                            <div class="text-sm text-slate-600 leading-relaxed space-y-3 bg-slate-50/50 p-4 rounded-xl border border-slate-100">
                                @if(!empty($book->synopsis))
                                    <p class="whitespace-pre-line">{{ $book->synopsis }}</p>
                                @else
                                    <p class="italic text-slate-400">Belum ada sinopsis atau deskripsi untuk buku ini.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pemanggilan Komponen Livewire Book Action (Tugas Orang 2) --}}
            <div class="mt-10 pt-8 border-t border-slate-200">
                <livewire:book-action :book="$book" />
            </div>
        </div>
    </div>
</div>
