<?php

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('admin can login and is redirected to admin dashboard', function () {
    Livewire::test('auth.login')
        ->set('email', 'admin@perpus.sch.id')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect('/admin/dashboard');

    $this->assertAuthenticated();
    expect(auth()->user()->role)->toBe('admin');
});

test('siswa can login and is redirected to home catalog', function () {
    Livewire::test('auth.login')
        ->set('email', 'siswa@perpus.sch.id')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect('/');

    $this->assertAuthenticated();
    expect(auth()->user()->role)->toBe('siswa');
});

test('login fails with invalid credentials', function () {
    Livewire::test('auth.login')
        ->set('email', 'siswa@perpus.sch.id')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

test('student registration creates user with siswa role and redirects to catalog', function () {
    Livewire::test('auth.register')
        ->set('name', 'Ahmad Fadil')
        ->set('email', 'fadil@siswa.sch.id')
        ->set('nis', '109988')
        ->set('class_name', 'XI RPL 2')
        ->set('password', 'password123')
        ->call('register')
        ->assertRedirect('/');

    $this->assertAuthenticated();

    $user = User::where('email', 'fadil@siswa.sch.id')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe('siswa');
    expect($user->nis)->toBe('109988');
    expect($user->class_name)->toBe('XI RPL 2');
});

test('catalog live search filters books by title or author', function () {
    Livewire::test('book-catalog')
        ->assertSee('Clean Code')
        ->assertSee('Laskar Pelangi')
        ->set('search', 'Clean')
        ->assertSee('Clean Code')
        ->assertDontSee('Laskar Pelangi')
        ->set('search', 'Andrea Hirata')
        ->assertSee('Laskar Pelangi')
        ->assertDontSee('Clean Code');
});

test('catalog category filter filters books by category', function () {
    $teknologi = Category::where('name', 'Teknologi')->first();
    $novel = Category::where('name', 'Novel')->first();

    Livewire::test('book-catalog')
        ->set('category_id', (string) $teknologi->id)
        ->assertSee('Clean Code')
        ->assertDontSee('Laskar Pelangi')
        ->set('category_id', (string) $novel->id)
        ->assertSee('Laskar Pelangi')
        ->assertDontSee('Clean Code');
});

test('book detail renders full info and embeds book-action', function () {
    $book = Book::first();

    $this->get('/books/'.$book->id)
        ->assertOk()
        ->assertSee($book->title)
        ->assertSee($book->author)
        ->assertSee($book->category->rack_code)
        ->assertSee('Halaman Aksi Buku');
});
