<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_dispatches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->uuid('request_key');
            $table->date('scheduled_date');
            $table->json('template_ids');
            $table->timestamps();
            $table->unique(['organization_id', 'request_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_dispatches');
    }
};
