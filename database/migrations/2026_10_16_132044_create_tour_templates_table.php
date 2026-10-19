<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('worker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('departure_address', 1000)->nullable();
            $table->string('arrival_address', 1000)->nullable();
            $table->json('client_ids');
            $table->timestamps();
            $table->index(['organization_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_templates');
    }
};
