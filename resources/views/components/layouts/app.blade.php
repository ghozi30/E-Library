<!DOCTYPE html>
<html lang="id" class="overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'E-Library Sekolah' }}</title>

    <!-- Editorial & Archival Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;0,6..72,700;1,6..72,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        serif: ['"Newsreader"', 'Georgia', 'serif'],
                        mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'monospace'],
                    },
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                        },
                        archival: {
                            paper: '#F7F5F0',
                            surface: '#FAF8F4',
                            border: '#E5E0D8',
                            ink: '#181615',
                            muted: '#5E5850',
                            pine: '#114B43',
                            ochre: '#B45309',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        html, body {
            overflow-x: hidden;
            max-width: 100%;
        }
    </style>
    <script>
        document.addEventListener('livewire:navigating', () => {
            document.body.classList.remove('overflow-hidden');
        });
    </script>
</head>
<body class="bg-[#F7F5F0] text-[#181615] min-h-screen w-full overflow-x-hidden flex flex-col font-sans antialiased selection:bg-[#114B43]/15 selection:text-[#114B43]">
    <!-- Skip Link for Keyboard Accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:px-4 focus:py-2 focus:rounded-lg focus:bg-[#114B43] focus:text-[#FAF8F4] focus:text-sm focus:font-semibold">
        Langsung ke konten utama
    </a>

    <!-- Navbar Editorial & Responsif -->
    <header class="bg-[#FAF8F4]/95 backdrop-blur-md border-b border-[#E5E0D8] sticky top-0 z-30" x-data="{ mobileMenuOpen: false }" @click.outside="mobileMenuOpen = false" @keydown.escape.window="mobileMenuOpen = false">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo & Brand -->
                <div class="flex items-center gap-4 sm:gap-6">
                    <a href="/" wire:navigate.hover class="flex items-center gap-2.5 sm:gap-3 group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43] focus-visible:ring-offset-2 rounded-lg">
                        <div class="w-9 h-9 rounded-lg bg-[#114B43] flex items-center justify-center text-[#FAF8F4] group-hover:bg-[#0C3832] transition-colors duration-200 shrink-0">
                            <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="font-serif text-lg sm:text-xl font-semibold text-[#181615] tracking-tight leading-none block">E-Library</span>
                            <span class="font-mono text-[9px] sm:text-[10px] uppercase tracking-[0.12em] sm:tracking-[0.14em] text-[#5E5850] block mt-0.5 truncate max-w-[130px] sm:max-w-none">Perpustakaan Sekolah</span>
                        </div>
                    </a>

                    <!-- Desktop Navigation Links (Layar Lebar xl: >= 1280px) -->
                    <nav class="hidden xl:flex items-center gap-1 border-l border-[#E5E0D8] pl-5 whitespace-nowrap" aria-label="Navigasi Utama">
                        <!-- Semua Pengunjung -->
                        <a href="/" wire:navigate.hover class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43] {{ request()->is('/') ? 'bg-[#114B43]/10 text-[#114B43] font-semibold' : 'text-[#5E5850] hover:text-[#181615] hover:bg-[#EFECE4]/80' }}">
                            Katalog Buku
                        </a>

                        @auth
                            <!-- Menu Khusus Siswa -->
                            @if(auth()->user()->role === 'siswa')
                                <a href="/my-borrowings" wire:navigate.hover class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43] {{ request()->is('my-borrowings*') ? 'bg-[#114B43]/10 text-[#114B43] font-semibold' : 'text-[#5E5850] hover:text-[#181615] hover:bg-[#EFECE4]/80' }}">
                                    Pinjaman Saya
                                </a>
                                <a href="/my-bookmarks" wire:navigate.hover class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43] {{ request()->is('my-bookmarks*') ? 'bg-[#114B43]/10 text-[#114B43] font-semibold' : 'text-[#5E5850] hover:text-[#181615] hover:bg-[#EFECE4]/80' }}">
                                    Buku Favorit
                                </a>
                            @endif

                            <!-- Menu Khusus Admin -->
                            @if(auth()->user()->role === 'admin')
                                <a href="/admin/dashboard" wire:navigate.hover class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43] {{ request()->is('admin/dashboard*') ? 'bg-[#114B43]/10 text-[#114B43] font-semibold' : 'text-[#5E5850] hover:text-[#181615] hover:bg-[#EFECE4]/80' }}">
                                    Dashboard Admin
                                </a>
                                <a href="/admin/categories" wire:navigate.hover class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43] {{ request()->is('admin/categories*') ? 'bg-[#114B43]/10 text-[#114B43] font-semibold' : 'text-[#5E5850] hover:text-[#181615] hover:bg-[#EFECE4]/80' }}">
                                    Kelola Rak
                                </a>
                                <a href="/admin/books" wire:navigate.hover class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43] {{ request()->is('admin/books*') ? 'bg-[#114B43]/10 text-[#114B43] font-semibold' : 'text-[#5E5850] hover:text-[#181615] hover:bg-[#EFECE4]/80' }}">
                                    Kelola Buku
                                </a>
                                <a href="/admin/borrowings" wire:navigate.hover class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43] {{ request()->is('admin/borrowings*') ? 'bg-[#114B43]/10 text-[#114B43] font-semibold' : 'text-[#5E5850] hover:text-[#181615] hover:bg-[#EFECE4]/80' }}">
                                    Data Peminjaman
                                </a>
                            @endif
                        @endauth
                    </nav>
                </div>

                <!-- Right Navbar: Auth / Guest Controls (Desktop xl: >= 1280px) -->
                <div class="hidden xl:flex items-center gap-3 shrink-0">
                    @guest
                        <a href="/login" wire:navigate.hover class="px-3.5 py-2 text-sm font-medium text-[#5E5850] hover:text-[#181615] transition-colors">
                            Masuk
                        </a>
                        <a href="/register" wire:navigate.hover class="px-4 py-2 text-sm font-semibold text-[#FAF8F4] bg-[#114B43] hover:bg-[#0C3832] rounded-lg transition-colors">
                            Daftar
                        </a>
                    @else
                        <div class="flex items-center gap-3 pl-2">
                            <div class="text-right">
                                <div class="text-sm font-semibold text-[#181615] leading-tight">{{ auth()->user()->name }}</div>
                                <div class="mt-0.5">
                                    @if(auth()->user()->role === 'admin')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded font-mono text-[10px] font-medium uppercase tracking-wider bg-[#EAF2F0] text-[#114B43] border border-[#114B43]/15">Pustakawan</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded font-mono text-[10px] font-medium bg-[#F0FDF4] text-[#166534] border border-[#BBF7D0]">Siswa ({{ auth()->user()->class_name ?? 'Umum' }})</span>
                                    @endif
                                </div>
                            </div>

                            <form action="/logout" method="POST" class="inline">
                                @csrf
                                <button type="submit" aria-label="Keluar / Logout" title="Keluar / Logout" class="p-2 text-[#5E5850] hover:text-[#991B1B] hover:bg-[#FEF2F2] rounded-lg border border-transparent hover:border-[#FECACA] transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43]">
                                    <svg class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @endguest
                </div>

                <!-- Mobile & Tablet Hamburger Button (< 1280px) -->
                <div class="flex xl:hidden items-center gap-2 shrink-0">
                    @auth
                        <span class="text-xs font-semibold text-stone-700 truncate max-w-[95px] sm:max-w-[180px] block">
                            {{ auth()->user()->name }}
                        </span>
                    @endauth
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" aria-label="Buka menu navigasi" :aria-expanded="mobileMenuOpen.toString()" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg text-[#5E5850] hover:text-[#181615] hover:bg-[#EFECE4] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#114B43] cursor-pointer">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6h16M4 12h16M4 18h16"></path>
                            <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile & Tablet Navigation Menu Drawer (< 1280px, Overlay tanpa mendorong konten) -->
        <div
            x-show="mobileMenuOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="xl:hidden absolute inset-x-0 top-full z-40 border-y border-[#E5E0D8] bg-[#FAF8F4] px-4 pt-2 pb-5 space-y-1 shadow-xl max-h-[calc(100dvh-4rem)] overflow-y-auto"
        >
            <a href="/" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-3 py-2.5 rounded-lg text-base font-medium {{ request()->is('/') ? 'bg-[#114B43]/10 text-[#114B43] font-semibold' : 'text-[#5E5850] hover:bg-[#EFECE4]' }}">
                Katalog Buku
            </a>

            @auth
                @if(auth()->user()->role === 'siswa')
                    <a href="/my-borrowings" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-3 py-2.5 rounded-lg text-base font-medium {{ request()->is('my-borrowings*') ? 'bg-[#114B43]/10 text-[#114B43] font-semibold' : 'text-[#5E5850] hover:bg-[#EFECE4]' }}">
                        Pinjaman Saya
                    </a>
                    <a href="/my-bookmarks" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-3 py-2.5 rounded-lg text-base font-medium {{ request()->is('my-bookmarks*') ? 'bg-[#114B43]/10 text-[#114B43] font-semibold' : 'text-[#5E5850] hover:bg-[#EFECE4]' }}">
                        Buku Favorit
                    </a>
                @endif

                @if(auth()->user()->role === 'admin')
                    <a href="/admin/dashboard" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-3 py-2.5 rounded-lg text-base font-medium {{ request()->is('admin/dashboard*') ? 'bg-[#114B43]/10 text-[#114B43] font-semibold' : 'text-[#5E5850] hover:bg-[#EFECE4]' }}">
                        Dashboard Admin
                    </a>
                    <a href="/admin/categories" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-3 py-2.5 rounded-lg text-base font-medium {{ request()->is('admin/categories*') ? 'bg-[#114B43]/10 text-[#114B43] font-semibold' : 'text-[#5E5850] hover:bg-[#EFECE4]' }}">
                        Kelola Rak
                    </a>
                    <a href="/admin/books" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-3 py-2.5 rounded-lg text-base font-medium {{ request()->is('admin/books*') ? 'bg-[#114B43]/10 text-[#114B43] font-semibold' : 'text-[#5E5850] hover:bg-[#EFECE4]' }}">
                        Kelola Buku
                    </a>
                    <a href="/admin/borrowings" wire:navigate.hover @click="mobileMenuOpen = false" class="block px-3 py-2.5 rounded-lg text-base font-medium {{ request()->is('admin/borrowings*') ? 'bg-[#114B43]/10 text-[#114B43] font-semibold' : 'text-[#5E5850] hover:bg-[#EFECE4]' }}">
                        Data Peminjaman
                    </a>
                @endif

                <div class="pt-4 border-t border-[#E5E0D8] mt-2">
                    <div class="px-3 py-1 mb-2">
                        <div class="font-semibold text-[#181615]">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-[#5E5850] capitalize">{{ auth()->user()->role }} {{ auth()->user()->class_name ? '• ' . auth()->user()->class_name : '' }}</div>
                    </div>
                    <form action="/logout" method="POST">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2.5 rounded-lg text-base font-medium text-[#991B1B] hover:bg-[#FEF2F2] transition-colors cursor-pointer">
                            Keluar / Logout
                        </button>
                    </form>
                </div>
            @else
                <div class="pt-4 border-t border-[#E5E0D8] mt-2 space-y-2">
                    <a href="/login" wire:navigate @click="mobileMenuOpen = false" class="block w-full text-center px-4 py-2.5 rounded-lg text-base font-medium text-[#181615] bg-[#EFECE4] hover:bg-[#E5E0D8]">
                        Masuk
                    </a>
                    <a href="/register" wire:navigate @click="mobileMenuOpen = false" class="block w-full text-center px-4 py-2.5 rounded-lg text-base font-semibold text-[#FAF8F4] bg-[#114B43] hover:bg-[#0C3832]">
                        Daftar
                    </a>
                </div>
            @endauth
        </div>
    </header>

    <!-- Main Content Slot -->
    <main id="main-content" class="flex-grow w-full min-w-0">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-[#FAF8F4] border-t border-[#E5E0D8] py-6 mt-12 text-center text-xs text-[#5E5850]">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <p class="font-serif italic text-sm text-[#181615]">E-Library Perpustakaan Sekolah</p>
            <p class="font-mono text-[11px] tracking-wide">&copy; {{ date('Y') }} Sistem Informasi Perpustakaan • Laravel &amp; Livewire</p>
        </div>
    </footer>
</body>
</html>
