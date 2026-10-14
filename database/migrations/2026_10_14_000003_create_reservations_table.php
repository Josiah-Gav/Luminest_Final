<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Plan §1.2. There is no balance column: balance = total_price − sum(payments.amount).
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // keep payment history (§5.7)
            $table->foreignId('house_id')->constrained();
            $table->string('status', 20)->default('reserved')->index(); // App\Enums\ReservationStatus
            $table->unsignedInteger('total_price'); // price snapshot at reservation time (R3)
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
