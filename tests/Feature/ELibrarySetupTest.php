<?php

use App\Models\Book;
use App\Models\Bookmark;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('seeded users, categories, books, and borrowings exist properly', function () {
    $admin = User::where('role', 'admin')->first();
    expect($admin)->not->toBeNull();
    expect($admin->email)->toBe('admin@perpus.sch.id');

    $siswa = User::where('role', 'siswa')->first();
    expect($siswa)->not->toBeNull();
    expect($siswa->email)->toBe('siswa@perpus.sch.id');
    expect($siswa->nis)->toBe('102938');
    expect($siswa->class_name)->toBe('XII RPL 1');

    expect(Category::count())->toBe(4);
    expect(Book::count())->toBe(6);

    $borrowing = Borrowing::where('user_id', $siswa->id)->first();
    expect($borrowing)->not->toBeNull();
    expect($borrowing->status)->toBe('dipinjam');
    expect($borrowing->fine_amount)->toBe(6000);
});

test('models have proper guarded property and relations', function () {
    $category = Category::first();
    expect($category->books)->toBeInstanceOf(Collection::class);

    $book = Book::first();
    expect($book->category)->toBeInstanceOf(Category::class);
    expect($book->borrowings)->toBeInstanceOf(Collection::class);
    expect($book->bookmarks)->toBeInstanceOf(Collection::class);

    $user = User::where('role', 'siswa')->first();
    expect($user->borrowings)->toBeInstanceOf(Collection::class);
    expect($user->bookmarks)->toBeInstanceOf(Collection::class);

    $borrowing = Borrowing::first();
    expect($borrowing->user)->toBeInstanceOf(User::class);
    expect($borrowing->book)->toBeInstanceOf(Book::class);

    $bookmark = Bookmark::create([
        'user_id' => $user->id,
        'book_id' => $book->id,
    ]);
    expect($bookmark->user)->toBeInstanceOf(User::class);
    expect($bookmark->book)->toBeInstanceOf(Book::class);
});

test('all required livewire routes return successful response', function () {
    $book = Book::first();

    $this->get('/')->assertOk()->assertSee('Halaman Katalog Buku');
    $this->get('/login')->assertOk()->assertSee('Halaman Login');
    $this->get('/register')->assertOk()->assertSee('Halaman Register');
    $this->get('/books/'.$book->id)->assertOk()->assertSee('Halaman Detail Buku');
    $this->get('/books/'.$book->id.'/action')->assertOk()->assertSee('Halaman Aksi Buku');
    $this->get('/my-bookmarks')->assertOk()->assertSee('Halaman Buku Favorit');
    $this->get('/my-borrowings')->assertOk()->assertSee('Halaman Pinjaman Saya');
    $this->get('/admin/dashboard')->assertOk()->assertSee('Halaman Dashboard Admin');
    $this->get('/admin/categories')->assertOk()->assertSee('Halaman Kelola Rak');
    $this->get('/admin/books')->assertOk()->assertSee('Halaman Kelola Buku');
    $this->get('/admin/borrowings')->assertOk()->assertSee('Halaman Data Peminjaman');
});

test('navbar renders correct navigation items based on user role', function () {
    // Guest
    $guestResponse = $this->get('/');
    $guestResponse->assertSee('Masuk');
    $guestResponse->assertSee('Daftar');
    $guestResponse->assertDontSee('Pinjaman Saya');
    $guestResponse->assertDontSee('Dashboard Admin');

    // Siswa
    $siswa = User::where('role', 'siswa')->first();
    $siswaResponse = $this->actingAs($siswa)->get('/');
    $siswaResponse->assertSee('Pinjaman Saya');
    $siswaResponse->assertSee('Buku Favorit');
    $siswaResponse->assertDontSee('Dashboard Admin');
    $siswaResponse->assertSee($siswa->name);

    // Admin
    $admin = User::where('role', 'admin')->first();
    $adminResponse = $this->actingAs($admin)->get('/');
    $adminResponse->assertSee('Dashboard Admin');
    $adminResponse->assertSee('Kelola Rak');
    $adminResponse->assertSee('Kelola Buku');
    $adminResponse->assertSee('Data Peminjaman');
    $adminResponse->assertDontSee('Pinjaman Saya');
    $adminResponse->assertSee($admin->name);
});

test('logout route terminates session and redirects', function () {
    $user = User::first();

    $response = $this->actingAs($user)->post('/logout');
    $response->assertRedirect('/');
    $this->assertGuest();
});
