<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->boolean('route_notifications_enabled')->default(false);
        });
        Schema::create('tour_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('tour_id')->constrained()->cascadeOnDelete();
            // Historical recipient reference, retained when a client is erased or deleted.
            $table->uuid('client_id');
            $table->string('recipient_key', 64)->nullable();
            $table->string('event', 16);
            $table->string('locale', 2);
            $table->string('status', 16)->default('pending');
            $table->string('reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['tour_id', 'event', 'client_id']);
            $table->unique(['tour_id', 'event', 'recipient_key']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_notifications');
        Schema::table('clients', fn (Blueprint $table) => $table->dropColumn('route_notifications_enabled'));
    }
};
