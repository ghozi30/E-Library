<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
