<?php

use App\Models\Book;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {
    use WithFileUploads;
    use WithPagination;

    public string $search = '';
    public string $selectedCategory = '';

    public bool $isModalOpen = false;
    public ?int $bookId = null;

    public ?int $category_id = null;
    public string $title = '';
    public string $author = '';
    public int $stock = 1;
    public string $synopsis = '';
    public $cover = null;
    public ?string $existingCover = null;

    public function mount(): void
    {
        if (! auth()->check() || auth()->user()->role !== 'admin') {
            abort(403);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedCategory(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'stock' => 'required|integer|min:0',
            'synopsis' => 'nullable|string',
            'cover' => 'nullable|image|max:10240',
        ];
    }

    protected function messages(): array
    {
        return [
            'category_id.required' => 'Pilih kategori rak buku.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'title.required' => 'Judul buku wajib diisi.',
            'author.required' => 'Nama penulis wajib diisi.',
            'stock.required' => 'Jumlah stok eksemplar wajib diisi.',
            'stock.integer' => 'Stok harus berupa bilangan bulat.',
            'stock.min' => 'Stok tidak boleh kurang dari 0.',
            'cover.image' => 'File sampul harus berupa gambar valid (JPG, PNG, WebP).',
            'cover.max' => 'Ukuran file sampul maksimal 10 MB.',
        ];
    }

    public function removeCover(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
        $this->cover = null;
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
        $this->resetForm();
        $firstCategory = Category::first();
        if ($firstCategory) {
            $this->category_id = $firstCategory->id;
        }
        $this->isModalOpen = true;
    }

    public function openEditModal(int $id): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
        $this->resetForm();
        $book = Book::findOrFail($id);
        $this->bookId = $book->id;
        $this->category_id = $book->category_id;
        $this->title = $book->title;
        $this->author = $book->author;
        $this->stock = (int) $book->stock;
        $this->synopsis = $book->synopsis ?? '';
        $this->existingCover = $book->cover_image;
        $this->isModalOpen = true;
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->resetForm();
        $this->js("document.body.classList.remove('overflow-hidden')");
    }

    public function resetForm(): void
    {
        $this->reset([
            'bookId',
            'category_id',
            'title',
            'author',
            'stock',
            'synopsis',
            'cover',
            'existingCover',
        ]);
        $this->stock = 1;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
        $this->validate();

        $data = [
            'category_id' => $this->category_id,
            'title' => $this->title,
            'author' => $this->author,
            'stock' => $this->stock,
            'synopsis' => $this->synopsis,
        ];

        if ($this->cover) {
            $data['cover_image'] = $this->cover->store('covers', 'public');
        }

        if ($this->bookId) {
            $book = Book::findOrFail($this->bookId);

            // Jika ada cover baru dan buku punya cover lama di storage, hapus cover lama
            if ($this->cover && $book->cover_image && Storage::disk('public')->exists($book->cover_image)) {
                Storage::disk('public')->delete($book->cover_image);
            }

            $book->update($data);
            session()->flash('message', "Buku '{$book->title}' berhasil diperbarui.");
        } else {
            $book = Book::create($data);
            session()->flash('message', "Buku '{$book->title}' berhasil ditambahkan ke katalog.");
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
        $book = Book::findOrFail($id);
        $title = $book->title;

        if ($book->cover_image && Storage::disk('public')->exists($book->cover_image)) {
            Storage::disk('public')->delete($book->cover_image);
        }

        $book->delete();

        if ($this->bookId === $id) {
            $this->closeModal();
        }

        session()->flash('message', "Buku '{$title}' berhasil dihapus.");
    }

    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    #[Computed]
    public function books()
    {
        return Book::with('category')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('author', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->selectedCategory, function ($query) {
                $query->where('category_id', $this->selectedCategory);
            })
            ->latest()
            ->paginate(10);
    }
};
?>

@php
    $thumbPalette = [
        0 => 'bg-[#144533]',
        1 => 'bg-[#7C2D12]',
        2 => 'bg-[#1E293B]',
        3 => 'bg-[#4C1D95]',
    ];
@endphp

<div x-data x-effect="if (!$wire.isModalOpen) document.body.classList.remove('overflow-hidden')" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 space-y-6">
    <!-- Header Editorial -->
    <div class="border-b border-stone-200/80 pb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="/admin/dashboard" wire:navigate class="text-xs font-semibold text-stone-500 hover:text-[#144533] transition inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Dashboard
                </a>
                <span class="text-xs text-stone-300">/</span>
                <span class="text-xs font-semibold text-[#144533]">Kelola Buku</span>
            </div>
            <div class="font-mono text-[11px] uppercase tracking-[0.14em] text-stone-500 mt-2">
                MEJA PUSTAKAWAN / INVENTARIS PUSTAKA
            </div>
            <h1 class="font-serif text-3xl sm:text-4xl font-semibold text-stone-900 tracking-tight mt-1">
                Kelola Koleksi Buku
            </h1>
            <p class="text-sm text-stone-600 mt-1">
                Tambah judul baru, perbarui data eksemplar, sinopsis, sampul, dan kelola ketersediaan stok.
            </p>
        </div>

        <div class="shrink-0 w-full sm:w-auto">
            <button
                type="button"
                wire:click="openCreateModal"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-[#144533] hover:bg-[#0F3325] text-white px-5 py-2.5 rounded-xl text-xs sm:text-sm font-medium transition shadow-sm cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Buku Baru</span>
            </button>
        </div>
    </div>

    <!-- Alert Notifikasi (Toast Overlay 5 Detik) -->
    @if(session()->has('message'))
        <div
            wire:key="book-toast-{{ md5(session('message')) }}-{{ substr((string) microtime(true), -6) }}"
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 5000)"
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2 sm:translate-y-0 sm:translate-x-4"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            role="status"
            aria-live="polite"
            class="fixed top-20 left-4 right-4 sm:left-auto sm:right-6 sm:w-full sm:max-w-md z-50 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start justify-between gap-3 shadow-lg"
        >
            <div class="flex items-start gap-2.5 min-w-0">
                <svg class="w-5 h-5 text-emerald-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium [overflow-wrap:anywhere]">{{ session('message') }}</span>
            </div>
            <button type="button" @click="show = false" aria-label="Tutup notifikasi" class="text-emerald-700/70 hover:text-emerald-900 shrink-0 p-0.5 rounded cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- Toolbar: Live Search & Filter -->
    <div class="bg-white border border-stone-200/80 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <!-- Live Search Input -->
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-stone-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                autocomplete="off"
                placeholder="Cari judul buku atau nama penulis..."
                class="w-full pl-10 pr-9 py-2.5 text-sm bg-[#FAF8F5] rounded-xl border border-stone-300 text-stone-900 placeholder-stone-400 focus:bg-white focus:border-[#144533] focus:ring-2 focus:ring-[#144533]/20 focus:outline-none transition"
            >
            @if($search)
                <button
                    type="button"
                    wire:click="$set('search', '')"
                    aria-label="Hapus kata kunci"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-400 hover:text-stone-700 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            @endif
        </div>

        <!-- Filter Kategori -->
        <div class="w-full md:w-64">
            <select
                wire:model.live="selectedCategory"
                class="w-full py-2.5 px-3 text-sm bg-[#FAF8F5] rounded-xl border border-stone-300 text-stone-700 focus:bg-white focus:border-[#144533] focus:ring-2 focus:ring-[#144533]/20 focus:outline-none transition cursor-pointer"
            >
                <option value="">Semua Kategori</option>
                @foreach($this->categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->rack_code }})</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Daftar Buku: Card Grid (HP & Tablet) + Tabel (Desktop) -->
    <div class="bg-white rounded-2xl border border-stone-200/80 shadow-sm overflow-hidden">
        <!-- Mobile & Tablet Card View (< 1024px) -->
        <div class="lg:hidden grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
            @forelse($this->books as $book)
                @php
                    $thumbBg = $thumbPalette[((int) ($book->category_id ?? $book->id)) % count($thumbPalette)];
                @endphp
                <div wire:key="book-card-{{ $book->id }}" class="rounded-xl border border-stone-200/90 bg-[#FAF8F5]/60 p-4 flex flex-col justify-between gap-4 min-w-0">
                    <div class="flex items-start gap-3.5 min-w-0">
                        @if($book->cover_image)
                            <img
                                src="{{ asset('storage/' . $book->cover_image) }}"
                                alt="{{ $book->title }}"
                                class="w-14 h-20 rounded-md shadow-sm object-cover shrink-0 border border-stone-200"
                            >
                        @else
                            <div class="w-14 h-20 rounded-md shadow-sm {{ $thumbBg }} flex flex-col justify-between p-1.5 text-white shrink-0 relative overflow-hidden border-l-2 border-white/20">
                                <span class="font-mono text-[7px] uppercase tracking-tighter text-white/80 truncate leading-none">
                                    {{ $book->category?->rack_code ?? 'UMUM' }}
                                </span>
                                <span class="font-serif text-[9px] font-semibold leading-tight line-clamp-2 my-auto">
                                    {{ $book->title }}
                                </span>
                                <span class="h-0.5 w-4 rounded-full bg-amber-300/80" aria-hidden="true"></span>
                            </div>
                        @endif

                        <div class="min-w-0 flex-1 space-y-1">
                            <h3 class="font-serif text-base font-semibold text-stone-900 leading-snug line-clamp-2 [overflow-wrap:anywhere]">
                                {{ $book->title }}
                            </h3>
                            <p class="text-xs text-stone-500 [overflow-wrap:anywhere]">
                                Penulis: <span class="font-medium text-stone-700">{{ $book->author }}</span>
                            </p>
                            @if($book->synopsis)
                                <p class="text-xs text-stone-500 line-clamp-2 [overflow-wrap:anywhere]">
                                    {{ $book->synopsis }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-3 pt-3 border-t border-stone-200/70">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-1.5 min-w-0">
                                <span class="text-xs font-semibold text-stone-700">{{ $book->category?->name ?? 'Tanpa Kategori' }}</span>
                                <span class="bg-stone-100 text-stone-700 border border-stone-200 font-mono text-[11px] px-2 py-0.5 rounded-md">
                                    {{ $book->category?->rack_code ?? '-' }}
                                </span>
                            </div>
                            @if($book->stock > 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-mono text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                    {{ $book->stock }} Tersedia
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-mono text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200 shrink-0">
                                    Stok Habis
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="openEditModal({{ $book->id }})"
                                class="flex-1 min-h-[38px] inline-flex items-center justify-center gap-1.5 border border-stone-200 bg-white hover:bg-stone-50 text-stone-700 px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Edit</span>
                            </button>
                            <button
                                type="button"
                                wire:click="delete({{ $book->id }})"
                                wire:confirm="Yakin ingin menghapus buku '{{ $book->title }}'?"
                                class="flex-1 min-h-[38px] inline-flex items-center justify-center gap-1.5 border border-red-200 bg-red-50/70 hover:bg-red-100 text-red-700 px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>Hapus</span>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="md:col-span-2 py-12 text-center text-stone-500">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-12 h-12 rounded-full bg-stone-100 text-stone-400 border border-stone-200 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <p class="font-serif text-base font-semibold text-stone-800">Tidak ada buku yang ditemukan.</p>
                        <p class="text-xs text-stone-500 mt-0.5">Coba gunakan kata kunci pencarian lain atau tambahkan judul buku baru.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Desktop Table View (>= 1024px) -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full table-fixed text-left border-collapse">
                <thead>
                    <tr class="bg-[#FAF8F5] border-b border-stone-200 text-[11px] font-mono font-semibold uppercase tracking-wider text-stone-500">
                        <th class="py-3.5 px-6 w-[46%]">Buku</th>
                        <th class="py-3.5 px-6 w-[20%]">Kategori &amp; Rak</th>
                        <th class="py-3.5 px-6 w-[16%] text-center">Stok Eksemplar</th>
                        <th class="py-3.5 px-6 w-[18%] text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200/70 text-sm">
                    @forelse($this->books as $book)
                        @php
                            $thumbBg = $thumbPalette[((int) ($book->category_id ?? $book->id)) % count($thumbPalette)];
                        @endphp
                        <tr wire:key="book-row-{{ $book->id }}" class="hover:bg-[#FAF8F5]/80 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-start gap-3.5 min-w-0">
                                    <!-- Cover Image Thumbnail or Mini Hardcover Book Thumbnail -->
                                    @if($book->cover_image)
                                        <img
                                            src="{{ asset('storage/' . $book->cover_image) }}"
                                            alt="{{ $book->title }}"
                                            class="w-12 h-16 rounded-md shadow-sm object-cover shrink-0 border border-stone-200"
                                        >
                                    @else
                                        <div class="w-12 h-16 rounded-md shadow-sm {{ $thumbBg }} flex flex-col justify-between p-1.5 text-white shrink-0 relative overflow-hidden border-l-2 border-white/20">
                                            <span class="font-mono text-[7px] uppercase tracking-tighter text-white/80 truncate leading-none">
                                                {{ $book->category?->rack_code ?? 'UMUM' }}
                                            </span>
                                            <span class="font-serif text-[9px] font-semibold leading-tight line-clamp-2 my-auto">
                                                {{ $book->title }}
                                            </span>
                                            <span class="h-0.5 w-4 rounded-full bg-amber-300/80" aria-hidden="true"></span>
                                        </div>
                                    @endif

                                    <div class="min-w-0 flex-1">
                                        <div class="font-serif text-base font-semibold text-stone-900 leading-snug line-clamp-1 [overflow-wrap:anywhere]" title="{{ $book->title }}">
                                            {{ $book->title }}
                                        </div>
                                        <div class="text-xs text-stone-500 mt-0.5 truncate">
                                            Penulis: <span class="font-medium text-stone-700">{{ $book->author }}</span>
                                        </div>
                                        @if($book->synopsis)
                                            <p class="text-xs text-stone-500 mt-1 line-clamp-1 [overflow-wrap:anywhere]">
                                                {{ $book->synopsis }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="py-4 px-6 whitespace-nowrap">
                                <div class="font-semibold text-stone-800 truncate">{{ $book->category?->name ?? 'Tanpa Kategori' }}</div>
                                <div>
                                    <span class="bg-stone-100 text-stone-700 border border-stone-200 font-mono text-xs px-2.5 py-0.5 rounded-md inline-flex items-center mt-1">
                                        {{ $book->category?->rack_code ?? '-' }}
                                    </span>
                                </div>
                            </td>

                            <td class="py-4 px-6 text-center whitespace-nowrap">
                                @if($book->stock > 0)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full font-mono text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ $book->stock }} Tersedia
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full font-mono text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                        Stok Habis
                                    </span>
                                @endif
                            </td>

                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        wire:click="openEditModal({{ $book->id }})"
                                        class="inline-flex items-center gap-1 border border-stone-200 bg-white hover:bg-stone-50 text-stone-700 px-2.5 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer"
                                        title="Edit Buku"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="delete({{ $book->id }})"
                                        wire:confirm="Yakin ingin menghapus buku '{{ $book->title }}'?"
                                        class="inline-flex items-center gap-1 border border-red-200 bg-red-50/60 hover:bg-red-100 text-red-700 px-2.5 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer"
                                        title="Hapus Buku"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-stone-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-full bg-stone-100 text-stone-400 border border-stone-200 flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                    </div>
                                    <p class="font-serif text-base font-semibold text-stone-800">Tidak ada buku yang ditemukan.</p>
                                    <p class="text-xs text-stone-500 mt-0.5">Coba gunakan kata kunci pencarian lain atau tambahkan judul buku baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->books->hasPages())
            <div class="p-4 border-t border-stone-200/80 bg-[#FAF8F5]">
                {{ $this->books->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form Tambah / Edit Buku -->
    @if($isModalOpen)
        @teleport('body')
            <div
                class="fixed inset-0 z-[100] overflow-y-auto bg-stone-900/60 backdrop-blur-sm flex min-h-full items-center justify-center p-3 sm:p-6"
                style="z-index: 9999;"
                x-data
                x-init="document.body.classList.add('overflow-hidden'); $cleanup(() => document.body.classList.remove('overflow-hidden'))"
                @keydown.escape.window="document.body.classList.remove('overflow-hidden'); $wire.closeModal()"
            >
                <!-- Backdrop Click to Close -->
                <div class="fixed inset-0 -z-10" @click="document.body.classList.remove('overflow-hidden'); $wire.closeModal()" aria-hidden="true"></div>

                <div class="bg-white rounded-2xl border border-stone-200 shadow-2xl w-full max-w-2xl overflow-hidden my-auto relative">
                <!-- Modal Header -->
                <div class="p-4 sm:p-6 bg-[#FAF8F5] border-b border-stone-200 flex items-center justify-between gap-4">
                    <div>
                        <span class="font-mono text-[10px] uppercase tracking-[0.14em] text-stone-500">
                            Formulir Katalog Pustaka
                        </span>
                        <h3 class="font-serif text-lg sm:text-xl font-semibold text-stone-900 mt-0.5">
                            {{ $bookId ? 'Edit Data Buku' : 'Tambah Buku Baru' }}
                        </h3>
                        <p class="text-xs text-stone-600 mt-0.5">
                            Lengkapi informasi detail buku, kategori rak, jumlah stok, sinopsis, dan unggah sampul buku.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="document.body.classList.remove('overflow-hidden'); $wire.closeModal()"
                        aria-label="Tutup modal"
                        class="p-2 rounded-lg text-stone-400 hover:text-stone-700 hover:bg-stone-200/60 transition cursor-pointer shrink-0"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body / Form -->
                <form wire:submit="save" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Judul Buku -->
                        <div class="md:col-span-2">
                            <label class="block font-mono text-[11px] font-semibold uppercase tracking-wider text-stone-600 mb-1.5">
                                Judul Buku <span class="text-red-600">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="title"
                                autocomplete="off"
                                placeholder="Contoh: Pemrograman Web Modern dengan Laravel"
                                class="w-full px-3.5 py-2.5 bg-[#FAF8F5] rounded-xl border {{ $errors->has('title') ? 'border-red-400 focus:border-red-600 focus:ring-red-200' : 'border-stone-300 focus:bg-white focus:border-[#144533] focus:ring-[#144533]/20' }} text-sm text-stone-900 placeholder-stone-400 focus:ring-2 focus:outline-none transition"
                            >
                            @error('title')
                                <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Penulis -->
                        <div>
                            <label class="block font-mono text-[11px] font-semibold uppercase tracking-wider text-stone-600 mb-1.5">
                                Penulis / Pengarang <span class="text-red-600">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="author"
                                autocomplete="off"
                                placeholder="Contoh: Taylor Otwell"
                                class="w-full px-3.5 py-2.5 bg-[#FAF8F5] rounded-xl border {{ $errors->has('author') ? 'border-red-400 focus:border-red-600 focus:ring-red-200' : 'border-stone-300 focus:bg-white focus:border-[#144533] focus:ring-[#144533]/20' }} text-sm text-stone-900 placeholder-stone-400 focus:ring-2 focus:outline-none transition"
                            >
                            @error('author')
                                <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Kategori & Rak -->
                        <div>
                            <label class="block font-mono text-[11px] font-semibold uppercase tracking-wider text-stone-600 mb-1.5">
                                Kategori &amp; Rak <span class="text-red-600">*</span>
                            </label>
                            <select
                                wire:model="category_id"
                                class="w-full px-3.5 py-2.5 bg-[#FAF8F5] rounded-xl border {{ $errors->has('category_id') ? 'border-red-400 focus:border-red-600 focus:ring-red-200' : 'border-stone-300 focus:bg-white focus:border-[#144533] focus:ring-[#144533]/20' }} text-sm text-stone-900 focus:ring-2 focus:outline-none transition"
                            >
                                <option value="">Pilih Kategori</option>
                                @foreach($this->categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }} ({{ $cat->rack_code }})</option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Stok -->
                        <div>
                            <label class="block font-mono text-[11px] font-semibold uppercase tracking-wider text-stone-600 mb-1.5">
                                Jumlah Stok Eksemplar <span class="text-red-600">*</span>
                            </label>
                            <input
                                type="number"
                                min="0"
                                wire:model="stock"
                                class="w-full px-3.5 py-2.5 bg-[#FAF8F5] rounded-xl border {{ $errors->has('stock') ? 'border-red-400 focus:border-red-600 focus:ring-red-200' : 'border-stone-300 focus:bg-white focus:border-[#144533] focus:ring-[#144533]/20' }} text-sm font-mono text-stone-900 focus:ring-2 focus:outline-none transition"
                            >
                            @error('stock')
                                <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Upload Cover Image -->
                        <div>
                            <label class="block font-mono text-[11px] font-semibold uppercase tracking-wider text-stone-600 mb-1.5">
                                Upload Sampul (Cover Image)
                            </label>
                            <input
                                type="file"
                                wire:model="cover"
                                accept="image/png,image/jpeg,image/webp,image/jpg"
                                class="file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#144533]/10 file:text-[#144533] hover:file:bg-[#144533]/20 border border-dashed border-stone-300 rounded-xl p-2 bg-[#FAF8F5] w-full text-xs text-stone-600 cursor-pointer"
                            >
                            <div class="flex items-center justify-between text-[11px] text-stone-500 mt-1">
                                <span>Format: JPG, JPEG, PNG, WebP</span>
                                <span>Maks. 10 MB</span>
                            </div>
                            <div wire:loading wire:target="cover" class="text-xs text-[#144533] mt-1.5 flex items-center gap-1.5 font-medium">
                                <svg class="animate-spin h-3.5 w-3.5 text-[#144533]" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Mengunggah gambar...
                            </div>
                            @error('cover')
                                <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Preview Sampul (Jika ada) -->
                    @if($cover || $existingCover)
                        <div class="p-3 bg-[#FAF8F5] rounded-xl border border-stone-200 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-stone-600 font-medium">Preview Sampul:</span>
                                @if($cover)
                                    @php
                                        $previewUrl = null;
                                        try {
                                            $previewUrl = $cover->temporaryUrl();
                                        } catch (\Throwable $e) {
                                            $previewUrl = null;
                                        }
                                    @endphp
                                    @if($previewUrl)
                                        <img src="{{ $previewUrl }}" alt="Preview Baru" class="w-12 h-16 object-cover rounded border border-stone-300 shadow-sm">
                                    @endif
                                    <span class="text-xs text-emerald-700 font-semibold">(File baru terpilih)</span>
                                @elseif($existingCover)
                                    <img src="{{ asset('storage/' . $existingCover) }}" alt="Sampul Saat Ini" class="w-12 h-16 object-cover rounded border border-stone-300 shadow-sm">
                                    <span class="text-xs text-stone-500">(Sampul saat ini)</span>
                                @endif
                            </div>
                            @if($cover)
                                <button
                                    type="button"
                                    wire:click="removeCover"
                                    class="text-xs text-stone-500 hover:text-red-600 font-medium transition cursor-pointer"
                                >
                                    Batal Pilih
                                </button>
                            @endif
                        </div>
                    @endif

                    <!-- Sinopsis -->
                    <div>
                        <label class="block font-mono text-[11px] font-semibold uppercase tracking-wider text-stone-600 mb-1.5">
                            Sinopsis / Deskripsi Buku
                        </label>
                        <textarea
                            wire:model="synopsis"
                            rows="3"
                            placeholder="Tulis ringkasan singkat isi buku..."
                            class="w-full px-3.5 py-2.5 bg-[#FAF8F5] rounded-xl border {{ $errors->has('synopsis') ? 'border-red-400 focus:border-red-600 focus:ring-red-200' : 'border-stone-300 focus:bg-white focus:border-[#144533] focus:ring-[#144533]/20' }} text-sm text-stone-900 placeholder-stone-400 focus:ring-2 focus:outline-none transition"
                        ></textarea>
                        @error('synopsis')
                            <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-4 border-t border-stone-200/80 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5">
                        <button
                            type="button"
                            @click="document.body.classList.remove('overflow-hidden'); $wire.closeModal()"
                            class="w-full sm:w-auto text-center px-4 py-2.5 text-xs font-semibold text-stone-700 bg-stone-100 hover:bg-stone-200 border border-stone-200 rounded-xl transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 bg-[#144533] hover:bg-[#0F3325] text-white px-5 py-2.5 rounded-xl text-xs font-medium transition shadow-sm disabled:opacity-50 cursor-pointer"
                        >
                            <span wire:loading.remove wire:target="save">
                                {{ $bookId ? 'Perbarui Buku' : 'Simpan Buku' }}
                            </span>
                            <span wire:loading wire:target="save" class="inline-flex items-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endteleport
    @endif
</div>
