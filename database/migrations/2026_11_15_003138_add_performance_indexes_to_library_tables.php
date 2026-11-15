<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('stock');
            $table->index('created_at');
        });

        Schema::table('borrowings', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('book_id');
            $table->index(['status', 'due_date']);
            $table->index('borrow_date');
        });

        Schema::table('bookmarks', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('book_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['stock']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('borrowings', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['book_id']);
            $table->dropIndex(['status', 'due_date']);
            $table->dropIndex(['borrow_date']);
        });

        Schema::table('bookmarks', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['book_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
        });
    }
};
