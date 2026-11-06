<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'E-Library Sekolah' }}</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    @livewireStyles
</head>
<body class="min-h-full flex flex-col text-slate-800 antialiased selection:bg-indigo-500 selection:text-white" x-data="{ mobileMenuOpen: false }">
    {{-- Navbar Modern & Responsif --}}
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Brand / Logo --}}
                <div class="flex items-center gap-8">
                    <a href="{{ url('/') }}" wire:navigate class="flex items-center gap-2.5 font-bold text-xl text-slate-900 group">
                        <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-indigo-600 text-white shadow-sm shadow-indigo-200 group-hover:scale-105 transition-transform">
                            📚
                        </span>
                        <span class="tracking-tight bg-gradient-to-r from-slate-900 via-indigo-950 to-indigo-800 bg-clip-text text-transparent">
                            E-Library <span class="text-indigo-600">Sekolah</span>
                        </span>
                    </a>

                    {{-- Desktop Nav Menu --}}
                    <nav class="hidden md:flex items-center gap-1">
                        {{-- Semua Pengunjung: Katalog Buku --}}
                        <a href="{{ url('/') }}" wire:navigate
                            class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('/') ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Katalog Buku
                        </a>

                        {{-- Khusus @auth dengan role siswa --}}
                        @auth
                            @if(auth()->user()->role === 'siswa')
                                <a href="{{ url('/my-borrowings') }}" wire:navigate
                                    class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('my-borrowings*') ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Pinjaman Saya
                                </a>
                                <a href="{{ url('/my-bookmarks') }}" wire:navigate
                                    class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('my-bookmarks*') ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Buku Favorit
                                </a>
                            @endif

                            {{-- Khusus @auth dengan role admin --}}
                            @if(auth()->user()->role === 'admin')
                                <a href="{{ url('/admin/dashboard') }}" wire:navigate
                                    class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('admin/dashboard*') ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Dashboard Admin
                                </a>
                                <a href="{{ url('/admin/categories') }}" wire:navigate
                                    class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('admin/categories*') ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Kelola Rak
                                </a>
                                <a href="{{ url('/admin/books') }}" wire:navigate
                                    class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('admin/books*') ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Kelola Buku
                                </a>
                                <a href="{{ url('/admin/borrowings') }}" wire:navigate
                                    class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->is('admin/borrowings*') ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Data Peminjaman
                                </a>
                            @endif
                        @endauth
                    </nav>
                </div>

                {{-- Right Navbar Actions --}}
                <div class="hidden md:flex items-center gap-3">
                    @guest
                        <a href="{{ url('/login') }}" wire:navigate
                            class="px-4 py-2 text-sm font-medium text-slate-700 hover:text-indigo-600 rounded-lg hover:bg-slate-100 transition-colors">
                            Masuk
                        </a>
                        <a href="{{ url('/register') }}" wire:navigate
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm shadow-indigo-200 transition-all">
                            Daftar
                        </a>
                    @endguest

                    @auth
                        <div class="flex items-center gap-3 pl-2 border-l border-slate-200">
                            <div class="flex flex-col items-end">
                                <span class="text-sm font-semibold text-slate-800 leading-tight">
                                    {{ auth()->user()->name }}
                                </span>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold tracking-wide uppercase {{ auth()->user()->role === 'admin' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ auth()->user()->role }}
                                </span>
                            </div>

                            <form method="POST" action="{{ route('logout') }}" class="inline m-0">
                                @csrf
                                <button type="submit"
                                    class="px-3.5 py-1.5 text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors border border-rose-200/60">
                                    Keluar
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>

                {{-- Mobile Hamburger Button --}}
                <div class="flex md:hidden items-center">
                    <button type="button" @click="mobileMenuOpen = !mobileMenuOpen"
                        class="p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none"
                        aria-label="Toggle Menu">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" style="display: none;" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile Menu Dropdown --}}
        <div class="md:hidden border-t border-slate-200 bg-white" x-show="mobileMenuOpen" style="display: none;" x-transition>
            <div class="px-4 pt-2 pb-3 space-y-1">
                {{-- Semua Pengunjung --}}
                <a href="{{ url('/') }}" wire:navigate @click="mobileMenuOpen = false"
                    class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('/') ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                    Katalog Buku
                </a>

                {{-- Siswa --}}
                @auth
                    @if(auth()->user()->role === 'siswa')
                        <a href="{{ url('/my-borrowings') }}" wire:navigate @click="mobileMenuOpen = false"
                            class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('my-borrowings*') ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                            Pinjaman Saya
                        </a>
                        <a href="{{ url('/my-bookmarks') }}" wire:navigate @click="mobileMenuOpen = false"
                            class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('my-bookmarks*') ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                            Buku Favorit
                        </a>
                    @endif

                    {{-- Admin --}}
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ url('/admin/dashboard') }}" wire:navigate @click="mobileMenuOpen = false"
                            class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('admin/dashboard*') ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                            Dashboard Admin
                        </a>
                        <a href="{{ url('/admin/categories') }}" wire:navigate @click="mobileMenuOpen = false"
                            class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('admin/categories*') ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                            Kelola Rak
                        </a>
                        <a href="{{ url('/admin/books') }}" wire:navigate @click="mobileMenuOpen = false"
                            class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('admin/books*') ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                            Kelola Buku
                        </a>
                        <a href="{{ url('/admin/borrowings') }}" wire:navigate @click="mobileMenuOpen = false"
                            class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('admin/borrowings*') ? 'text-indigo-600 bg-indigo-50 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                            Data Peminjaman
                        </a>
                    @endif
                @endauth
            </div>

            <div class="pt-4 pb-3 border-t border-slate-200 px-4">
                @guest
                    <div class="grid grid-cols-2 gap-3">
                        <a href="{{ url('/login') }}" wire:navigate @click="mobileMenuOpen = false"
                            class="w-full text-center px-4 py-2 text-sm font-medium text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50">
                            Masuk
                        </a>
                        <a href="{{ url('/register') }}" wire:navigate @click="mobileMenuOpen = false"
                            class="w-full text-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">
                            Daftar
                        </a>
                    </div>
                @endguest

                @auth
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-base font-medium text-slate-800">{{ auth()->user()->name }}</div>
                            <div class="text-xs font-semibold uppercase text-slate-500">{{ auth()->user()->role }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="px-4 py-2 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg border border-rose-200">
                                Keluar
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </div>
    </header>

    {{-- Main Content Slot --}}
    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="bg-white border-t border-slate-200 py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-3">
            <p>&copy; {{ date('Y') }} E-Library Sekolah. All rights reserved.</p>
            <p>Dibangun dengan Laravel 13, Livewire 4 SFC & Tailwind CSS</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
