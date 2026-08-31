<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('login_challenge_code')->nullable()->after('validation_code_expires_at');
            $table->timestamp('login_challenge_code_expires_at')->nullable()->after('login_challenge_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['login_challenge_code', 'login_challenge_code_expires_at']);
        });
    }
};
