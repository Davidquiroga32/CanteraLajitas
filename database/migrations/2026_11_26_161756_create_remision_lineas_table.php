<?php

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
        Schema::create('remision_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remision_id')->constrained('remisiones')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materiales')->restrictOnDelete();
            $table->decimal('cantidad_m3', 8, 2);
            $table->unsignedInteger('precio_m3');
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remision_lineas');
    }
};
