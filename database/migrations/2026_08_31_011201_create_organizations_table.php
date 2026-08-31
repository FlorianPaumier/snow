<?php

declare(strict_types=1);

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
        Schema::create('organizations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignUuid('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->foreignUuid('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();

            // Client numbers are per-organization sequences: replace the
            // global unique index with a composite one...
            $table->dropUnique(['clientNumber']);
            $table->unique(['organization_id', 'clientNumber']);
        });

        Schema::table('contracts', function (Blueprint $table): void {
            $table->foreignUuid('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();
        });

        Schema::table('contract_services', function (Blueprint $table): void {
            $table->foreignUuid('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignUuid('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organization_id');
        });

        Schema::table('contract_services', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organization_id');
        });

        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organization_id');
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->dropUnique(['organization_id', 'clientNumber']);
            $table->dropConstrainedForeignId('organization_id');
            $table->unique(['clientNumber']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organization_id');
        });

        Schema::dropIfExists('organizations');
    }
};
