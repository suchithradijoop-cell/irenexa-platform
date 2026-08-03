<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Nullable on purpose: you often meet a real person before you
            // know (or before we've recorded) which company they work at.
            $table->foreignId('company_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained()
                // If a Company is deleted, don't delete the real people
                // who work there — just unlink them. A person's contact
                // info is still worth keeping even if their employer
                // record goes away. Different meaning than tenant_id,
                // which cascades because a tenant owns everything under it.
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
