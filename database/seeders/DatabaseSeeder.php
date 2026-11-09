<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin Perpus
        $admin = User::create([
            'name' => 'Admin Perpus',
            'email' => 'admin@perpus.sch.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // 2. Siswa (Budi Siswa)
        $siswa = User::create([
            'name' => 'Budi Siswa',
            'email' => 'siswa@perpus.sch.id',
            'nis' => '102938',
            'class_name' => 'XII RPL 1',
            'password' => Hash::make('password'),
            'role' => 'siswa',
        ]);

        // 3. 4 Kategori Rak
        $catTeknologi = Category::create([
            'name' => 'Teknologi',
            'rack_code' => 'RAK-TEK-01',
        ]);

        $catNovel = Category::create([
            'name' => 'Novel',
            'rack_code' => 'RAK-NOV-01',
        ]);

        $catSains = Category::create([
            'name' => 'Sains',
            'rack_code' => 'RAK-SAI-01',
        ]);

        $catSejarah = Category::create([
            'name' => 'Sejarah',
            'rack_code' => 'RAK-SEJ-01',
        ]);

        // 4. 6 Data Buku
        $book1 = Book::create([
            'category_id' => $catTeknologi->id,
            'title' => 'Pemrograman Web Modern dengan Laravel & Livewire',
            'author' => 'Taylor Otwell & Caleb Porzio',
            'cover_image' => null,
            'stock' => 5,
            'synopsis' => 'Panduan lengkap membangun aplikasi web modern, dinamis, dan reaktif menggunakan arsitektur Laravel dan Livewire Single-File Components.',
        ]);

        $book2 = Book::create([
            'category_id' => $catTeknologi->id,
            'title' => 'Kecerdasan Buatan dan Rekayasa Prompt',
            'author' => 'Andrew Ng',
            'cover_image' => null,
            'stock' => 3,
            'synopsis' => 'Eksplorasi mendalam mengenai teknologi machine learning, pemrosesan bahasa alami (NLP), dan strategi prompt engineering untuk AI masa depan.',
        ]);

        $book3 = Book::create([
            'category_id' => $catNovel->id,
            'title' => 'Laskar Pelangi',
            'author' => 'Andrea Hirata',
            'cover_image' => null,
            'stock' => 7,
            'synopsis' => 'Kisah inspiratif perjuangan sepuluh anak di Belitung dalam meraih pendidikan dan menggapai mimpi di tengah segala keterbatasan.',
        ]);

        $book4 = Book::create([
            'category_id' => $catNovel->id,
            'title' => 'Bumi',
            'author' => 'Tere Liye',
            'cover_image' => null,
            'stock' => 4,
            'synopsis' => 'Petualangan fantasi tiga sahabat, Raib, Seli, dan Ali, yang mengungkap rahasia klan Bulan dan petualangan melintasi dunia paralel.',
        ]);

        $book5 = Book::create([
            'category_id' => $catSains->id,
            'title' => 'Kosmos: Menjelajahi Alam Semesta',
            'author' => 'Carl Sagan',
            'cover_image' => null,
            'stock' => 2,
            'synopsis' => 'Perjalanan luar biasa menyusuri 15 miliar tahun evolusi kosmik, penjelajahan galaksi, dan sejarah pencarian sains manusia.',
        ]);

        $book6 = Book::create([
            'category_id' => $catSejarah->id,
            'title' => 'Sapiens: Riwayat Singkat Umat Manusia',
            'author' => 'Yuval Noah Harari',
            'cover_image' => null,
            'stock' => 6,
            'synopsis' => 'Sebuah narasi mendalam yang menelusuri bagaimana spesies kera tak berarti bisa menjadi penguasa bumi lewat revolusi kognitif, agrikultur, dan sains.',
        ]);

        // 5. 1 Data Borrowing untuk Budi Siswa (terlambat 3 hari, denda Rp 6.000)
        Borrowing::create([
            'borrow_code' => 'PJM-'.strtoupper(Str::random(8)),
            'user_id' => $siswa->id,
            'book_id' => $book1->id,
            'borrow_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDays(3)->toDateString(),
            'return_date' => null,
            'status' => 'dipinjam',
            'fine_amount' => 6000,
        ]);
    }
}
