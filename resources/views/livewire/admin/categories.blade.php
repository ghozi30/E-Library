<?php

use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $categoryId = null;
    public $name = '';
    public $rack_code = '';
    public $isEditing = false;
    public $search = '';

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

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'rack_code' => 'required|string|max:50|unique:categories,rack_code,' . ($this->categoryId ?? 'NULL') . ',id',
        ];
    }

    protected $messages = [
        'name.required' => 'Nama kategori wajib diisi.',
        'rack_code.required' => 'Kode lokasi rak wajib diisi.',
        'rack_code.unique' => 'Kode rak ini sudah digunakan oleh kategori lain.',
    ];

    public function save()
    {
        $this->validate();

        if ($this->isEditing && $this->categoryId) {
            $category = Category::findOrFail($this->categoryId);
            $category->update([
                'name' => trim($this->name),
                'rack_code' => strtoupper(trim($this->rack_code)),
            ]);

            session()->flash('success', 'Kategori dan kode rak berhasil diperbarui.');
        } else {
            Category::create([
                'name' => trim($this->name),
                'rack_code' => strtoupper(trim($this->rack_code)),
            ]);

            session()->flash('success', 'Kategori dan kode rak baru berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->rack_code = $category->rack_code;
        $this->isEditing = true;
        $this->resetValidation();
    }

    public function resetForm()
    {
        $this->reset(['name', 'rack_code', 'categoryId', 'isEditing']);
        $this->resetValidation();
    }

    public function delete($id)
    {
        $category = Category::withCount('books')->findOrFail($id);

        if ($category->books_count > 0) {
            session()->flash('error', "Kategori '{$category->name}' tidak dapat dihapus karena masih terkait dengan {$category->books_count} buku.");
            return;
        }

        $category->delete();
        session()->flash('success', "Kategori '{$category->name}' berhasil dihapus.");

        if ($this->categoryId === $id) {
            $this->resetForm();
        }
    }

    public function render()
    {
        $categories = Category::withCount('books')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('rack_code', 'like', '%' . $this->search . '%');
            })
            ->latest('id')
            ->paginate(10);

        return $this->view([
            'categories' => $categories,
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
                    Master Data Perpustakaan
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Kelola Kategori & Kode Rak</h1>
            <p class="text-slate-500 text-sm mt-0.5">Atur klasifikasi kategori buku dan penempatan rak koleksi perpustakaan dalam satu layar.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}" wire:navigate
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs sm:text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
                ← Kembali ke Dashboard
            </a>
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

    {{-- Grid Layout 1 Layar: Form di Kiri, Tabel di Kanan --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        {{-- Kolom Kiri: Form Tambah/Edit Kategori & Kode Rak --}}
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm sticky top-24">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                <div>
                    <h2 class="text-base font-bold text-slate-900">
                        {{ $isEditing ? 'Edit Kategori & Rak' : 'Tambah Kategori & Rak' }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $isEditing ? 'Perbarui data kategori yang dipilih.' : 'Tambahkan kategori baru dan lokasi rak.' }}
                    </p>
                </div>
                @if($isEditing)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">
                        Mode Edit
                    </span>
                @endif
            </div>

            <form wire:submit.prevent="save" class="space-y-4">
                {{-- Field: Nama Kategori --}}
                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nama Kategori <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="name" wire:model="name"
                           placeholder="Contoh: Teknologi Informasi"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors placeholder:text-slate-400">
                    @error('name')
                        <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Field: Kode Rak --}}
                <div>
                    <label for="rack_code" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                        Kode Lokasi Rak <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="rack_code" wire:model="rack_code"
                           placeholder="Contoh: RAK-TEK-01"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-mono uppercase focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors placeholder:text-slate-400">
                    <p class="text-[11px] text-slate-400 mt-1">Kode unik untuk menentukan letak fisik buku di perpustakaan.</p>
                    @error('rack_code')
                        <p class="text-rose-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tombol Aksi Form --}}
                <div class="pt-3 flex items-center gap-2">
                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="flex-1 inline-flex justify-center items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-sm shadow-indigo-200 disabled:opacity-50">
                        <span wire:loading.remove wire:target="save">
                            {{ $isEditing ? 'Perbarui Kategori' : 'Simpan Kategori' }}
                        </span>
                        <span wire:loading wire:target="save" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Memproses...
                        </span>
                    </button>

                    @if($isEditing)
                        <button type="button" wire:click="resetForm"
                                class="px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                            Batal
                        </button>
                    @endif
                </div>
            </form>
        </div>

        {{-- Kolom Kanan: Tabel Daftar Kategori & Kode Rak --}}
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            {{-- Search & Subheader --}}
            <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Daftar Kategori & Rak</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Daftar seluruh rak dan jumlah buku yang terhubung.</p>
                </div>
                <div class="relative w-full sm:w-64">
                    <input type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Cari kategori / kode rak..."
                           class="w-full pl-9 pr-3.5 py-2 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder:text-slate-400">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/80 text-xs font-semibold uppercase text-slate-500 tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3.5 w-12 text-center">#</th>
                            <th class="px-5 py-3.5">Nama Kategori</th>
                            <th class="px-5 py-3.5">Kode Rak</th>
                            <th class="px-5 py-3.5 text-center">Jumlah Buku</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($categories as $index => $cat)
                            <tr class="hover:bg-slate-50/60 transition-colors {{ $categoryId === $cat->id ? 'bg-indigo-50/40' : '' }}">
                                <td class="px-5 py-4 text-center text-xs text-slate-400 font-medium">
                                    {{ $categories->firstItem() + $index }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-900">{{ $cat->name }}</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                                        {{ $cat->rack_code }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $cat->books_count > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $cat->books_count }} Judul
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right text-xs">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" wire:click="edit({{ $cat->id }})"
                                                class="px-2.5 py-1.5 rounded-lg text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 font-medium transition-colors border border-transparent hover:border-indigo-200">
                                            Edit
                                        </button>
                                        <button type="button" wire:click="delete({{ $cat->id }})"
                                                wire:confirm="Yakin ingin menghapus kategori '{{ $cat->name }}'? Pastikan tidak ada buku yang terhubung."
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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        <p class="font-medium text-slate-500">Tidak ada kategori yang ditemukan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($categories->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
