<?php

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $category = Category::create([
        'name' => 'Teknologi',
        'rack_code' => 'RAK-TEK-01',
    ]);

    Book::create([
        'id' => 1,
        'category_id' => $category->id,
        'title' => 'Buku Panduan Laravel',
        'author' => 'Taylor Otwell',
        'stock' => 5,
        'synopsis' => 'Sinopsis buku panduan Laravel.',
    ]);
});

test('public and visitor routes can be rendered', function (string $url) {
    $response = $this->get($url);

    $response->assertStatus(200);
})->with([
    '/',
    '/login',
    '/register',
    '/books/1',
    '/books/1/action',
    '/my-bookmarks',
    '/my-borrowings',
    '/admin/dashboard',
    '/admin/categories',
    '/admin/books',
    '/admin/borrowings',
]);

test('logout route redirects to home', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/');
    $this->assertGuest();
});
