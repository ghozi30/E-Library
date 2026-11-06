<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Buat 1 Admin
        $admin = User::create([
            'name' => 'Admin Perpus',
            'email' => 'admin@perpus.sch.id',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // 2. Buat 1 Siswa
        $siswa = User::create([
            'name' => 'Budi Siswa',
            'email' => 'siswa@perpus.sch.id',
            'nis' => '102938',
            'class_name' => 'XII RPL 1',
            'password' => bcrypt('password'),
            'role' => 'siswa',
        ]);

        // 3. Buat 4 Kategori Rak
        $catTeknologi = Category::create([
            'name' => 'Teknologi',
            'rack_code' => 'RAK-TEK-01',
        ]);

        $catNovel = Category::create([
            'name' => 'Novel',
            'rack_code' => 'RAK-NOV-02',
        ]);

        $catSains = Category::create([
            'name' => 'Sains',
            'rack_code' => 'RAK-SAI-03',
        ]);

        $catSejarah = Category::create([
            'name' => 'Sejarah',
            'rack_code' => 'RAK-SEJ-04',
        ]);

        // 4. Buat 6 data Buku
        $book1 = Book::create([
            'category_id' => $catTeknologi->id,
            'title' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
            'author' => 'Robert C. Martin',
            'cover_image' => null,
            'stock' => 5,
            'synopsis' => 'Panduan komprehensif menulis kode yang bersih, mudah dipelihara, dan dapat dikembangkan dengan prinsip-prinsip software engineering modern.',
        ]);

        $book2 = Book::create([
            'category_id' => $catTeknologi->id,
            'title' => 'Pemrograman Web Modern dengan Laravel & Livewire',
            'author' => 'Taylor Otwell & Caleb Porzio',
            'cover_image' => null,
            'stock' => 4,
            'synopsis' => 'Eksplorasi pembuatan aplikasi web modern secara reaktif tanpa meninggalkan kenyamanan ekosistem fullstack Laravel.',
        ]);

        $book3 = Book::create([
            'category_id' => $catNovel->id,
            'title' => 'Laskar Pelangi',
            'author' => 'Andrea Hirata',
            'cover_image' => null,
            'stock' => 7,
            'synopsis' => 'Kisah penuh inspirasi tentang perjuangan sepuluh anak Belitung dalam menuntut ilmu dengan tekad dan harapan tak terbatas.',
        ]);

        $book4 = Book::create([
            'category_id' => $catNovel->id,
            'title' => 'Bumi Manusia',
            'author' => 'Pramoedya Ananta Toer',
            'cover_image' => null,
            'stock' => 3,
            'synopsis' => 'Kisah epik perjuangan Minke dalam melawan ketidakadilan dan diskriminasi di era kolonial Hindia Belanda.',
        ]);

        $book5 = Book::create([
            'category_id' => $catSains->id,
            'title' => 'Kosmos: Menjelajahi Misteri Alam Semesta',
            'author' => 'Carl Sagan',
            'cover_image' => null,
            'stock' => 6,
            'synopsis' => 'Perjalanan ilmiah mendalam tentang asal-usul alam raya, evolusi bintang, dan posisi kemanusiaan di jagat raya yang luas.',
        ]);

        $book6 = Book::create([
            'category_id' => $catSejarah->id,
            'title' => 'Sejarah Peradaban Kuno Dunia',
            'author' => 'Susan Wise Bauer',
            'cover_image' => null,
            'stock' => 2,
            'synopsis' => 'Rekaman kronologis berdirinya imperium-imperium besar, kebudayaan masa silam, dan warisan sejarah bagi dunia modern.',
        ]);

        // 5. Buat 1 data Borrowing untuk Budi Siswa (terlambat 3 hari, denda Rp 6.000)
        Borrowing::create([
            'borrow_code' => 'BOR-20261027-001',
            'user_id' => $siswa->id,
            'book_id' => $book1->id,
            'borrow_date' => Carbon::now()->subDays(10)->toDateString(),
            'due_date' => Carbon::now()->subDays(3)->toDateString(),
            'return_date' => null,
            'status' => 'dipinjam',
            'fine_amount' => 6000,
        ]);
    }
}
