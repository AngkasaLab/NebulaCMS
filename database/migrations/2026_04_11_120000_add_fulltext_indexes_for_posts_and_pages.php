<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Full-text indexes for scalable post/page search (MySQL, MariaDB, PostgreSQL).
     * SQLite uses LIKE fallback in application code.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table('posts', function (Blueprint $table) {
                $table->fullText(['title', 'content'], 'posts_title_content_fulltext');
            });
            Schema::table('pages', function (Blueprint $table) {
                $table->fullText(['title', 'content'], 'pages_title_content_fulltext');
            });
        } elseif ($driver === 'pgsql') {
            Schema::table('posts', function (Blueprint $table) {
                $table->fullText(['title', 'content'], 'posts_title_content_fulltext')->language('english');
            });
            Schema::table('pages', function (Blueprint $table) {
                $table->fullText(['title', 'content'], 'pages_title_content_fulltext')->language('english');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
            return;
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->dropFullText(['title', 'content']);
        });
        Schema::table('pages', function (Blueprint $table) {
            $table->dropFullText(['title', 'content']);
        });
    }
};
