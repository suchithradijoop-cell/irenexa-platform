<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // The notification class that created this row.
            $table->string('type');

            // Who it was sent to — polymorphic, normally a User. There is
            // deliberately NO tenant_id column: a notification belongs to
            // one user, and a user belongs to one tenant, so isolation
            // follows the recipient (ADR 008).
            $table->morphs('notifiable');

            // The JSON payload returned by toArray()/toDatabase().
            $table->text('data');

            // null = unread.
            $table->timestamp('read_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
