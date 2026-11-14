<?php

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('non admin cannot access admin books page', function () {
    $student = User::factory()->create(['role' => 'siswa']);

    $this->get('/admin/books')->assertForbidden();
    $this->actingAs($student)->get('/admin/books')->assertForbidden();
});

test('admin can view admin books page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/books')
        ->assertOk()
        ->assertSee('Kelola Koleksi Buku');
});

test('admin can create book with uploaded cover image', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin']);
    $category = Category::create(['name' => 'Teknologi', 'rack_code' => 'RAK-TEK-01']);
    $cover = UploadedFile::fake()->image('cover_test.jpg', 600, 800);

    Livewire::actingAs($admin)
        ->test('admin.books')
        ->call('openCreateModal')
        ->set('category_id', $category->id)
        ->set('title', 'Belajar Laravel Livewire')
        ->set('author', 'Taylor Otwell')
        ->set('stock', 5)
        ->set('synopsis', 'Sinopsis buku livewire')
        ->set('cover', $cover)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('isModalOpen', false);

    $book = Book::where('title', 'Belajar Laravel Livewire')->first();
    expect($book)->not->toBeNull();
    expect($book->cover_image)->not->toBeNull();
    Storage::disk('public')->assertExists($book->cover_image);
});
