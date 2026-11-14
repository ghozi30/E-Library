<?php

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guest and non admin users receive forbidden when visiting admin dashboard', function () {
    $student = User::factory()->create(['role' => 'siswa']);

    $this->get('/admin/dashboard')->assertForbidden();
    $this->actingAs($student)->get('/admin/dashboard')->assertForbidden();
});

test('admin can view dashboard with live circulation metrics rack distribution and low stock alerts', function () {
    $this->travelTo('2026-11-14 09:00:00');

    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create([
        'name' => 'Budi Santoso',
        'nis' => '102938',
        'class_name' => 'XII RPL 1',
        'role' => 'siswa',
    ]);

    $catTek = Category::create(['name' => 'Teknologi', 'rack_code' => 'RAK-TEK-01']);
    $catSains = Category::create(['name' => 'Sains', 'rack_code' => 'RAK-SAI-01']);

    $bookLowStock = Book::create([
        'category_id' => $catTek->id,
        'title' => 'Arsitektur Laravel',
        'author' => 'Taylor Otwell',
        'stock' => 1,
    ]);
    $bookHealthy = Book::create([
        'category_id' => $catSains->id,
        'title' => 'Fisika Modern',
        'author' => 'Carl Sagan',
        'stock' => 6,
    ]);

    Borrowing::create([
        'borrow_code' => 'PJM-OVERDUE1',
        'user_id' => $student->id,
        'book_id' => $bookLowStock->id,
        'borrow_date' => '2026-11-04',
        'due_date' => '2026-11-11',
        'status' => 'dipinjam',
        'fine_amount' => 0,
    ]);

    Borrowing::create([
        'borrow_code' => 'PJM-DONE0002',
        'user_id' => $student->id,
        'book_id' => $bookHealthy->id,
        'borrow_date' => '2026-11-01',
        'due_date' => '2026-11-08',
        'return_date' => '2026-11-07',
        'status' => 'dikembalikan',
        'fine_amount' => 0,
    ]);

    $response = $this->actingAs($admin)->get('/admin/dashboard');

    $response->assertOk()
        ->assertSee('Ringkasan Sirkulasi')
        ->assertSee('PJM-OVERDUE1')
        ->assertSee('Terlambat 3 hr')
        ->assertSee('Rp 6.000')
        ->assertSee('RAK-TEK-01')
        ->assertSee('Arsitektur Laravel')
        ->assertSee('Sisa 1');
});

test('admin can filter circulation ledger by status tabs and search term', function () {
    $this->travelTo('2026-11-14 09:00:00');

    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['name' => 'Citra Dewi', 'role' => 'siswa']);
    $category = Category::create(['name' => 'Novel', 'rack_code' => 'RAK-NOV-01']);
    $book = Book::create([
        'category_id' => $category->id,
        'title' => 'Laskar Pelangi',
        'author' => 'Andrea Hirata',
        'stock' => 5,
    ]);

    Borrowing::create([
        'borrow_code' => 'PJM-LATE99',
        'user_id' => $student->id,
        'book_id' => $book->id,
        'borrow_date' => '2026-11-02',
        'due_date' => '2026-11-10',
        'status' => 'dipinjam',
        'fine_amount' => 0,
    ]);

    Borrowing::create([
        'borrow_code' => 'PJM-ONTIME1',
        'user_id' => $student->id,
        'book_id' => $book->id,
        'borrow_date' => '2026-11-12',
        'due_date' => '2026-11-19',
        'status' => 'dipinjam',
        'fine_amount' => 0,
    ]);

    Borrowing::create([
        'borrow_code' => 'PJM-FINISH2',
        'user_id' => $student->id,
        'book_id' => $book->id,
        'borrow_date' => '2026-11-01',
        'due_date' => '2026-11-05',
        'return_date' => '2026-11-05',
        'status' => 'dikembalikan',
        'fine_amount' => 0,
    ]);

    Livewire::actingAs($admin)
        ->test('admin.dashboard')
        ->call('setCirculationFilter', 'overdue')
        ->assertSee('PJM-LATE99')
        ->assertDontSee('PJM-ONTIME1')
        ->assertDontSee('PJM-FINISH2')
        ->call('setCirculationFilter', 'returned')
        ->assertSee('PJM-FINISH2')
        ->assertDontSee('PJM-LATE99')
        ->call('resetLedgerFilters')
        ->set('search', 'ONTIME1')
        ->assertSee('PJM-ONTIME1')
        ->assertDontSee('PJM-LATE99');
});

test('admin can confirm return directly from dashboard and stock is incremented safely', function () {
    $this->travelTo('2026-11-14 09:00:00');

    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['name' => 'Ahmad Fauzi', 'role' => 'siswa']);
    $category = Category::create(['name' => 'Sejarah', 'rack_code' => 'RAK-SEJ-01']);
    $book = Book::create([
        'category_id' => $category->id,
        'title' => 'Sapiens',
        'author' => 'Yuval Noah Harari',
        'stock' => 2,
    ]);

    $borrowing = Borrowing::create([
        'borrow_code' => 'PJM-RET001',
        'user_id' => $student->id,
        'book_id' => $book->id,
        'borrow_date' => '2026-11-04',
        'due_date' => '2026-11-11',
        'status' => 'dipinjam',
        'fine_amount' => 0,
    ]);

    Livewire::actingAs($admin)
        ->test('admin.dashboard')
        ->call('confirmReturn', $borrowing->id)
        ->call('confirmReturn', $borrowing->id);

    $borrowing->refresh();
    $book->refresh();

    expect($borrowing->status)->toBe('dikembalikan')
        ->and($borrowing->return_date?->toDateString())->toBe('2026-11-14')
        ->and($borrowing->fine_amount)->toBe(6000)
        ->and($book->stock)->toBe(3);
});

test('popular books shows top 5 borrowed books and excludes books with zero borrowings', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'siswa']);
    $category = Category::create(['name' => 'Umum', 'rack_code' => 'RAK-UM-01']);

    $unborrowed = Book::create([
        'category_id' => $category->id,
        'title' => 'Buku Belum Dipinjam',
        'author' => 'Penulis Nol',
        'stock' => 5,
    ]);

    $borrowedBooks = [];
    foreach (range(1, 6) as $index) {
        $book = Book::create([
            'category_id' => $category->id,
            'title' => "Buku Populer {$index}",
            'author' => "Penulis {$index}",
            'stock' => 10,
        ]);
        $borrowedBooks[$index] = $book;

        foreach (range(1, $index) as $copy) {
            Borrowing::create([
                'borrow_code' => sprintf('PJM-%02d-%02d', $index, $copy),
                'user_id' => $student->id,
                'book_id' => $book->id,
                'borrow_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'status' => 'dipinjam',
                'fine_amount' => 0,
            ]);
        }
    }

    $component = Livewire::actingAs($admin)->test('admin.dashboard');
    $popular = $component->instance()->popularBooks();

    expect($popular)->toHaveCount(5)
        ->and($popular->pluck('title')->all())->toBe([
            'Buku Populer 6',
            'Buku Populer 5',
            'Buku Populer 4',
            'Buku Populer 3',
            'Buku Populer 2',
        ])
        ->and($popular->pluck('id')->all())->not->toContain($unborrowed->id)
        ->and($popular->pluck('id')->all())->not->toContain($borrowedBooks[1]->id);

    $component->assertDontSee('0× pinjam');
});

test('popular books renders empty message when no books have been borrowed yet', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = Category::create(['name' => 'Umum', 'rack_code' => 'RAK-UM-02']);
    Book::create([
        'category_id' => $category->id,
        'title' => 'Buku Sepi',
        'author' => 'Penulis',
        'stock' => 5,
    ]);

    Livewire::actingAs($admin)
        ->test('admin.dashboard')
        ->assertSee('Belum ada riwayat peminjaman buku.')
        ->assertDontSee('0× pinjam');
});
