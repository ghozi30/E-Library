<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Daftar Akun Siswa - E-Library Sekolah')] class extends Component
{
    #[Validate('required|string|max:255', message: [
        'required' => 'Nama lengkap wajib diisi.',
    ])]
    public string $name = '';

    #[Validate('required|email|max:255|unique:users,email', message: [
        'required' => 'Email wajib diisi.',
        'email' => 'Format email tidak valid.',
        'unique' => 'Email ini sudah terdaftar.',
    ])]
    public string $email = '';

    #[Validate('required|string|max:50|unique:users,nis', message: [
        'required' => 'Nomor Induk Siswa (NIS) wajib diisi.',
        'unique' => 'NIS ini sudah terdaftar.',
    ])]
    public string $nis = '';

    #[Validate('required|string|max:50', message: [
        'required' => 'Kelas wajib diisi.',
    ])]
    public string $class_name = '';

    #[Validate('required|string|min:6', message: [
        'required' => 'Password wajib diisi.',
        'min' => 'Password minimal terdiri dari 6 karakter.',
    ])]
    public string $password = '';

    public function register(): void
    {
        $validated = $this->validate();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nis' => $validated['nis'],
            'class_name' => $validated['class_name'],
            'password' => Hash::make($validated['password']),
            'role' => 'siswa',
        ]);

        Auth::login($user);
        session()->regenerate();

        $this->redirect('/', navigate: true);
    }
};
?>

<div class="min-h-[calc(100vh-12rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-lg">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-8 sm:p-10">
            {{-- Header --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-100 text-2xl text-indigo-600 mb-4 shadow-sm">
                    🎓
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Registrasi Siswa Baru
                </h1>
                <p class="text-sm text-slate-500 mt-2">
                    Daftar akun keanggotaan untuk meminjam koleksi buku perpustakaan
                </p>
            </div>

            {{-- Form Register --}}
            <form wire:submit="register" class="space-y-4">
                {{-- Nama Lengkap --}}
                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-2">
                        Nama Lengkap
                    </label>
                    <input
                        type="text"
                        id="name"
                        wire:model="name"
                        placeholder="contoh: Muhammad Ghozi"
                        autocomplete="name"
                        class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('name') ? 'border-rose-400 focus:ring-rose-200' : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-100' }} text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 transition-all"
                    >
                    @error('name')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-2">
                        Alamat Email
                    </label>
                    <input
                        type="email"
                        id="email"
                        wire:model="email"
                        placeholder="contoh: ghozi@siswa.sch.id"
                        autocomplete="email"
                        class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('email') ? 'border-rose-400 focus:ring-rose-200' : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-100' }} text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 transition-all"
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Grid NIS & Kelas --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- NIS --}}
                    <div>
                        <label for="nis" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-2">
                            Nomor Induk Siswa (NIS)
                        </label>
                        <input
                            type="text"
                            id="nis"
                            wire:model="nis"
                            placeholder="contoh: 102940"
                            class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('nis') ? 'border-rose-400 focus:ring-rose-200' : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-100' }} text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 transition-all"
                        >
                        @error('nis')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Kelas --}}
                    <div>
                        <label for="class_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-2">
                            Kelas
                        </label>
                        <input
                            type="text"
                            id="class_name"
                            wire:model="class_name"
                            placeholder="contoh: XII RPL 1"
                            class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('class_name') ? 'border-rose-400 focus:ring-rose-200' : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-100' }} text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 transition-all"
                        >
                        @error('class_name')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-2">
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        wire:model="password"
                        placeholder="Minimal 6 karakter"
                        autocomplete="new-password"
                        class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('password') ? 'border-rose-400 focus:ring-rose-200' : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-100' }} text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 transition-all"
                    >
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <div class="pt-2">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-sm font-semibold shadow-sm shadow-indigo-200 hover:shadow transition-all disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                    >
                        <svg wire:loading wire:target="register" class="animate-spin w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Daftar Akun Baru</span>
                    </button>
                </div>
            </form>

            {{-- Login Link --}}
            <div class="mt-6 text-center text-xs text-slate-500">
                Sudah memiliki akun terdaftar?
                <a href="{{ url('/login') }}" wire:navigate class="font-semibold text-indigo-600 hover:text-indigo-700 hover:underline ml-1">
                    Masuk di sini
                </a>
            </div>
        </div>
    </div>
</div>
