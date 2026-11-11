<?php

use App\Models\Book;
use App\Models\Category;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Katalog Buku - E-Library Perpustakaan')] class extends Component {
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
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
        $this->reset(['search', 'category_id']);
        $this->resetPage();
    }

    public function with(): array
    {
        $categories = Category::orderBy('name')->get();

        $books = Book::with('category')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('author', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->category_id !== '', function ($query) {
                $query->where('category_id', $this->category_id);
            })
            ->latest()
            ->paginate(8);

        return [
            'books' => $books,
            'categories' => $categories,
        ];
    }
};
?>

<div class="space-y-8 pb-16">
    <!-- Hero Banner Perpustakaan Sekolah -->
    <section class="relative bg-gradient-to-r from-indigo-900 via-indigo-800 to-indigo-950 text-white overflow-hidden shadow-sm">
        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 relative z-10">
            <div class="max-w-3xl">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-200 border border-indigo-400/30 mb-4">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                    E-Library Resmi Sekolah
                </span>
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white leading-tight">
                    Jelajahi Gudang Ilmu & Koleksi Buku Digital
                </h1>
                <p class="mt-4 text-base sm:text-lg text-indigo-100/90 leading-relaxed">
                    Akses ribuan buku pelajaran, novel, sains, dan literatur teknologi secara mudah. Temukan buku idamanmu dan pinjam langsung lewat portal perpustakaan.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Search & Filter Bar -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-4 items-center justify-between">
            <!-- Live Search Input -->
            <div class="w-full md:flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Cari judul buku atau nama penulis..."
                    class="block w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100 transition text-sm"
                >
                @if($search !== '')
                    <button
                        wire:click="$set('search', '')"
                        type="button"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                @endif
            </div>

            <!-- Category Filter Dropdown -->
            <div class="w-full md:w-72 flex gap-2">
                <select
                    wire:model.live="category_id"
                    class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:bg-white focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100 transition text-sm font-medium"
                >
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->name }} ({{ $category->rack_code }})
                        </option>
                    @endforeach
                </select>

                @if($search !== '' || $category_id !== '')
                    <button
                        wire:click="resetFilters"
                        type="button"
                        title="Reset Filter"
                        class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition text-sm font-semibold flex items-center justify-center shrink-0"
                    >
                        Reset
                    </button>
                @endif
            </div>
        </div>

        <!-- Grid Daftar Buku -->
        <div>
            @if($books->isEmpty())
                <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
                    <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Buku tidak ditemukan</h3>
                    <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">
                        Tidak ada koleksi buku yang sesuai dengan kata kunci pencarian atau kategori yang Anda pilih.
                    </p>
                    <button
                        wire:click="resetFilters"
                        class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition"
                    >
                        Tampilkan Semua Buku
                    </button>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach($books as $book)
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all flex flex-col overflow-hidden group">
                            <!-- Cover Image / Fallback Placeholder -->
                            <div class="relative h-56 bg-slate-100 overflow-hidden flex items-center justify-center">
                                @if($book->cover_image)
                                    <img
                                        src="{{ asset('storage/' . $book->cover_image) }}"
                                        alt="{{ $book->title }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        loading="lazy"
                                    >
                                @else
                                    <!-- Fallback Gambar Placeholder Rapi -->
                                    <div class="w-full h-full bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800 p-6 flex flex-col justify-between text-white relative">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[10px] font-bold tracking-wider uppercase px-2 py-0.5 rounded bg-white/20 backdrop-blur-sm">
                                                {{ $book->category->name ?? 'Buku' }}
                                            </span>
                                            <svg class="w-5 h-5 text-indigo-200" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-sm leading-snug line-clamp-3 text-white drop-shadow-sm">
                                                {{ $book->title }}
                                            </h4>
                                            <p class="text-[11px] text-indigo-200 mt-1 line-clamp-1">
                                                {{ $book->author }}
                                            </p>
                                        </div>
                                        <div class="h-1 w-8 bg-amber-400 rounded-full"></div>
                                    </div>
                                @endif

                                <!-- Stock Badge On Image (Top Left) -->
                                <div class="absolute top-3 left-3">
                                    @if($book->stock > 0)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500 text-white shadow-sm shadow-emerald-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                            Tersedia: {{ $book->stock }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-600 text-white shadow-sm shadow-rose-600/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                            Stok Habis
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Content Details -->
                            <div class="p-5 flex-1 flex flex-col justify-between">
                                <div>
                                    <!-- Category & Rack Code Badge -->
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <span class="text-xs font-semibold text-indigo-600 truncate">
                                            {{ $book->category->name ?? 'Umum' }}
                                        </span>
                                        <span class="inline-flex items-center text-[11px] font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200 shrink-0" title="Kode Rak">
                                            {{ $book->category->rack_code ?? 'RAK-00' }}
                                        </span>
                                    </div>

                                    <!-- Title -->
                                    <h3 class="font-bold text-slate-900 text-base leading-snug line-clamp-2 group-hover:text-indigo-600 transition" title="{{ $book->title }}">
                                        {{ $book->title }}
                                    </h3>

                                    <!-- Author -->
                                    <p class="text-xs text-slate-500 mt-1 flex items-center gap-1 line-clamp-1">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        {{ $book->author }}
                                    </p>
                                </div>

                                <!-- Action Button -->
                                <div class="mt-4 pt-3 border-t border-slate-100">
                                    <a
                                        href="{{ route('books.detail', $book->id) }}"
                                        wire:navigate
                                        class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs font-semibold bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white transition duration-200"
                                    >
                                        <span>Lihat Detail</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $books->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
