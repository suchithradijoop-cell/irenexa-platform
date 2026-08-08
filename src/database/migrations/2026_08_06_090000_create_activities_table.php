<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // morphs('subject') creates BOTH columns needed for a
            // polymorphic relationship in one line:
            //   - subject_type (string)  e.g. "App\Models\Contact"
            //   - subject_id   (bigint)  e.g. 42
            // and adds an index on both together, since every lookup
            // filters by both columns at once.
            $table->morphs('subject');

            $table->string('type')->default('note');
            $table->text('content');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
