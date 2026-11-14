<?php

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('admin borrowings can search by book title and confirm return idempotently', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'siswa', 'nis' => '123456', 'class_name' => 'XII IPA 1']);
    $category = Category::create(['name' => 'Fiksi', 'rack_code' => 'RAK-A1']);

    $targetBook = Book::create([
        'category_id' => $category->id,
        'title' => 'Laskar Pelangi',
        'author' => 'Andrea Hirata',
        'stock' => 2,
        'synopsis' => 'Novel inspiratif.',
    ]);

    $otherBook = Book::create([
        'category_id' => $category->id,
        'title' => 'Bumi Manusia',
        'author' => 'Pramoedya Ananta Toer',
        'stock' => 4,
        'synopsis' => 'Novel sejarah.',
    ]);

    $targetBorrowing = Borrowing::create([
        'borrow_code' => 'PJM-0001',
        'user_id' => $student->id,
        'book_id' => $targetBook->id,
        'borrow_date' => now()->subDays(10)->toDateString(),
        'due_date' => now()->subDays(3)->toDateString(),
        'status' => 'dipinjam',
        'fine_amount' => 0,
    ]);

    Borrowing::create([
        'borrow_code' => 'PJM-0002',
        'user_id' => $student->id,
        'book_id' => $otherBook->id,
        'borrow_date' => now()->subDays(2)->toDateString(),
        'due_date' => now()->addDays(5)->toDateString(),
        'status' => 'dipinjam',
        'fine_amount' => 0,
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.borrowings')
        ->set('search', 'Laskar Pelangi')
        ->assertSee('Laskar Pelangi')
        ->assertDontSee('Bumi Manusia')
        ->call('confirmReturn', $targetBorrowing->id)
        ->call('confirmReturn', $targetBorrowing->id);

    $targetBorrowing->refresh();
    $targetBook->refresh();

    expect($targetBorrowing->status)->toBe('dikembalikan')
        ->and($targetBorrowing->fine_amount)->toBeGreaterThanOrEqual(6000)
        ->and($targetBook->stock)->toBe(3);
});

test('non-admin users receive forbidden when invoking admin livewire actions', function (): void {
    $student = User::factory()->create(['role' => 'siswa']);
    $category = Category::create(['name' => 'Sains', 'rack_code' => 'RAK-B1']);
    $book = Book::create([
        'category_id' => $category->id,
        'title' => 'Fisika Dasar',
        'author' => 'Penulis Sains',
        'stock' => 3,
        'synopsis' => 'Buku pelajaran fisika.',
    ]);
    $borrowing = Borrowing::create([
        'borrow_code' => 'PJM-0003',
        'user_id' => $student->id,
        'book_id' => $book->id,
        'borrow_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'status' => 'dipinjam',
        'fine_amount' => 0,
    ]);

    $this->actingAs($student);

    Livewire::test('admin.books')
        ->assertForbidden();

    Livewire::test('admin.categories')
        ->assertForbidden();

    Livewire::test('admin.borrowings')
        ->assertForbidden();

    Livewire::test('admin.dashboard')
        ->assertForbidden();
});

test('my borrowings renders storage cover image url properly', function (): void {
    $student = User::factory()->create(['role' => 'siswa']);
    $category = Category::create(['name' => 'Sejarah', 'rack_code' => 'RAK-C1']);
    $book = Book::create([
        'category_id' => $category->id,
        'title' => 'Sejarah Nusantara',
        'author' => 'Sejarawan',
        'cover_image' => 'covers/test.jpg',
        'stock' => 1,
        'synopsis' => 'Ringkasan sejarah.',
    ]);

    Borrowing::create([
        'borrow_code' => 'PJM-0004',
        'user_id' => $student->id,
        'book_id' => $book->id,
        'borrow_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
        'status' => 'dipinjam',
        'fine_amount' => 0,
    ]);

    $this->actingAs($student);

    Livewire::test('my-borrowings')
        ->assertSee('storage/covers/test.jpg');
});

test('admin categories normalizes rack code to uppercase when saved', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin);

    Livewire::test('admin.categories')
        ->set('name', 'Teknologi Informasi')
        ->set('rack_code', ' rak-ti-01 ')
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::where('name', 'Teknologi Informasi')->first();

    expect($category)->not->toBeNull()
        ->and($category->rack_code)->toBe('RAK-TI-01');
});
