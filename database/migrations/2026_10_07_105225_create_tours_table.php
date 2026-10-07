<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('worker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->date('scheduled_date');
            $table->string('departure_address', 1000)->nullable();
            $table->string('arrival_address', 1000)->nullable();
            $table->json('stops');
            $table->timestamp('optimized_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'worker_id', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
