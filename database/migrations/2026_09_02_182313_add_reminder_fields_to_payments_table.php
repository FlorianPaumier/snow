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
        Schema::table('payments', function (Blueprint $table): void {
            // Reminders are opt-in per installment: only payments with an
            // explicit due date are ever reminded...
            $table->date('due_date')->nullable()->index()->after('slot');
            $table->timestamp('last_reminder_at')->nullable()->after('paid_at');
            $table->unsignedSmallInteger('automated_reminders_sent')->default(0)->after('last_reminder_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['due_date']);
            $table->dropColumn(['due_date', 'last_reminder_at', 'automated_reminders_sent']);
        });
    }
};
