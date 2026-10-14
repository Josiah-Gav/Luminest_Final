<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per physical unit.
     */
    public function up(): void
    {
        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_type_id')->constrained();
            $table->unsignedSmallInteger('block');
            $table->unsignedSmallInteger('lot');
            $table->string('status', 20)->default('available')->index(); // App\Enums\HouseStatus
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete(); // set on sale only (R5)
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();

            $table->unique(['block', 'lot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('houses');
    }
};
