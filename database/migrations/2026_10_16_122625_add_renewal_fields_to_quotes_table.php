<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            // Preserve provenance if the source is deleted; anonymization removes the quotes themselves.
            $table->uuid('source_contract_id')->nullable()->index();
            $table->timestamp('renewal_reviewed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropIndex(['source_contract_id']);
            $table->dropColumn(['source_contract_id', 'renewal_reviewed_at']);
        });
    }
};
