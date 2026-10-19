<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('stripe_connect_id')->nullable()->unique();
        });
        Schema::table('payments', function (Blueprint $table): void {
            $table->timestamp('stripe_checkout_started_at')->nullable();
            $table->uuid('stripe_checkout_key')->nullable()->unique();
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->unique();
            $table->string('stripe_status')->nullable();
            $table->unsignedInteger('stripe_reversed_amount')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['stripe_checkout_key']);
            $table->dropUnique(['stripe_session_id']);
            $table->dropUnique(['stripe_payment_intent_id']);
            $table->dropColumn(['stripe_checkout_started_at', 'stripe_checkout_key', 'stripe_session_id', 'stripe_payment_intent_id', 'stripe_status', 'stripe_reversed_amount']);
        });
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropUnique(['stripe_connect_id']);
            $table->dropColumn('stripe_connect_id');
        });
    }
};
