<?php

use App\Models\Book;
use App\Models\Bookmark;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('authenticated student sees kartu baca siswa with active loans overdue alert fine and bookmarks on landing page', function () {
    $this->travelTo('2026-11-14 09:00:00');

    $student = User::factory()->create([
        'name' => 'Budi Siswa',
        'nis' => '102938',
        'class_name' => 'XII RPL 1',
        'role' => 'siswa',
    ]);

    $category = Category::create(['name' => 'Teknologi', 'rack_code' => 'RAK-TEK-01']);

    $overdueBook = Book::create([
        'category_id' => $category->id,
        'title' => 'Clean Architecture Handbook',
        'author' => 'Robert Martin',
        'stock' => 2,
    ]);

    $ontimeBook = Book::create([
        'category_id' => $category->id,
        'title' => 'Refactoring Patterns',
        'author' => 'Martin Fowler',
        'stock' => 5,
    ]);

    Borrowing::create([
        'borrow_code' => 'PJM-DUE3DAYS',
        'user_id' => $student->id,
        'book_id' => $overdueBook->id,
        'borrow_date' => '2026-11-04',
        'due_date' => '2026-11-11',
        'status' => 'dipinjam',
        'fine_amount' => 0,
    ]);

    Borrowing::create([
        'borrow_code' => 'PJM-ONTIME01',
        'user_id' => $student->id,
        'book_id' => $ontimeBook->id,
        'borrow_date' => '2026-11-13',
        'due_date' => '2026-11-20',
        'status' => 'dipinjam',
        'fine_amount' => 0,
    ]);

    Bookmark::create([
        'user_id' => $student->id,
        'book_id' => $ontimeBook->id,
    ]);

    $this->actingAs($student)
        ->get('/')
        ->assertOk()
        ->assertSee('Kartu Baca Siswa')
        ->assertSee('Budi Siswa')
        ->assertSee('NIS 102938')
        ->assertSee('XII RPL 1')
        ->assertSee('1 Terlambat')
        ->assertSee('Clean Architecture Handbook')
        ->assertSee('Terlambat 3 hari')
        ->assertSee('Rp 6.000')
        ->assertSee('/my-borrowings')
        ->assertSee('/my-bookmarks');
});

test('catalog supports available stock toggle category tabs sorting and reset filters', function () {
    $student = User::factory()->create(['role' => 'siswa']);
    $catNovel = Category::create(['name' => 'Novel', 'rack_code' => 'RAK-NOV-01']);
    $catTek = Category::create(['name' => 'Teknologi', 'rack_code' => 'RAK-TEK-01']);

    $bookAlphaOut = Book::create([
        'category_id' => $catNovel->id,
        'title' => 'Alpha Dusk',
        'author' => 'Ayu Lestari',
        'stock' => 0,
    ]);

    $bookZebraPopular = Book::create([
        'category_id' => $catTek->id,
        'title' => 'Zebra Systems',
        'author' => 'Bima Pratama',
        'stock' => 4,
    ]);

    Borrowing::create([
        'borrow_code' => 'PJM-POP-01',
        'user_id' => $student->id,
        'book_id' => $bookZebraPopular->id,
        'borrow_date' => now()->subDays(5),
        'due_date' => now()->addDays(2),
        'status' => 'dikembalikan',
        'return_date' => now()->subDay(),
        'fine_amount' => 0,
    ]);

    Borrowing::create([
        'borrow_code' => 'PJM-POP-02',
        'user_id' => $student->id,
        'book_id' => $bookZebraPopular->id,
        'borrow_date' => now()->subDays(1),
        'due_date' => now()->addDays(6),
        'status' => 'dipinjam',
        'fine_amount' => 0,
    ]);

    Livewire::test('book-catalog')
        ->set('onlyAvailable', true)
        ->assertSee('Zebra Systems')
        ->assertDontSee('Alpha Dusk')
        ->set('onlyAvailable', false)
        ->call('selectCategory', (string) $catNovel->id)
        ->assertSee('Alpha Dusk')
        ->assertDontSee('Zebra Systems')
        ->call('resetFilters')
        ->set('sortBy', 'title')
        ->assertSeeInOrder(['Alpha Dusk', 'Zebra Systems'])
        ->set('sortBy', 'popular')
        ->assertSeeInOrder(['Zebra Systems', 'Alpha Dusk']);
});

test('bookmark toggle persists for students and redirects guests to login', function () {
    $category = Category::create(['name' => 'Sains', 'rack_code' => 'RAK-SAI-01']);
    $book = Book::create([
        'category_id' => $category->id,
        'title' => 'Cosmos Explorer',
        'author' => 'Carl Sagan',
        'stock' => 3,
    ]);

    Livewire::test('book-catalog')
        ->call('toggleBookmark', $book->id)
        ->assertRedirect(route('login'));

    $student = User::factory()->create(['role' => 'siswa']);

    Livewire::actingAs($student)
        ->test('book-catalog')
        ->call('toggleBookmark', $book->id);

    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $student->id,
        'book_id' => $book->id,
    ]);

    Livewire::actingAs($student)
        ->test('book-catalog')
        ->call('toggleBookmark', $book->id);

    $this->assertDatabaseMissing('bookmarks', [
        'user_id' => $student->id,
        'book_id' => $book->id,
    ]);
});

test('admin visiting catalog landing page sees librarian quick circulation summary', function () {
    $this->travelTo('2026-11-14 09:00:00');

    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'siswa']);
    $category = Category::create(['name' => 'Sejarah', 'rack_code' => 'RAK-SEJ-01']);
    $book = Book::create([
        'category_id' => $category->id,
        'title' => 'Sejarah Nusantara',
        'author' => 'Sartono Kartodirdjo',
        'stock' => 3,
    ]);

    Borrowing::create([
        'borrow_code' => 'PJM-ADM-01',
        'user_id' => $student->id,
        'book_id' => $book->id,
        'borrow_date' => '2026-11-01',
        'due_date' => '2026-11-08',
        'status' => 'dipinjam',
        'fine_amount' => 0,
    ]);

    $this->actingAs($admin)
        ->get('/')
        ->assertOk()
        ->assertSee('Mode Pustakawan')
        ->assertSee('Ringkasan Sirkulasi Hari Ini')
        ->assertSee('1 Terlambat')
        ->assertSee('/admin/dashboard');
});

test('catalog book cards have double click navigation binding to book detail', function () {
    $category = Category::create(['name' => 'Fiksi', 'rack_code' => 'RAK-FIK-01']);
    $book = Book::create([
        'category_id' => $category->id,
        'title' => 'Negeri 5 Menara',
        'author' => 'Ahmad Fuadi',
        'stock' => 4,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Negeri 5 Menara')
        ->assertSee('@dblclick', false)
        ->assertSee("Livewire.navigate('".route('books.detail', $book->id)."')", false);
});

test('featured hero card on catalog displays the single newest book automatically', function () {
    $student = User::factory()->create(['role' => 'siswa']);
    $category = Category::create(['name' => 'Teknologi', 'rack_code' => 'RAK-TEK-01']);

    $olderPopularBook = Book::create([
        'category_id' => $category->id,
        'title' => 'Buku Lama Populer',
        'author' => 'Penulis Lama',
        'stock' => 5,
    ]);

    Borrowing::create([
        'borrow_code' => 'PJM-OLD-01',
        'user_id' => $student->id,
        'book_id' => $olderPopularBook->id,
        'borrow_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'status' => 'dipinjam',
        'fine_amount' => 0,
    ]);

    $newestBook = Book::create([
        'category_id' => $category->id,
        'title' => 'Buku Terbaru Hari Ini',
        'author' => 'Penulis Baru',
        'stock' => 3,
    ]);

    $component = Livewire::test('book-catalog');
    $featured = $component->instance()->featuredBook();

    expect($featured)->not->toBeNull()
        ->and($featured->id)->toBe($newestBook->id)
        ->and($featured->title)->toBe('Buku Terbaru Hari Ini');

    $component->assertSee('Koleksi Terbaru');
});
