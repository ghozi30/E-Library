<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Masuk - E-Library Sekolah')] class extends Component
{
    #[Validate('required|email', message: [
        'required' => 'Email wajib diisi.',
        'email' => 'Format email tidak valid.',
    ])]
    public string $email = '';

    #[Validate('required', message: [
        'required' => 'Password wajib diisi.',
    ])]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();

            if (Auth::user()->role === 'admin') {
                $this->redirect('/admin/dashboard', navigate: true);

                return;
            }

            $this->redirect('/', navigate: true);

            return;
        }

        $this->addError('email', 'Email atau password yang Anda masukkan tidak sesuai.');
    }
};
?>

<div class="min-h-[calc(100vh-12rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-8 sm:p-10">
            {{-- Header --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-100 text-2xl text-indigo-600 mb-4 shadow-sm">
                    📚
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    Masuk ke E-Library
                </h1>
                <p class="text-sm text-slate-500 mt-2">
                    Akses koleksi buku dan layanan peminjaman perpustakaan sekolah
                </p>
            </div>

            {{-- Form Login --}}
            <form wire:submit="login" class="space-y-5">
                {{-- Email --}}
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-2">
                        Alamat Email
                    </label>
                    <input
                        type="email"
                        id="email"
                        wire:model="email"
                        placeholder="contoh: siswa@perpus.sch.id"
                        autocomplete="email"
                        class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('email') ? 'border-rose-400 focus:ring-rose-200' : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-100' }} text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 transition-all"
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Password
                        </label>
                    </div>
                    <input
                        type="password"
                        id="password"
                        wire:model="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('password') ? 'border-rose-400 focus:ring-rose-200' : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-100' }} text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 transition-all"
                    >
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Remember Me --}}
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            wire:model="remember"
                            class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500"
                        >
                        <span class="text-xs text-slate-600 font-medium">Ingat saya di perangkat ini</span>
                    </label>
                </div>

                {{-- Submit Button --}}
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-sm font-semibold shadow-sm shadow-indigo-200 hover:shadow transition-all disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                >
                    <svg wire:loading wire:target="login" class="animate-spin w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Masuk ke Akun</span>
                </button>
            </form>

            {{-- Demo Hint Box --}}
            <div class="mt-6 p-3.5 rounded-xl bg-slate-50 border border-slate-200/70 text-xs text-slate-600 space-y-1">
                <div class="font-semibold text-slate-700">Akun Pengujian Cepat:</div>
                <div class="flex justify-between items-center text-[11px]">
                    <span>Admin: <strong class="text-slate-800">admin@perpus.sch.id</strong></span>
                    <span class="text-slate-500">pass: password</span>
                </div>
                <div class="flex justify-between items-center text-[11px]">
                    <span>Siswa: <strong class="text-slate-800">siswa@perpus.sch.id</strong></span>
                    <span class="text-slate-500">pass: password</span>
                </div>
            </div>

            {{-- Register Link --}}
            <div class="mt-6 text-center text-xs text-slate-500">
                Belum memiliki akun siswa?
                <a href="{{ url('/register') }}" wire:navigate class="font-semibold text-indigo-600 hover:text-indigo-700 hover:underline ml-1">
                    Daftar di sini
                </a>
            </div>
        </div>
    </div>
</div>
