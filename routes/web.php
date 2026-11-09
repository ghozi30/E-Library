<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AREA ORANG 1: Autentikasi & Akun Pengguna
|--------------------------------------------------------------------------
| Bertanggung jawab atas alur autentikasi: Halaman login dan register siswa.
*/
Route::livewire('/login', 'auth.login')->name('login');
Route::livewire('/register', 'auth.register')->name('register');

/*
|--------------------------------------------------------------------------
| AREA ORANG 2: Pengunjung & Katalog Buku
|--------------------------------------------------------------------------
| Bertanggung jawab atas eksplorasi katalog buku dan tampilan detail buku.
*/
Route::livewire('/', 'book-catalog')->name('home');
Route::livewire('/books/{id}', 'book-detail')->name('books.detail');

/*
|--------------------------------------------------------------------------
| AREA ORANG 3: Siswa - Peminjaman, Favorit, & Aksi Buku
|--------------------------------------------------------------------------
| Bertanggung jawab atas interaksi siswa: Aksi pinjam/bookmark, daftar pinjaman, dan favorit.
*/
Route::livewire('/books/{id}/action', 'book-action')->name('books.action');
Route::livewire('/my-bookmarks', 'my-bookmarks')->name('my-bookmarks');
Route::livewire('/my-borrowings', 'my-borrowings')->name('my-borrowings');

/*
|--------------------------------------------------------------------------
| AREA ORANG 4: Panel Administrator Perpustakaan
|--------------------------------------------------------------------------
| Bertanggung jawab atas pengelolaan perpustakaan: Dashboard, Rak, Buku, & Transaksi Peminjaman.
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/dashboard', 'admin.dashboard')->name('dashboard');
    Route::livewire('/categories', 'admin.categories')->name('categories');
    Route::livewire('/books', 'admin.books')->name('books');
    Route::livewire('/borrowings', 'admin.borrowings')->name('borrowings');
});

/*
|--------------------------------------------------------------------------
| Route Logout
|--------------------------------------------------------------------------
*/
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
})->name('logout');
