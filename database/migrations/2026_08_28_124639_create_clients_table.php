<?php

declare(strict_types=1);

use App\Enums\ClientType;
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
        Schema::create('clients', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('clientNumber')->unique();
            $table->enum('type', ClientType::cases())->default(ClientType::CLIENT_INDIVIDUAL->value);
            $table->string('companyName')->nullable();
            $table->string('lastName');
            $table->string('firstName');
            $table->string('address_street');
            $table->string('address_city');
            $table->string('address_postal_code');
            $table->string('address_country');
            $table->string('phoneDay')->nullable();
            $table->string('cellPhone')->nullable();
            $table->string('email')->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
