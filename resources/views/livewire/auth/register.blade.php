<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Daftar Akun Siswa - E-Library Sekolah')] class extends Component {
    #[Validate('required', message: 'Nama lengkap wajib diisi.')]
    #[Validate('string')]
    #[Validate('min:3', message: 'Nama minimal 3 karakter.')]
    #[Validate('max:255', message: 'Nama maksimal 255 karakter.')]
    public string $name = '';

    #[Validate('required', message: 'Alamat email wajib diisi.')]
    #[Validate('email', message: 'Format email tidak valid.')]
    #[Validate('unique:users,email', message: 'Email sudah terdaftar pada sistem.')]
    public string $email = '';

    #[Validate('required', message: 'NIS wajib diisi.')]
    #[Validate('string')]
    #[Validate('max:30', message: 'NIS maksimal 30 karakter.')]
    #[Validate('unique:users,nis', message: 'NIS ini sudah terdaftar.')]
    public string $nis = '';

    #[Validate('required', message: 'Kelas wajib diisi.')]
    #[Validate('string')]
    #[Validate('max:50', message: 'Nama kelas maksimal 50 karakter.')]
    public string $class_name = '';

    #[Validate('required', message: 'Kata sandi wajib diisi.')]
    #[Validate('min:6', message: 'Kata sandi minimal 6 karakter.')]
    #[Validate('confirmed', message: 'Konfirmasi kata sandi tidak cocok.')]
    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'nis' => $this->nis,
            'class_name' => $this->class_name,
            'password' => Hash::make($this->password),
            'role' => 'siswa',
        ]);

        Auth::login($user);
        session()->regenerate();

        $this->redirect('/', navigate: true);
    }
};
?>

<div class="min-h-[calc(100vh-14rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-lg w-full space-y-8 bg-white p-8 sm:p-10 rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100">
        <!-- Header -->
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-200 mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Pendaftaran Siswa Baru</h2>
            <p class="mt-2 text-sm text-slate-500">Buat akun untuk meminjam buku dan menyimpan koleksi bacaan favorit</p>
        </div>

        <!-- Form -->
        <form wire:submit="register" class="mt-8 space-y-4">
            <!-- Nama Lengkap -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lengkap</label>
                <input
                    wire:model="name"
                    id="name"
                    type="text"
                    placeholder="contoh: Ahmad Budi Santoso"
                    class="block w-full px-3.5 py-2.5 bg-slate-50 border @error('name') border-rose-300 ring-rose-200 @else border-slate-200 ring-indigo-200 @enderror rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-600 focus:ring-4 transition text-sm"
                >
                @error('name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- NIS & Kelas (2 Columns) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="nis" class="block text-sm font-medium text-slate-700 mb-1.5">Nomor Induk Siswa (NIS)</label>
                    <input
                        wire:model="nis"
                        id="nis"
                        type="text"
                        placeholder="contoh: 102938"
                        class="block w-full px-3.5 py-2.5 bg-slate-50 border @error('nis') border-rose-300 ring-rose-200 @else border-slate-200 ring-indigo-200 @enderror rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-600 focus:ring-4 transition text-sm"
                    >
                    @error('nis')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="class_name" class="block text-sm font-medium text-slate-700 mb-1.5">Kelas & Jurusan</label>
                    <input
                        wire:model="class_name"
                        id="class_name"
                        type="text"
                        placeholder="contoh: XII RPL 1"
                        class="block w-full px-3.5 py-2.5 bg-slate-50 border @error('class_name') border-rose-300 ring-rose-200 @else border-slate-200 ring-indigo-200 @enderror rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-600 focus:ring-4 transition text-sm"
                    >
                    @error('class_name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Alamat Email</label>
                <input
                    wire:model="email"
                    id="email"
                    type="email"
                    placeholder="contoh: budi@perpus.sch.id"
                    class="block w-full px-3.5 py-2.5 bg-slate-50 border @error('email') border-rose-300 ring-rose-200 @else border-slate-200 ring-indigo-200 @enderror rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-600 focus:ring-4 transition text-sm"
                >
                @error('email')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password & Password Confirmation (2 Columns) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Kata Sandi</label>
                    <input
                        wire:model="password"
                        id="password"
                        type="password"
                        placeholder="Min. 6 karakter"
                        class="block w-full px-3.5 py-2.5 bg-slate-50 border @error('password') border-rose-300 ring-rose-200 @else border-slate-200 ring-indigo-200 @enderror rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-600 focus:ring-4 transition text-sm"
                    >
                    @error('password')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Konfirmasi Kata Sandi</label>
                    <input
                        wire:model="password_confirmation"
                        id="password_confirmation"
                        type="password"
                        placeholder="Ulangi kata sandi"
                        class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 ring-indigo-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-600 focus:ring-4 transition text-sm"
                    >
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full flex items-center justify-center py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 shadow-md shadow-indigo-200 focus:outline-none focus:ring-4 focus:ring-indigo-200 transition disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                >
                    <span wire:loading.remove wire:target="register">Daftar Sebagai Siswa</span>
                    <span wire:loading wire:target="register" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Mendaftarkan akun...
                    </span>
                </button>
            </div>
        </form>

        <!-- Footer link -->
        <div class="text-center pt-2 border-t border-slate-100">
            <p class="text-sm text-slate-600">
                Sudah memiliki akun?
                <a href="/login" wire:navigate class="font-semibold text-indigo-600 hover:text-indigo-700 hover:underline">
                    Masuk di sini
                </a>
            </p>
        </div>
    </div>
</div>
