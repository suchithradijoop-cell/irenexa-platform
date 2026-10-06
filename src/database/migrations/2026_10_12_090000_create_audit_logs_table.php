<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // What happened, e.g. "lead.converted" — the same naming
            // style as workflow triggers.
            $table->string('event');

            // Which record it happened to (polymorphic, like activities).
            $table->morphs('subject');

            // Extra facts about the event (amounts, ids). Nullable.
            $table->json('data')->nullable();

            // Append-only history: rows are written once and never
            // updated, so only created_at exists.
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
