<?php

use App\Models\Book;
use App\Models\Category;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Katalog Koleksi Buku - E-Library Sekolah')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $category_id = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->category_id = '';
        $this->resetPage();
    }

    public function with(): array
    {
        $query = Book::with('category');

        if (! empty(trim($this->search))) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', $searchTerm)
                    ->orWhere('author', 'like', $searchTerm);
            });
        }

        if (! empty($this->category_id)) {
            $query->where('category_id', $this->category_id);
        }

        return [
            'books' => $query->latest()->paginate(9),
            'categories' => Category::orderBy('name')->get(),
            'totalBooksCount' => Book::count(),
            'availableBooksCount' => Book::where('stock', '>', 0)->count(),
        ];
    }
};
?>

<div>
    {{-- Hero Banner Perpustakaan Sekolah --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-indigo-900 via-indigo-800 to-slate-900 text-white py-16 sm:py-20">
        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur border border-white/20 text-xs font-medium text-indigo-200 mb-6 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Perpustakaan Digital Sekolah Modern
                </div>
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white leading-tight">
                    Jelajahi Ilmu & Temukan Inspirasi Membaca Anda
                </h1>
                <p class="mt-4 text-base sm:text-lg text-indigo-100/90 leading-relaxed">
                    Akses ratusan koleksi literatur akademik, sains, sastra, dan teknologi terkini. Pinjam buku dengan mudah, pantau riwayat sirkulasi, dan simpan bacaan favorit Anda secara mandiri.
                </p>

                <div class="mt-8 flex flex-wrap gap-4 text-xs font-semibold">
                    <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white/10 backdrop-blur border border-white/15">
                        <span class="text-amber-300 text-sm">📚</span>
                        <span>{{ $totalBooksCount }} Total Judul Buku</span>
                    </div>
                    <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white/10 backdrop-blur border border-white/15">
                        <span class="text-emerald-300 text-sm">✅</span>
                        <span>{{ $availableBooksCount }} Judul Siap Dipinjam</span>
                    </div>
                    <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white/10 backdrop-blur border border-white/15">
                        <span class="text-indigo-300 text-sm">🏷️</span>
                        <span>{{ $categories->count() }} Kategori Rak</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Main Container --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        {{-- Search & Filter Bar --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-4 sm:p-6 mb-8 -mt-16 sm:-mt-20 relative z-20">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                {{-- Live Search Input --}}
                <div class="md:col-span-8 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari berdasarkan judul buku atau nama penulis..."
                        class="w-full pl-10 pr-10 py-3 rounded-xl border border-slate-300 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all"
                    >
                    @if(!empty($search))
                        <button
                            type="button"
                            wire:click="$set('search', '')"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600"
                            title="Hapus pencarian"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endif
                </div>

                {{-- Filter Kategori Dropdown --}}
                <div class="md:col-span-4">
                    <select
                        wire:model.live="category_id"
                        class="w-full py-3 px-4 rounded-xl border border-slate-300 text-sm text-slate-800 bg-white focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all cursor-pointer"
                    >
                        <option value="">Semua Kategori Rak</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }} (Rak: {{ $cat->rack_code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Filter Status Info --}}
            @if(!empty($search) || !empty($category_id))
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
                    <div class="flex items-center gap-2">
                        <span>Menampilkan hasil pencarian:</span>
                        @if(!empty($search))
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-indigo-50 text-indigo-700 font-medium border border-indigo-200">
                                Kata kunci: "{{ $search }}"
                            </span>
                        @endif
                        @if(!empty($category_id))
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-purple-50 text-purple-700 font-medium border border-purple-200">
                                Kategori: {{ $categories->firstWhere('id', $category_id)?->name }}
                            </span>
                        @endif
                    </div>
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="text-rose-600 hover:text-rose-700 font-medium hover:underline cursor-pointer"
                    >
                        Reset Filter
                    </button>
                </div>
            @endif
        </div>

        {{-- Loading Indicator --}}
        <div wire:loading class="w-full py-4 text-center text-sm text-indigo-600 font-medium">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-50 border border-indigo-100 shadow-sm">
                <svg class="animate-spin w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Memperbarui daftar buku...</span>
            </div>
        </div>

        {{-- Grid Daftar Buku --}}
        @if($books->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($books as $book)
                    <div class="group bg-white rounded-2xl shadow-sm hover:shadow-md border border-slate-200/80 hover:border-indigo-200 transition-all duration-200 flex flex-col overflow-hidden">
                        {{-- Cover Image with Fallback Placeholder --}}
                        <div class="relative h-56 bg-slate-100 overflow-hidden shrink-0">
                            @if(!empty($book->cover_image))
                                <img
                                    src="{{ asset('storage/' . $book->cover_image) }}"
                                    alt="{{ $book->title }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                >
                            @else
                                {{-- Fallback Placeholder Modern --}}
                                <div class="w-full h-full bg-gradient-to-br from-indigo-600 via-indigo-700 to-slate-900 flex flex-col items-center justify-center p-6 text-center text-white relative group-hover:scale-105 transition-transform duration-300">
                                    <div class="w-14 h-14 rounded-2xl bg-white/15 backdrop-blur border border-white/20 flex items-center justify-center text-3xl mb-3 shadow-inner">
                                        📖
                                    </div>
                                    <span class="text-xs uppercase tracking-widest text-indigo-200 font-semibold mb-1">
                                        {{ $book->category->name ?? 'Pustaka' }}
                                    </span>
                                    <h3 class="text-sm font-bold line-clamp-2 text-white/90 px-2 leading-tight">
                                        {{ $book->title }}
                                    </h3>
                                    <span class="absolute bottom-2 text-[10px] text-indigo-300/80 font-mono">
                                        {{ $book->category->rack_code ?? 'RAK-00' }}
                                    </span>
                                </div>
                            @endif

                            {{-- Floating Category Badge --}}
                            <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-white/95 text-slate-800 backdrop-blur shadow-sm">
                                    {{ $book->category->name ?? 'Umum' }}
                                </span>
                            </div>

                            {{-- Floating Stock Status Badge --}}
                            <div class="absolute top-3 right-3">
                                @if($book->stock > 0)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-500 text-white shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                        Tersedia: {{ $book->stock }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-500 text-white shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                        Stok Habis
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Card Body --}}
                        <div class="p-5 flex-1 flex flex-col justify-between">
                            <div>
                                {{-- Kode Rak Badge --}}
                                <div class="flex items-center justify-between text-xs text-slate-500 mb-2">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-mono font-medium">
                                        🏷️ Rak: {{ $book->category->rack_code ?? '-' }}
                                    </span>
                                    <span class="text-[11px] text-slate-400">
                                        ID #{{ $book->id }}
                                    </span>
                                </div>

                                {{-- Judul Buku --}}
                                <h2 class="text-base font-bold text-slate-900 group-hover:text-indigo-600 transition-colors line-clamp-2 leading-snug">
                                    {{ $book->title }}
                                </h2>

                                {{-- Penulis --}}
                                <p class="text-xs text-slate-500 mt-2 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span class="line-clamp-1">Penulis: <strong class="text-slate-700 font-medium">{{ $book->author }}</strong></span>
                                </p>

                                {{-- Sinopsis Singkat --}}
                                @if(!empty($book->synopsis))
                                    <p class="text-xs text-slate-600 mt-2.5 line-clamp-2 leading-relaxed">
                                        {{ $book->synopsis }}
                                    </p>
                                @endif
                            </div>

                            {{-- Tombol Detail Buku --}}
                            <div class="pt-5 mt-4 border-t border-slate-100">
                                <a
                                    href="{{ route('books.show', $book->id) }}"
                                    wire:navigate
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-50 hover:bg-indigo-600 text-slate-700 hover:text-white text-xs font-semibold border border-slate-200/80 hover:border-transparent transition-all group/btn cursor-pointer"
                                >
                                    <span>Lihat Detail Buku</span>
                                    <svg class="w-3.5 h-3.5 group-hover/btn:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination Links --}}
            <div class="mt-10">
                {{ $books->links() }}
            </div>
        @else
            {{-- Empty State --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center max-w-xl mx-auto shadow-sm">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto text-3xl mb-4">
                    🔍
                </div>
                <h3 class="text-lg font-bold text-slate-900">Buku Tidak Ditemukan</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">
                    Tidak ada koleksi buku yang cocok dengan kriteria pencarian "{{ $search }}" atau filter kategori yang dipilih.
                </p>
                <div class="mt-6">
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition-all cursor-pointer"
                    >
                        <span>Tampilkan Semua Koleksi</span>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
