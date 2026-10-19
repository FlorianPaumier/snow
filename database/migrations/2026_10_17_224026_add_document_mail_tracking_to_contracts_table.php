<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->string('document_mail_status')->nullable();
            $table->uuid('document_mail_request_id')->nullable();
            $table->timestamp('document_sent_at')->nullable();
            $table->foreignUuid('document_sent_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('document_sent_by');
            $table->dropColumn(['document_mail_status', 'document_mail_request_id', 'document_sent_at']);
        });
    }
};
