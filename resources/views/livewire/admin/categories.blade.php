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
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
        $validated = $this->validate();
        $validated['rack_code'] = strtoupper(trim($validated['rack_code']));

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
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
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
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
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

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 space-y-6">
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
                <span class="text-xs font-semibold text-[#144533]">Kelola Rak</span>
            </div>
            <div class="font-mono text-[11px] uppercase tracking-[0.14em] text-stone-500 mt-2">
                MEJA PUSTAKAWAN / ORGANISASI KOLEKSI
            </div>
            <h1 class="font-serif text-3xl sm:text-4xl font-semibold text-stone-900 tracking-tight mt-1">
                Kelola Kategori &amp; Kode Rak
            </h1>
            <p class="text-sm text-stone-600 mt-1">
                Atur pengelompokan buku dan penataan lokasi rak buku perpustakaan.
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <span class="px-3.5 py-1.5 text-xs font-medium rounded-full bg-stone-100 border border-stone-200 text-stone-700 font-mono">
                Total Kategori: {{ $this->categories->count() }}
            </span>
        </div>
    </div>

    <!-- Alert Notifikasi (Toast Overlay 5 Detik) -->
    @if(session()->has('message'))
        <div
            wire:key="category-toast-{{ md5(session('message')) }}-{{ substr((string) microtime(true), -6) }}"
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

    <!-- Layout 2 Kolom (1 Layar) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Kolom Kiri: Form Tambah / Edit -->
        <div class="lg:col-span-4 bg-white p-6 rounded-2xl border border-stone-200/80 shadow-sm lg:sticky lg:top-24">
            <div class="flex items-center justify-between pb-4 border-b border-stone-200/70 mb-5">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl {{ $categoryId ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-[#144533]/10 text-[#144533]' }} flex items-center justify-center font-bold">
                        @if($categoryId)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        @endif
                    </div>
                    <h2 class="font-serif text-lg font-semibold text-stone-900">
                        {{ $categoryId ? 'Edit Kategori & Rak' : 'Tambah Kategori & Rak' }}
                    </h2>
                </div>

                @if($categoryId)
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200 font-medium">Mode Edit</span>
                @endif
            </div>

            <form wire:submit="save" class="space-y-4">
                <!-- Nama Kategori -->
                <div>
                    <label for="name" class="block font-mono text-[11px] font-semibold uppercase tracking-wider text-stone-600 mb-1.5">
                        Nama Kategori <span class="text-red-600">*</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        wire:model="name"
                        autocomplete="off"
                        placeholder="Contoh: Teknologi, Sains, Novel"
                        class="w-full px-3.5 py-2.5 bg-[#FAF8F5] rounded-xl border {{ $errors->has('name') ? 'border-red-400 focus:ring-red-200 focus:border-red-600' : 'border-stone-300 focus:bg-white focus:border-[#144533] focus:ring-[#144533]/20' }} text-sm text-stone-900 placeholder-stone-400 focus:outline-none focus:ring-2 transition"
                    >
                    @error('name')
                        <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kode Rak -->
                <div>
                    <label for="rack_code" class="block font-mono text-[11px] font-semibold uppercase tracking-wider text-stone-600 mb-1.5">
                        Kode Rak <span class="text-red-600">*</span>
                    </label>
                    <input
                        type="text"
                        id="rack_code"
                        wire:model="rack_code"
                        autocomplete="off"
                        placeholder="Contoh: RAK-TEK-01, RAK-NOV-02"
                        class="w-full px-3.5 py-2.5 bg-[#FAF8F5] rounded-xl border {{ $errors->has('rack_code') ? 'border-red-400 focus:ring-red-200 focus:border-red-600' : 'border-stone-300 focus:bg-white focus:border-[#144533] focus:ring-[#144533]/20' }} text-sm font-mono uppercase text-stone-900 placeholder-stone-400 focus:outline-none focus:ring-2 transition"
                    >
                    @error('rack_code')
                        <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tombol Aksi Form -->
                <div class="pt-2 flex items-center gap-2">
                    <button
                        type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-3 text-xs font-medium text-white bg-[#144533] hover:bg-[#0F3325] rounded-xl transition shadow-sm disabled:opacity-50 cursor-pointer"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="save">
                            {{ $categoryId ? 'Perbarui Kategori' : 'Simpan Kategori' }}
                        </span>
                        <span wire:loading wire:target="save" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" aria-hidden="true">
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
                            class="px-3.5 py-3 text-xs font-semibold text-stone-700 bg-stone-100 hover:bg-stone-200 border border-stone-200 rounded-xl transition cursor-pointer"
                        >
                            Batal
                        </button>
                    @endif
                </div>
            </form>
        </div>

        <!-- Kolom Kanan: Tabel Daftar Kategori & Jumlah Buku -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-stone-200/80 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-stone-200/70 flex items-center justify-between">
                <div>
                    <h2 class="font-serif text-lg font-semibold text-stone-900">Daftar Kategori &amp; Rak</h2>
                    <p class="text-xs text-stone-500 mt-0.5">Semua kategori buku terdaftar beserta nomor rak dan jumlah koleksi buku.</p>
                </div>
            </div>

            <!-- Mobile Card View (< 768px) -->
            <div class="md:hidden divide-y divide-stone-200/70">
                @forelse($this->categories as $category)
                    <div wire:key="cat-card-{{ $category->id }}" class="p-4 space-y-3 {{ $categoryId === $category->id ? 'bg-amber-50/40' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-semibold text-stone-900 [overflow-wrap:anywhere]">{{ $category->name }}</div>
                                <div class="text-[11px] text-stone-500 mt-0.5">Dibuat {{ $category->created_at?->diffForHumans() }}</div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold shrink-0 {{ $category->books_count > 0 ? 'bg-stone-100 text-stone-800 border border-stone-200' : 'bg-stone-50 text-stone-400 border border-stone-200/60' }}">
                                {{ $category->books_count }} Judul
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-2 pt-1">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-stone-100 text-stone-800 border border-stone-300 font-mono text-xs">
                                {{ $category->rack_code }}
                            </span>

                            <div class="inline-flex items-center gap-1.5 shrink-0">
                                <button
                                    type="button"
                                    wire:click="edit({{ $category->id }})"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold border border-stone-200 bg-white hover:bg-stone-50 text-stone-700 rounded-lg transition cursor-pointer"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    wire:click="delete({{ $category->id }})"
                                    wire:confirm="Yakin ingin menghapus kategori '{{ $category->name }}'? Buku dalam kategori ini mungkin ikut terhapus."
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold border border-red-200 bg-red-50/60 hover:bg-red-100 text-red-700 rounded-lg transition cursor-pointer"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-12 px-4 text-center text-stone-500">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-12 h-12 rounded-full bg-stone-100 text-stone-400 border border-stone-200 flex items-center justify-center mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <p class="font-serif text-base font-semibold text-stone-800">Belum ada kategori yang ditambahkan.</p>
                            <p class="text-xs text-stone-500 mt-0.5">Gunakan formulir di atas untuk membuat kategori baru.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Tablet & Desktop Table View (>= 768px) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full table-fixed text-left border-collapse">
                    <thead>
                        <tr class="bg-[#FAF8F5] border-b border-stone-200 text-xs font-mono uppercase tracking-wider text-stone-500">
                            <th class="py-3.5 px-6 w-[36%]">Nama Kategori</th>
                            <th class="py-3.5 px-6 w-[22%]">Kode Rak</th>
                            <th class="py-3.5 px-6 w-[18%] text-center">Jumlah Buku</th>
                            <th class="py-3.5 px-6 w-[24%] text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-200/70 text-sm">
                        @forelse($this->categories as $category)
                            <tr wire:key="cat-row-{{ $category->id }}" class="hover:bg-[#FAF8F5]/80 transition-colors {{ $categoryId === $category->id ? 'bg-amber-50/40' : '' }}">
                                <td class="py-4 px-6">
                                    <div class="font-semibold text-stone-900 [overflow-wrap:anywhere]">{{ $category->name }}</div>
                                    <div class="text-[11px] text-stone-500 mt-0.5">Dibuat {{ $category->created_at?->diffForHumans() }}</div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-stone-100 text-stone-800 border border-stone-300 font-mono text-xs">
                                        {{ $category->rack_code }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $category->books_count > 0 ? 'bg-stone-100 text-stone-800 border border-stone-200' : 'bg-stone-50 text-stone-400 border border-stone-200/60' }}">
                                        {{ $category->books_count }} Judul
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            wire:click="edit({{ $category->id }})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium border border-stone-200 bg-white hover:bg-stone-50 text-stone-700 rounded-lg transition cursor-pointer"
                                            title="Edit Kategori"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="delete({{ $category->id }})"
                                            wire:confirm="Yakin ingin menghapus kategori '{{ $category->name }}'? Buku dalam kategori ini mungkin ikut terhapus."
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium border border-red-200 bg-red-50/60 hover:bg-red-100 text-red-700 rounded-lg transition cursor-pointer"
                                            title="Hapus Kategori"
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
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                        </div>
                                        <p class="font-serif text-base font-semibold text-stone-800">Belum ada kategori yang ditambahkan.</p>
                                        <p class="text-xs text-stone-500 mt-0.5">Gunakan formulir di sebelah kiri untuk membuat kategori baru.</p>
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
