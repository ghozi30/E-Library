<?php

use Livewire\Component;

new class extends Component {
    public $id = null;

    public function mount($id = null): void
    {
        $this->id = $id;
    }
};
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center">
        <h1 class="text-3xl font-extrabold text-slate-900 mb-2">Halaman Detail Buku</h1>
        <p class="text-slate-500">Placeholder komponen Livewire 4 SFC untuk melihat informasi lengkap buku.</p>
    </div>
</div>

