<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The plan catalogue is GLOBAL: the same Free/Pro/Business plans
        // are offered to every tenant, so there is deliberately no
        // tenant_id here.
        Schema::create('plans', function (Blueprint $table) {
            $table->id();

            // Stable code identifier ("pro"), used in code and APIs. The
            // display name can change; the key never does.
            $table->string('key')->unique();
            $table->string('name');

            // Money is an INTEGER in the smallest unit (4900 = 49.00),
            // never a float, and always travels with its currency.
            $table->unsignedInteger('price_cents');
            $table->char('currency', 3)->default('USD');
            $table->string('interval')->default('month');

            // The limit this plan grants. NULL means unlimited.
            $table->unsignedInteger('max_contacts')->nullable();

            // The price's id at the payment provider (e.g. Stripe
            // "price_..."). Null for the free plan.
            $table->string('provider_price_id')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
