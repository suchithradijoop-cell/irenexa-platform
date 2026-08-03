<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            // Unlike leads (Lesson 5.4), tenant_id is added here from day
            // one, in the very first migration — not bolted on later.
            // Tenant already exists by this point, so there's no reason
            // to split it into a second migration.
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('job_title')->nullable();

            // company_id is deliberately NOT here yet — the companies
            // table doesn't exist until Lesson 8.3. Added there.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
