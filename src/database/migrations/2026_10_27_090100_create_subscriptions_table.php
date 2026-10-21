<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            // A subscription belongs to the TENANT (the paying company),
            // not to a user.
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // restrictOnDelete: a plan that has subscribers cannot be
            // deleted. Retire it with plans.is_active = false instead.
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();

            $table->string('status');

            // Which provider handles the money ("fake" in dev, "stripe"),
            // and our references to it. We store REFERENCES only, never
            // card data.
            $table->string('provider');
            $table->string('provider_customer_id')->nullable();
            $table->string('provider_subscription_id')->nullable()->unique();

            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
