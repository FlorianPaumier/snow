<?php

declare(strict_types=1);

use App\Enums\ContactInquiryStatus;
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
        Schema::create('contact_inquiries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email');
            $table->string('company')->nullable();
            $table->string('fleetSize')->nullable();
            $table->string('message', 5000);
            // Delivery bookkeeping: the send job flips pending -> sent once
            // SMTP accepts the message, and failed when retries are exhausted...
            $table->enum('status', ContactInquiryStatus::cases())->default(ContactInquiryStatus::STATUS_PENDING->value)->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_inquiries');
    }
};
