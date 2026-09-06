<?php

declare(strict_types=1);

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
        Schema::create('organization_invites', function (Blueprint $table): void {
            $table->id();
            // The invite is bound to the prospect's email: the code only
            // works for the person it was sent to...
            $table->string('email')->index();
            // SHA-256 of the code; the plaintext only ever lives in the
            // outgoing email...
            $table->string('code')->index();
            $table->timestamp('expires_at');
            // Set when the invite is redeemed at registration, so a code
            // can never found a second organization...
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_invites');
    }
};
