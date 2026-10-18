<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FULLTEXT is a MySQL feature. The test suite runs on in-memory
        // SQLite, which cannot create it, so we skip it there. Search tests
        // for MySQL-specific behaviour are opt-in (see Lesson 16.6).
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('contacts', function (Blueprint $table) {
            // One index over the columns people search in, in one go.
            $table->fullText(['name', 'email', 'job_title'], 'contacts_search_fulltext');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropFullText('contacts_search_fulltext');
        });
    }
};
