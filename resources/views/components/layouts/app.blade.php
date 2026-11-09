<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'E-Library Sekolah' }}</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col font-sans antialiased">
    <!-- Navbar Modern & Responsif -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo & Brand -->
                <div class="flex items-center space-x-3">
                    <a href="/" wire:navigate class="flex items-center space-x-2.5 group">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-200 group-hover:bg-indigo-700 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="text-lg font-bold text-slate-900 tracking-tight leading-none block">E-Library</span>
                            <span class="text-xs font-medium text-indigo-600">Perpustakaan Sekolah</span>
                        </div>
                    </a>

                    <!-- Desktop Navigation Links -->
                    <nav class="hidden md:flex items-center space-x-1 ml-6">
                        <!-- Semua Pengunjung -->
                        <a href="/" wire:navigate class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->is('/') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            Katalog Buku
                        </a>

                        @auth
                            <!-- Menu Khusus Siswa -->
                            @if(auth()->user()->role === 'siswa')
                                <a href="/my-borrowings" wire:navigate class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->is('my-borrowings*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Pinjaman Saya
                                </a>
                                <a href="/my-bookmarks" wire:navigate class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->is('my-bookmarks*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Buku Favorit
                                </a>
                            @endif

                            <!-- Menu Khusus Admin -->
                            @if(auth()->user()->role === 'admin')
                                <a href="/admin/dashboard" wire:navigate class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->is('admin/dashboard*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Dashboard Admin
                                </a>
                                <a href="/admin/categories" wire:navigate class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->is('admin/categories*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Kelola Rak
                                </a>
                                <a href="/admin/books" wire:navigate class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->is('admin/books*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Kelola Buku
                                </a>
                                <a href="/admin/borrowings" wire:navigate class="px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->is('admin/borrowings*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                                    Data Peminjaman
                                </a>
                            @endif
                        @endauth
                    </nav>
                </div>

                <!-- Right Navbar: Auth / Guest Controls -->
                <div class="hidden md:flex items-center space-x-3">
                    @guest
                        <a href="/login" wire:navigate class="px-4 py-2 text-sm font-medium text-slate-700 hover:text-indigo-600 transition">
                            Masuk
                        </a>
                        <a href="/register" wire:navigate class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition">
                            Daftar
                        </a>
                    @else
                        <div class="flex items-center space-x-3 pl-2">
                            <div class="text-right">
                                <div class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</div>
                                <div class="text-xs">
                                    @if(auth()->user()->role === 'admin')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-purple-100 text-purple-700">Admin</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-emerald-100 text-emerald-700">Siswa ({{ auth()->user()->class_name ?? 'Umum' }})</span>
                                    @endif
                                </div>
                            </div>

                            <form action="/logout" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Keluar / Logout">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @endguest
                </div>

                <!-- Mobile Hamburger Button -->
                <div class="flex md:hidden items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <div x-show="mobileMenuOpen" x-cloak class="md:hidden border-t border-slate-200 bg-white px-4 pt-2 pb-4 space-y-1">
            <a href="/" wire:navigate @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('/') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                Katalog Buku
            </a>

            @auth
                @if(auth()->user()->role === 'siswa')
                    <a href="/my-borrowings" wire:navigate @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('my-borrowings*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        Pinjaman Saya
                    </a>
                    <a href="/my-bookmarks" wire:navigate @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('my-bookmarks*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        Buku Favorit
                    </a>
                @endif

                @if(auth()->user()->role === 'admin')
                    <a href="/admin/dashboard" wire:navigate @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('admin/dashboard*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        Dashboard Admin
                    </a>
                    <a href="/admin/categories" wire:navigate @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('admin/categories*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        Kelola Rak
                    </a>
                    <a href="/admin/books" wire:navigate @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('admin/books*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        Kelola Buku
                    </a>
                    <a href="/admin/borrowings" wire:navigate @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-lg text-base font-medium {{ request()->is('admin/borrowings*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        Data Peminjaman
                    </a>
                @endif

                <div class="pt-4 border-t border-slate-200 mt-2">
                    <div class="px-3 py-1 mb-2">
                        <div class="font-semibold text-slate-800">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-slate-500 capitalize">{{ auth()->user()->role }} {{ auth()->user()->class_name ? '• ' . auth()->user()->class_name : '' }}</div>
                    </div>
                    <form action="/logout" method="POST">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-base font-medium text-rose-600 hover:bg-rose-50 transition">
                            Keluar / Logout
                        </button>
                    </form>
                </div>
            @else
                <div class="pt-4 border-t border-slate-200 mt-2 space-y-2">
                    <a href="/login" wire:navigate @click="mobileMenuOpen = false" class="block w-full text-center px-4 py-2 rounded-lg text-base font-medium text-slate-700 bg-slate-100 hover:bg-slate-200">
                        Masuk
                    </a>
                    <a href="/register" wire:navigate @click="mobileMenuOpen = false" class="block w-full text-center px-4 py-2 rounded-lg text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        Daftar
                    </a>
                </div>
            @endauth
        </div>
    </header>

    <!-- Main Content Slot -->
    <main class="flex-grow">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 mt-12 text-center text-sm text-slate-500">
        <div class="max-w-7xl mx-auto px-4">
            <p>&copy; {{ date('Y') }} E-Library Perpustakaan Sekolah. Dibangun dengan Laravel 13 & Livewire 4.</p>
        </div>
    </footer>
</body>
</html>
