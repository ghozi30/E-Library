<?php

use App\Models\Book;
use App\Models\Bookmark;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('models have proper relations and guarded attributes', function () {
    $category = Category::create([
        'name' => 'Teknologi',
        'rack_code' => 'RAK-TEK-01',
    ]);

    $book = Book::create([
        'category_id' => $category->id,
        'title' => 'Laravel Livewire 4 SFC',
        'author' => 'Taylor & Caleb',
        'stock' => 5,
        'synopsis' => 'Buku mantap.',
    ]);

    $user = User::create([
        'name' => 'Budi Siswa',
        'email' => 'budi@perpus.sch.id',
        'nis' => '123456',
        'class_name' => 'XII RPL 1',
        'password' => bcrypt('password'),
        'role' => 'siswa',
    ]);

    $borrowing = Borrowing::create([
        'borrow_code' => 'PJM-TEST01',
        'user_id' => $user->id,
        'book_id' => $book->id,
        'borrow_date' => now()->subDays(10)->toDateString(),
        'due_date' => now()->subDays(3)->toDateString(),
        'status' => 'dipinjam',
        'fine_amount' => 6000,
    ]);

    $bookmark = Bookmark::create([
        'user_id' => $user->id,
        'book_id' => $book->id,
    ]);

    // Test Category relations
    expect($category->books)->toHaveCount(1)
        ->and($category->books->first()->id)->toBe($book->id);

    // Test Book relations
    expect($book->category->id)->toBe($category->id)
        ->and($book->borrowings)->toHaveCount(1)
        ->and($book->bookmarks)->toHaveCount(1)
        ->and($book->bookmarkedByUsers)->toHaveCount(1);

    // Test User relations
    expect($user->borrowings)->toHaveCount(1)
        ->and($user->bookmarks)->toHaveCount(1)
        ->and($user->bookmarkedBooks)->toHaveCount(1)
        ->and($user->isSiswa())->toBeTrue()
        ->and($user->isAdmin())->toBeFalse();

    // Test Borrowing relations & casts
    expect($borrowing->user->id)->toBe($user->id)
        ->and($borrowing->book->id)->toBe($book->id)
        ->and($borrowing->borrow_date)->toBeInstanceOf(Carbon::class)
        ->and($borrowing->fine_amount)->toBe(6000);

    // Test Bookmark relations
    expect($bookmark->user->id)->toBe($user->id)
        ->and($bookmark->book->id)->toBe($book->id);
});
