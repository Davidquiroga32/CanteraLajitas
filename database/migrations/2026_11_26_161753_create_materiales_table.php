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
        Schema::create('materiales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->unsignedInteger('precio_m3');
            $table->decimal('stock_m3', 10, 2)->default(0);
            $table->decimal('stock_minimo_m3', 10, 2)->default(0);
            $table->string('color', 7)->default('#955515');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materiales');
    }
};
