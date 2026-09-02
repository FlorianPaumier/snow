<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anonymization (Quebec Law 25 s. 22) erases the client's identity
     * fields in place while the financial skeleton stays for accountability;
     * these columns record that the erasure happened and who triggered it.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->timestamp('anonymized_at')->nullable()->index();
            $table->foreignUuid('anonymized_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('anonymized_by');
            $table->dropIndex(['anonymized_at']);
            $table->dropColumn('anonymized_at');
        });
    }
};
