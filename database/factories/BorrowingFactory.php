<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Borrowing>
 */
class BorrowingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $borrowDate = fake()->dateTimeBetween('-1 month', 'now');
        $dueDate = (clone $borrowDate)->modify('+7 days');

        return [
            'borrow_code' => 'BOR-'.strtoupper(fake()->unique()->bothify('??###')),
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'borrow_date' => $borrowDate->format('Y-m-d'),
            'due_date' => $dueDate->format('Y-m-d'),
            'return_date' => null,
            'status' => 'dipinjam',
            'fine_amount' => 0,
        ];
    }
}
