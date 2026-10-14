<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per simulated payment; each one has a PDF receipt (R9).
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->restrictOnDelete();
            $table->string('receipt_number', 20)->nullable()->unique(); // set right after insert, see Payment::booted()
            $table->string('type', 20); // App\Enums\PaymentType
            $table->unsignedInteger('amount');
            $table->unsignedInteger('balance_after'); // snapshot for the receipt only
            $table->string('method', 20)->default('simulated');
            $table->timestamp('paid_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
