<?php

use App\Models\Book;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $bookId = null;
    public $category_id = '';
    public $title = '';
    public $author = '';
    public $stock = 0;
    public $synopsis = '';
    public $cover;
    public $existingCover = null;

    public $isModalOpen = false;
    public $isEditing = false;
    public $search = '';
    public $categoryFilter = '';

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403);
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    protected function rules()
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'stock' => 'required|integer|min:0',
            'synopsis' => 'nullable|string',
            'cover' => 'nullable|image|max:2048', // Maksimal 2MB
        ];
    }

    protected $messages = [
        'category_id.required' => 'Kategori rak buku wajib dipilih.',
        'category_id.exists' => 'Kategori yang dipilih tidak valid.',
        'title.required' => 'Judul buku wajib diisi.',
        'author.required' => 'Penulis buku wajib diisi.',
        'stock.required' => 'Jumlah stok eksemplar wajib diisi.',
        'stock.integer' => 'Stok buku harus berupa bilangan bulat.',
        'stock.min' => 'Stok buku tidak boleh kurang dari 0.',
        'cover.image' => 'File sampul harus berupa gambar (JPG, PNG, WebP).',
        'cover.max' => 'Ukuran file gambar sampul maksimal 2 MB.',
    ];

    public function openModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->resetForm();
        $this->isModalOpen = false;
    }

    public function resetForm()
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
            'isEditing'
        ]);
        $this->resetValidation();
    }

    public function edit($id)
    {
        $book = Book::findOrFail($id);
        $this->bookId = $book->id;
        $this->category_id = $book->category_id;
        $this->title = $book->title;
        $this->author = $book->author;
        $this->stock = $book->stock;
        $this->synopsis = $book->synopsis;
        $this->existingCover = $book->cover_image;
        $this->cover = null;
        $this->isEditing = true;
        $this->isModalOpen = true;
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate();

        $data = [
            'category_id' => $this->category_id,
            'title' => trim($this->title),
            'author' => trim($this->author),
            'stock' => (int) $this->stock,
            'synopsis' => $this->synopsis ? trim($this->synopsis) : null,
        ];

        // Upload cover_image ke folder covers disk public
        if ($this->cover) {
            $data['cover_image'] = $this->cover->store('covers', 'public');
        }

        if ($this->isEditing && $this->bookId) {
            $book = Book::findOrFail($this->bookId);

            // Hapus cover lama dari disk public jika ada cover baru yang diunggah
            if ($this->cover && $book->cover_image && Storage::disk('public')->exists($book->cover_image)) {
                Storage::disk('public')->delete($book->cover_image);
            }

            $book->update($data);
            session()->flash('success', "Buku '{$book->title}' berhasil diperbarui.");
        } else {
            Book::create($data);
            session()->flash('success', 'Buku baru berhasil ditambahkan ke inventaris perpustakaan.');
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        $book = Book::withCount(['borrowings' => function ($query) {
            $query->where('status', 'dipinjam');
        }])->findOrFail($id);

        if ($book->borrowings_count > 0) {
            session()->flash('error', "Buku '{$book->title}' tidak dapat dihapus karena masih sedang dipinjam oleh {$book->borrowings_count} siswa.");
            return;
        }

        if ($book->cover_image && Storage::disk('public')->exists($book->cover_image)) {
            Storage::disk('public')->delete($book->cover_image);
        }

        $book->delete();
        session()->flash('success', "Buku '{$book->title}' berhasil dihapus dari perpustakaan.");
    }

    public function render()
    {
        $books = Book::with('category')
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('author', 'like', '%' . $this->search . '%')
                        ->orWhereHas('category', function ($catQuery) {
                            $catQuery->where('name', 'like', '%' . $this->search . '%')
                                     ->orWhere('rack_code', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->categoryFilter, function ($query) {
                $query->where('category_id', $this->categoryFilter);
            })
            ->latest('id')
            ->paginate(8);

        return $this->view([
            'books' => $books,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                    Inventaris & Sirkulasi
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Kelola Koleksi Buku</h1>
            <p class="text-slate-500 text-sm mt-0.5">Manajemen data katalog, unggah sampul, pembaruan stok eksemplar, dan informasi buku.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.dashboard') }}" wire:navigate
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs sm:text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
                ← Dashboard
            </a>
            <button type="button" wire:click="openModal"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-sm shadow-indigo-200">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Buku Baru
            </button>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session()->has('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" wire:click="$set('success', null)" class="text-emerald-600 hover:text-emerald-900 text-xs font-bold">×</button>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" wire:click="$set('error', null)" class="text-rose-600 hover:text-rose-900 text-xs font-bold">×</button>
        </div>
    @endif

    {{-- Filter & Live Search Toolbar --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col md:flex-row items-center justify-between gap-3">
        {{-- Live Search --}}
        <div class="relative w-full md:w-96">
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Cari judul buku, penulis, atau kategori..."
                   class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder:text-slate-400">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>

        {{-- Category Filter Dropdown --}}
        <div class="flex items-center gap-2 w-full md:w-auto">
            <select wire:model.live="categoryFilter"
                    class="w-full md:w-48 px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Semua Kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->rack_code }})</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Tabel Daftar Koleksi Buku --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50/80 text-xs font-semibold uppercase text-slate-500 tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 w-16">Sampul</th>
                        <th class="px-5 py-3.5">Judul Buku & Penulis</th>
                        <th class="px-5 py-3.5">Kategori / Rak</th>
                        <th class="px-5 py-3.5 text-center">Stok Fisik</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($books as $book)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            {{-- Cover Thumbnail --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="w-12 h-16 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center shadow-xs">
                                    @if($book->cover_image)
                                        <img src="{{ filter_var($book->cover_image, FILTER_VALIDATE_URL) ? $book->cover_image : asset('storage/' . $book->cover_image) }}"
                                             alt="{{ $book->title }}"
                                             class="w-full h-full object-cover">
                                    @else
                                        <span class="text-xl">📚</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Title & Author & Synopsis Snippet --}}
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900 line-clamp-1 text-base">{{ $book->title }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Penulis: <span class="font-medium text-slate-700">{{ $book->author }}</span></div>
                                @if($book->synopsis)
                                    <p class="text-xs text-slate-400 mt-1 line-clamp-1 max-w-md">{{ $book->synopsis }}</p>
                                @endif
                            </td>

                            {{-- Category & Rack Code --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="font-medium text-slate-800 text-xs">{{ $book->category->name ?? '-' }}</div>
                                <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded text-[11px] font-mono font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                                    {{ $book->category->rack_code ?? 'Tanpa Rak' }}
                                </span>
                            </td>

                            {{-- Stock Indicator --}}
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                @if($book->stock > 0)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ $book->stock }} Tersedia
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        Habis
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-5 py-4 whitespace-nowrap text-right text-xs">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" wire:click="edit({{ $book->id }})"
                                            class="px-2.5 py-1.5 rounded-lg text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 font-medium transition-colors border border-transparent hover:border-indigo-200">
                                        Edit
                                    </button>
                                    <button type="button" wire:click="delete({{ $book->id }})"
                                            wire:confirm="Yakin ingin menghapus buku '{{ $book->title }}'? Data yang dihapus tidak dapat dipulihkan."
                                            class="px-2.5 py-1.5 rounded-lg text-rose-600 hover:text-rose-800 hover:bg-rose-50 font-medium transition-colors border border-transparent hover:border-rose-200">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <p class="font-medium text-slate-500">Tidak ada buku yang cocok dengan pencarian.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($books->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $books->links() }}
            </div>
        @endif
    </div>

    {{-- Form Modal Tambah & Edit Buku --}}
    @if($isModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto bg-slate-900/60 backdrop-blur-xs"
             x-data
             x-on:keydown.escape.window="$wire.closeModal()">
            <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden my-8"
                 @click.away="$wire.closeModal()">
                {{-- Modal Header --}}
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            {{ $isEditing ? 'Edit Koleksi Buku' : 'Tambah Buku Baru' }}
                        </h2>
                        <p class="text-xs text-slate-500">Isi kelengkapan data informasi dan jumlah stok buku.</p>
                    </div>
                    <button type="button" wire:click="closeModal"
                            class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal Body Form --}}
                <form wire:submit.prevent="save" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Kategori Buku --}}
                        <div class="sm:col-span-2">
                            <label for="category_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Kategori & Kode Rak <span class="text-rose-500">*</span>
                            </label>
                            <select id="category_id" wire:model="category_id"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">-- Pilih Kategori Rak --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }} (Rak: {{ $category->rack_code }})</option>
                                @endforeach
                            </select>
                            @error('category_id') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Judul Buku --}}
                        <div class="sm:col-span-2">
                            <label for="title" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Judul Buku <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="title" wire:model="title"
                                   placeholder="Contoh: Pemrograman Web Modern"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @error('title') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Penulis --}}
                        <div>
                            <label for="author" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Penulis / Pengarang <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="author" wire:model="author"
                                   placeholder="Contoh: Taylor Otwell"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @error('author') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Stok Eksemplar --}}
                        <div>
                            <label for="stock" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Stok Eksemplar <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" id="stock" min="0" wire:model="stock"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            @error('stock') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Sinopsis / Deskripsi --}}
                        <div class="sm:col-span-2">
                            <label for="synopsis" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Sinopsis Ringkas
                            </label>
                            <textarea id="synopsis" rows="3" wire:model="synopsis"
                                      placeholder="Uraian singkat ringkasan isi buku..."
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            @error('synopsis') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Upload Cover Image --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                File Sampul Buku (cover_image)
                            </label>
                            <div class="flex items-start gap-4">
                                {{-- Preview Area --}}
                                <div class="w-16 h-20 rounded-lg bg-slate-100 border border-slate-300 flex items-center justify-center overflow-hidden flex-shrink-0">
                                    @if ($cover)
                                        <img src="{{ $cover->temporaryUrl() }}" class="w-full h-full object-cover">
                                    @elseif ($existingCover)
                                        <img src="{{ filter_var($existingCover, FILTER_VALIDATE_URL) ? $existingCover : asset('storage/' . $existingCover) }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-slate-400 text-xs">No Cover</span>
                                    @endif
                                </div>

                                <div class="flex-1">
                                    <input type="file" wire:model="cover" accept="image/*"
                                           class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                    <p class="text-[11px] text-slate-400 mt-1">Format: JPG, PNG, atau WebP (Maks. 2MB). Disimpan ke direktori publik.</p>
                                    <div wire:loading wire:target="cover" class="text-xs text-indigo-600 font-semibold mt-1">
                                        Mengunggah pratinjau gambar...
                                    </div>
                                    @error('cover') <p class="text-rose-600 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer Actions --}}
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" wire:click="closeModal"
                                class="px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200 disabled:opacity-50">
                            <span wire:loading.remove wire:target="save, cover">
                                {{ $isEditing ? 'Simpan Perubahan' : 'Tambahkan Buku' }}
                            </span>
                            <span wire:loading wire:target="save, cover" class="inline-flex items-center gap-1.5">
                                <svg class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
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
