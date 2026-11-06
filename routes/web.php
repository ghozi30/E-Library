<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AREA ORANG 1: Autentikasi & Akun Pengguna
|--------------------------------------------------------------------------
| Menangani pendaftaran anggota baru, login sistem, dan terminasi sesi.
*/
Route::livewire('/login', 'auth.login')->name('login');
Route::livewire('/register', 'auth.register')->name('register');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->name('logout');

/*
|--------------------------------------------------------------------------
| AREA ORANG 2: Eksplorasi Katalog & Detail Buku
|--------------------------------------------------------------------------
| Menangani pencarian katalog koleksi buku dan tampilan rincian informasi buku.
*/
Route::livewire('/', 'book-catalog')->name('books.index');
Route::livewire('/books/{book}', 'book-detail')->name('books.show');

/*
|--------------------------------------------------------------------------
| AREA ORANG 3: Interaksi Siswa (Aksi Buku, Favorit & Pinjaman Saya)
|--------------------------------------------------------------------------
| Menangani aksi peminjaman buku, bookmark koleksi favorit, dan pemantauan denda siswa.
*/
Route::livewire('/books/{book}/action', 'book-action')->name('books.action');
Route::livewire('/my-bookmarks', 'my-bookmarks')->name('bookmarks.index');
Route::livewire('/my-borrowings', 'my-borrowings')->name('borrowings.my');

/*
|--------------------------------------------------------------------------
| AREA ORANG 4: Panel Administrasi Perpustakaan (Admin)
|--------------------------------------------------------------------------
| Menangani ringkasan dashboard, manajemen master kategori/rak, koleksi buku, dan data sirkulasi.
*/
Route::livewire('/admin/dashboard', 'admin.dashboard')->name('admin.dashboard');
Route::livewire('/admin/categories', 'admin.categories')->name('admin.categories');
Route::livewire('/admin/books', 'admin.books')->name('admin.books');
Route::livewire('/admin/borrowings', 'admin.borrowings')->name('admin.borrowings');
