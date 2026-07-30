<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('posts')
            ->where('status', 'published')
            ->where('published_at', '>', $now)
            ->update(['published_at' => $now]);
    }

    public function down(): void
    {
        // This is a data-only migration; rollback is not supported.
    }
};
