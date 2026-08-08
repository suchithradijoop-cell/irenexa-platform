<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // e.g. "lead.converted" — matched against a fixed, known set
            // of trigger names the app actually fires (Lesson 9.5).
            $table->string('trigger');

            // Structured, not free text — e.g.
            // {"field": "deal_amount", "operator": ">", "value": 5000}
            // Nullable: no condition means "always run this action".
            $table->json('conditions')->nullable();

            // A short KEY (e.g. "create_activity"), never a raw class
            // name — see the security note in this lesson.
            $table->string('action');

            // Parameters the action needs, e.g. {"content": "Follow up"}
            $table->json('action_config')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_rules');
    }
};
