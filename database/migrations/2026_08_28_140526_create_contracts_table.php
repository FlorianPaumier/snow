<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Enums\PaymentSlot;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('season');
            $table->timestamps();

            $table->foreignUuid('client_id')
                ->constrained('clients');

            // A client signs exactly one contract per season...
            $table->unique(['client_id', 'season']);
        });

        Schema::create('contract_services', function (Blueprint $table): void {
            $table->id();

            $table->string('label');
            $table->integer('amount')->default(0);
            $table->integer('position');

            $table->foreignUuid('contract_id')
                ->constrained('contracts')
                ->cascadeOnDelete();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();

            $table->enum('slot', PaymentSlot::cases());
            $table->integer('amount')->default(0);
            $table->boolean('paid')->default(false);
            $table->enum('method', PaymentMethod::cases())->nullable();
            $table->dateTime('paid_at')->nullable();

            $table->foreignUuid('contract_id')
                ->constrained('contracts')
                ->cascadeOnDelete();

            $table->unique(['contract_id', 'slot']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('contract_services');
        Schema::dropIfExists('contracts');
    }
};
