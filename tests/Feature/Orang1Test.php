<?php

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('siswa can login and is redirected to home', function () {
    $user = User::create([
        'name' => 'Budi Siswa',
        'email' => 'budi@perpus.sch.id',
        'nis' => '102938',
        'class_name' => 'XII RPL 1',
        'password' => Hash::make('password123'),
        'role' => 'siswa',
    ]);

    Livewire::test('auth.login')
        ->set('email', 'budi@perpus.sch.id')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect(auth()->check())->toBeTrue()
        ->and(auth()->id())->toBe($user->id);
});

test('admin can login and is redirected to admin dashboard', function () {
    $admin = User::create([
        'name' => 'Admin Perpus',
        'email' => 'admin@perpus.sch.id',
        'password' => Hash::make('password123'),
        'role' => 'admin',
    ]);

    Livewire::test('auth.login')
        ->set('email', 'admin@perpus.sch.id')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect('/admin/dashboard');

    expect(auth()->check())->toBeTrue()
        ->and(auth()->user()->isAdmin())->toBeTrue();
});

test('login with invalid credentials fails and shows error', function () {
    User::create([
        'name' => 'Siswa Test',
        'email' => 'siswa@perpus.sch.id',
        'password' => Hash::make('password123'),
        'role' => 'siswa',
    ]);

    Livewire::test('auth.login')
        ->set('email', 'siswa@perpus.sch.id')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(auth()->check())->toBeFalse();
});

test('new student can register with role siswa and is logged in', function () {
    Livewire::test('auth.register')
        ->set('name', 'Siti Rahma')
        ->set('email', 'siti@perpus.sch.id')
        ->set('nis', '109988')
        ->set('class_name', 'XI TKJ 2')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $user = User::where('email', 'siti@perpus.sch.id')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe('siswa')
        ->and($user->nis)->toBe('109988')
        ->and($user->class_name)->toBe('XI TKJ 2')
        ->and(auth()->check())->toBeTrue()
        ->and(auth()->id())->toBe($user->id);
});

test('book catalog can filter by search keyword and category', function () {
    $catTek = Category::create(['name' => 'Teknologi', 'rack_code' => 'RAK-TEK']);
    $catNov = Category::create(['name' => 'Novel', 'rack_code' => 'RAK-NOV']);

    $book1 = Book::create([
        'category_id' => $catTek->id,
        'title' => 'Mastering Laravel',
        'author' => 'Taylor',
        'stock' => 3,
        'synopsis' => 'Belajar Laravel.',
    ]);

    $book2 = Book::create([
        'category_id' => $catNov->id,
        'title' => 'Laskar Pelangi',
        'author' => 'Andrea Hirata',
        'stock' => 0,
        'synopsis' => 'Novel inspiratif.',
    ]);

    // Test default lists all
    Livewire::test('book-catalog')
        ->assertSee('Mastering Laravel')
        ->assertSee('Laskar Pelangi')
        ->assertSee('Tersedia: 3')
        ->assertSee('Stok Habis');

    // Test filter by search
    Livewire::test('book-catalog')
        ->set('search', 'Andrea')
        ->assertSee('Laskar Pelangi')
        ->assertDontSee('Mastering Laravel');

    // Test filter by category
    Livewire::test('book-catalog')
        ->set('category_id', (string) $catTek->id)
        ->assertSee('Mastering Laravel')
        ->assertDontSee('Laskar Pelangi');
});

test('book detail renders book information and embeds book-action', function () {
    $cat = Category::create(['name' => 'Sains', 'rack_code' => 'RAK-SAI']);
    $book = Book::create([
        'category_id' => $cat->id,
        'title' => 'Kosmos Semesta',
        'author' => 'Carl Sagan',
        'stock' => 4,
        'synopsis' => 'Petualangan astronomi spektakuler.',
    ]);

    Livewire::test('book-detail', ['id' => $book->id])
        ->assertSee('Kosmos Semesta')
        ->assertSee('Carl Sagan')
        ->assertSee('RAK-SAI')
        ->assertSee('Tersedia: 4 Buku')
        ->assertSee('Petualangan astronomi spektakuler.')
        ->assertSeeHtml('wire:snapshot'); // book-action component is mounted inside
});

