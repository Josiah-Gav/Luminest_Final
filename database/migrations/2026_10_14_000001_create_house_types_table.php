<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The house model catalog (legacy: hard-coded in three places).
     */
    public function up(): void
    {
        Schema::create('house_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('category');
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('badge')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedTinyInteger('bedrooms');
            $table->unsignedTinyInteger('bathrooms');
            $table->unsignedTinyInteger('carports');
            $table->unsignedInteger('price'); // whole pesos (R8)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_types');
    }
};
