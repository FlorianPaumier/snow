<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table): void {
            $table->foreignUuid('dispatch_id')->nullable()->constrained('tour_dispatches')->nullOnDelete();
            $table->foreignUuid('template_id')->nullable()->constrained('tour_templates')->nullOnDelete();
            $table->unique(['dispatch_id', 'template_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table): void {
            $table->dropUnique(['dispatch_id', 'template_id']);
            $table->dropConstrainedForeignId('dispatch_id');
            $table->dropConstrainedForeignId('template_id');
        });
    }
};
