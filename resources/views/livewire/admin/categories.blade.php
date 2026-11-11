<?php

use App\Models\Category;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public ?int $categoryId = null;
    public string $name = '';
    public string $rack_code = '';

    public function mount(): void
    {
        if (! auth()->check() || auth()->user()->role !== 'admin') {
            abort(403);
        }
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'rack_code' => 'required|string|max:50',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->categoryId) {
            $category = Category::findOrFail($this->categoryId);
            $category->update($validated);
            session()->flash('message', "Kategori '{$category->name}' berhasil diperbarui.");
        } else {
            Category::create($validated);
            session()->flash('message', 'Kategori baru berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $category = Category::findOrFail($id);
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->rack_code = $category->rack_code;
        $this->resetErrorBag();
    }

    public function resetForm(): void
    {
        $this->reset(['categoryId', 'name', 'rack_code']);
        $this->resetErrorBag();
    }

    public function delete(int $id): void
    {
        $category = Category::withCount('books')->findOrFail($id);
        $name = $category->name;
        $category->delete();

        if ($this->categoryId === $id) {
            $this->resetForm();
        }

        session()->flash('message', "Kategori '{$name}' berhasil dihapus.");
    }

    #[Computed]
    public function categories()
    {
        return Category::withCount('books')->latest()->get();
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
                <span class="text-xs font-semibold text-indigo-600">Kelola Rak</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Kelola Kategori & Kode Rak</h1>
            <p class="text-sm text-slate-500 mt-0.5">Atur pengelompokan buku dan penataan lokasi rak buku perpustakaan.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">
                Total Kategori: {{ $this->categories->count() }}
            </span>
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

    <!-- Layout 2 Kolom (1 Layar) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Kolom Kiri: Form Tambah / Edit -->
        <div class="lg:col-span-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm sticky top-24">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg {{ $categoryId ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }} flex items-center justify-center font-bold">
                        @if($categoryId)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        @endif
                    </div>
                    <h2 class="text-base font-bold text-slate-900">
                        {{ $categoryId ? 'Edit Kategori & Rak' : 'Tambah Kategori & Rak' }}
                    </h2>
                </div>

                @if($categoryId)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-medium">Mode Edit</span>
                @endif
            </div>

            <form wire:submit="save" class="space-y-4">
                <!-- Nama Kategori -->
                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nama Kategori <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        wire:model="name"
                        placeholder="Contoh: Teknologi, Sains, Novel"
                        class="w-full px-3.5 py-2.5 rounded-xl border {{ $errors->has('name') ? 'border-rose-400 focus:ring-rose-200 focus:border-rose-500' : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-200' }} text-sm focus:outline-none focus:ring-2 transition"
                    >
                    @error('name')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kode Rak -->
                <div>
                    <label for="rack_code" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Kode Rak <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="rack_code"
                        wire:model="rack_code"
                        placeholder="Contoh: RAK-TEK-01, RAK-NOV-02"
                        class="w-full px-3.5 py-2.5 rounded-xl border {{ $errors->has('rack_code') ? 'border-rose-400 focus:ring-rose-200 focus:border-rose-500' : 'border-slate-200 focus:border-indigo-500 focus:ring-indigo-200' }} text-sm font-mono focus:outline-none focus:ring-2 transition uppercase"
                    >
                    @error('rack_code')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tombol Aksi Form -->
                <div class="pt-2 flex items-center gap-2">
                    <button
                        type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-semibold text-white {{ $categoryId ? 'bg-amber-600 hover:bg-amber-700 shadow-amber-100' : 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-100' }} rounded-xl transition shadow-md disabled:opacity-50"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="save">
                            {{ $categoryId ? 'Perbarui Kategori' : 'Simpan Kategori' }}
                        </span>
                        <span wire:loading wire:target="save" class="inline-flex items-center gap-1">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Menyimpan...
                        </span>
                    </button>

                    @if($categoryId)
                        <button
                            type="button"
                            wire:click="resetForm"
                            class="px-3.5 py-2.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                        >
                            Batal
                        </button>
                    @endif
                </div>
            </form>
        </div>

        <!-- Kolom Kanan: Tabel Daftar Kategori & Jumlah Buku -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Daftar Kategori & Rak</h2>
                    <p class="text-xs text-slate-500">Semua kategori buku terdaftar beserta nomor rak dan jumlah koleksi buku.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3.5 px-6">Nama Kategori</th>
                            <th class="py-3.5 px-6">Kode Rak</th>
                            <th class="py-3.5 px-6 text-center">Jumlah Buku</th>
                            <th class="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($this->categories as $category)
                            <tr class="hover:bg-slate-50/70 transition {{ $categoryId === $category->id ? 'bg-indigo-50/40' : '' }}">
                                <td class="py-4 px-6">
                                    <div class="font-semibold text-slate-900">{{ $category->name }}</div>
                                    <div class="text-[11px] text-slate-400">Dibuat {{ $category->created_at?->diffForHumans() }}</div>
                                </td>
                                <td class="py-4 px-6 font-mono text-xs font-medium text-indigo-700">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 border border-indigo-100">
                                        {{ $category->rack_code }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $category->books_count > 0 ? 'bg-slate-100 text-slate-800' : 'bg-slate-50 text-slate-400' }}">
                                        {{ $category->books_count }} Judul
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            wire:click="edit({{ $category->id }})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:text-indigo-600 bg-slate-100 hover:bg-indigo-50 rounded-lg transition"
                                            title="Edit Kategori"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="delete({{ $category->id }})"
                                            wire:confirm="Yakin ingin menghapus kategori '{{ $category->name }}'? Buku dalam kategori ini mungkin ikut terhapus."
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg transition"
                                            title="Hapus Kategori"
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
                                        <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        <p class="text-sm font-medium">Belum ada kategori yang ditambahkan.</p>
                                        <p class="text-xs text-slate-400 mt-0.5">Gunakan formulir di sebelah kiri untuk membuat kategori baru.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
