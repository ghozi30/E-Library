<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Masuk - E-Library Sekolah')] class extends Component {
    #[Validate('required', message: 'Alamat email wajib diisi.')]
    #[Validate('email', message: 'Format email tidak valid.')]
    public string $email = '';

    #[Validate('required', message: 'Kata sandi wajib diisi.')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();

            if (Auth::user()->role === 'admin') {
                $this->redirectIntended(default: '/admin/dashboard', navigate: true);
                return;
            }

            $this->redirectIntended(default: '/', navigate: true);
            return;
        }

        $this->addError('email', 'Email atau kata sandi yang Anda masukkan salah.');
    }
};
?>

<div class="min-h-[calc(100vh-14rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 sm:p-10 rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100">
        <!-- Header -->
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-200 mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Selamat Datang</h2>
            <p class="mt-2 text-sm text-slate-500">Masuk ke akun Anda untuk mengakses layanan perpustakaan sekolah</p>
        </div>

        <!-- Form -->
        <form wire:submit="login" class="mt-8 space-y-5">
            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Alamat Email</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"></path>
                        </svg>
                    </div>
                    <input
                        wire:model="email"
                        id="email"
                        type="email"
                        autocomplete="email"
                        placeholder="contoh: siswa@perpus.sch.id"
                        class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border @error('email') border-rose-300 ring-rose-200 @else border-slate-200 ring-indigo-200 @enderror rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-600 focus:ring-4 transition text-sm"
                    >
                </div>
                @error('email')
                    <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Kata Sandi</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <input
                        wire:model="password"
                        id="password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border @error('password') border-rose-300 ring-rose-200 @else border-slate-200 ring-indigo-200 @enderror rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-600 focus:ring-4 transition text-sm"
                    >
                </div>
                @error('password')
                    <p class="mt-1.5 text-xs text-rose-600 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 inline" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <!-- Remember me -->
            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center text-slate-600 cursor-pointer select-none">
                    <input
                        wire:model="remember"
                        type="checkbox"
                        class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500"
                    >
                    <span class="ml-2 text-sm">Ingat saya</span>
                </label>
            </div>

            <!-- Submit Button -->
            <div>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full flex items-center justify-center py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 shadow-md shadow-indigo-200 focus:outline-none focus:ring-4 focus:ring-indigo-200 transition disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                >
                    <span wire:loading.remove wire:target="login">Masuk ke Akun</span>
                    <span wire:loading wire:target="login" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memproses...
                    </span>
                </button>
            </div>
        </form>

        <!-- Footer link -->
        <div class="text-center pt-2 border-t border-slate-100">
            <p class="text-sm text-slate-600">
                Belum memiliki akun siswa?
                <a href="/register" wire:navigate class="font-semibold text-indigo-600 hover:text-indigo-700 hover:underline">
                    Daftar di sini
                </a>
            </p>
        </div>
    </div>
</div>
