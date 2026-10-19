<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_services', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('amount');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_services');
    }
};
