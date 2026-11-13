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
            'cover' => 'nullable|image|max:2048',
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $firstCategory = Category::first();
        if ($firstCategory) {
            $this->category_id = $firstCategory->id;
        }
        $this->isModalOpen = true;
    }

    public function openEditModal(int $id): void
    {
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
        $book = Book::findOrFail($id);
        $title = $book->title;

        if ($book->cover_image && Storage::disk('public')->exists($book->cover_image)) {
            Storage::disk('public')->delete($book->cover_image);
        }

        $book->delete();
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

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="/admin/dashboard" wire:navigate class="text-xs font-semibold text-slate-400 hover:text-indigo-600 transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Dashboard
                </a>
                <span class="text-xs text-slate-300">/</span>
                <span class="text-xs font-semibold text-indigo-600">Kelola Buku</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Kelola Koleksi Buku</h1>
            <p class="text-sm text-slate-500 mt-0.5">Tambah judul baru, perbarui data eksemplar, sinopsis, sampul, dan kelola ketersediaan stok.</p>
        </div>

        <div>
            <button
                type="button"
                wire:click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition shadow-md shadow-indigo-100"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Buku Baru
            </button>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    @if(session()->has('message'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- Toolbar: Live Search & Filter -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <!-- Live Search Input -->
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari judul buku atau nama penulis..."
                class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none transition"
            >
            @if($search)
                <button
                    type="button"
                    wire:click="$set('search', '')"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            @endif
        </div>

        <!-- Filter Kategori -->
        <div class="w-full md:w-64">
            <select
                wire:model.live="selectedCategory"
                class="w-full py-2 px-3 text-sm rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none transition bg-white text-slate-700"
            >
                <option value="">Semua Kategori</option>
                @foreach($this->categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->rack_code }})</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Tabel Buku -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">Buku</th>
                        <th class="py-3.5 px-6">Kategori & Rak</th>
                        <th class="py-3.5 px-6 text-center">Stok Eksemplar</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($this->books as $book)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-4 px-6">
                                <div class="flex items-start gap-3.5">
                                    <!-- Cover Image Thumbnail -->
                                    <div class="w-12 h-16 rounded-lg bg-slate-100 overflow-hidden flex-shrink-0 border border-slate-200 flex items-center justify-center">
                                        @if($book->cover_image)
                                            <img
                                                src="{{ asset('storage/' . $book->cover_image) }}"
                                                alt="{{ $book->title }}"
                                                class="w-full h-full object-cover"
                                            >
                                        @else
                                            <div class="w-full h-full flex flex-col items-center justify-center text-slate-300 bg-slate-50">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 leading-snug line-clamp-1" title="{{ $book->title }}">
                                            {{ $book->title }}
                                        </div>
                                        <div class="text-xs text-slate-500 mt-0.5">
                                            Penulis: <span class="font-medium text-slate-700">{{ $book->author }}</span>
                                        </div>
                                        @if($book->synopsis)
                                            <p class="text-xs text-slate-400 mt-1 line-clamp-1 max-w-md">
                                                {{ $book->synopsis }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="py-4 px-6 whitespace-nowrap">
                                <div class="font-semibold text-slate-800">{{ $book->category?->name ?? 'Tanpa Kategori' }}</div>
                                <div class="font-mono text-xs text-indigo-600 mt-0.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-indigo-50 border border-indigo-100">
                                        {{ $book->category?->rack_code ?? '-' }}
                                    </span>
                                </div>
                            </td>

                            <td class="py-4 px-6 text-center whitespace-nowrap">
                                @if($book->stock > 0)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ $book->stock }} Tersedia
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Stok Habis
                                    </span>
                                @endif
                            </td>

                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        wire:click="openEditModal({{ $book->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:text-indigo-600 bg-slate-100 hover:bg-indigo-50 rounded-lg transition"
                                        title="Edit Buku"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="delete({{ $book->id }})"
                                        wire:confirm="Yakin ingin menghapus buku '{{ $book->title }}'?"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg transition"
                                        title="Hapus Buku"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <p class="text-sm font-medium">Tidak ada buku yang ditemukan.</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Coba gunakan kata kunci pencarian lain atau tambahkan judul buku baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->books->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $this->books->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form Tambah / Edit Buku -->
    @if($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-2xl overflow-hidden transform transition-all animate-fade-in my-8">
                <!-- Modal Header -->
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">
                            {{ $bookId ? 'Edit Data Buku' : 'Tambah Buku Baru' }}
                        </h3>
                        <p class="text-xs text-slate-500">
                            Lengkapi informasi detail buku, kategori rak, jumlah stok, sinopsis, dan unggah sampul buku.
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="text-slate-400 hover:text-slate-600 transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body / Form -->
                <form wire:submit="save" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Judul Buku -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Judul Buku <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="title"
                                placeholder="Contoh: Pemrograman Web Modern dengan Laravel"
                                class="w-full px-3.5 py-2.5 rounded-xl border {{ $errors->has('title') ? 'border-rose-400' : 'border-slate-200' }} text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none transition"
                            >
                            @error('title')
                                <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Penulis -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Penulis / Pengarang <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="author"
                                placeholder="Contoh: Taylor Otwell"
                                class="w-full px-3.5 py-2.5 rounded-xl border {{ $errors->has('author') ? 'border-rose-400' : 'border-slate-200' }} text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none transition"
                            >
                            @error('author')
                                <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Kategori & Rak -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Kategori & Rak <span class="text-rose-500">*</span>
                            </label>
                            <select
                                wire:model="category_id"
                                class="w-full px-3.5 py-2.5 rounded-xl border {{ $errors->has('category_id') ? 'border-rose-400' : 'border-slate-200' }} text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none transition bg-white"
                            >
                                <option value="">Pilih Kategori</option>
                                @foreach($this->categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }} (Rak: {{ $cat->rack_code }})</option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Stok -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Jumlah Stok Eksemplar <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="number"
                                min="0"
                                wire:model="stock"
                                class="w-full px-3.5 py-2.5 rounded-xl border {{ $errors->has('stock') ? 'border-rose-400' : 'border-slate-200' }} text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none transition"
                            >
                            @error('stock')
                                <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Upload Cover Image -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                Upload Sampul (Cover Image)
                            </label>
                            <input
                                type="file"
                                wire:model="cover"
                                accept="image/*"
                                class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            >
                            <div wire:loading wire:target="cover" class="text-xs text-indigo-600 mt-1 flex items-center gap-1">
                                <svg class="animate-spin h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Mengunggah gambar...
                            </div>
                            @error('cover')
                                <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Preview Sampul (Jika ada) -->
                    @if($cover || $existingCover)
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-3">
                            <span class="text-xs text-slate-500 font-medium">Preview Sampul:</span>
                            @if($cover)
                                <img src="{{ $cover->temporaryUrl() }}" alt="Preview Baru" class="w-12 h-16 object-cover rounded border border-slate-300">
                                <span class="text-xs text-emerald-600 font-semibold">(File baru terpilih)</span>
                            @elseif($existingCover)
                                <img src="{{ asset('storage/' . $existingCover) }}" alt="Sampul Saat Ini" class="w-12 h-16 object-cover rounded border border-slate-300">
                                <span class="text-xs text-slate-500">(Sampul saat ini)</span>
                            @endif
                        </div>
                    @endif

                    <!-- Sinopsis -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Sinopsis / Deskripsi Buku
                        </label>
                        <textarea
                            wire:model="synopsis"
                            rows="3"
                            placeholder="Tulis ringkasan singkat isi buku..."
                            class="w-full px-3.5 py-2.5 rounded-xl border {{ $errors->has('synopsis') ? 'border-rose-400' : 'border-slate-200' }} text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none transition"
                        ></textarea>
                        @error('synopsis')
                            <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition shadow-md shadow-indigo-100 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="save">
                                {{ $bookId ? 'Perbarui Buku' : 'Simpan Buku' }}
                            </span>
                            <span wire:loading wire:target="save" class="inline-flex items-center gap-1">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
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
    @endif
</div>
