<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cada movimiento afecta una cuenta del comprador ("credito" = lo que debe,
     * "anticipo" = saldo a favor) con un valor con signo. El saldo de cada cuenta
     * es la suma de sus movimientos.
     */
    public function up(): void
    {
        Schema::create('movimientos_cartera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprador_id')->constrained('compradores')->restrictOnDelete();
            $table->string('cuenta', 15);
            $table->string('tipo', 25);
            $table->bigInteger('valor');
            $table->foreignId('remision_id')->nullable()->constrained('remisiones')->restrictOnDelete();
            $table->string('medio_pago', 20)->nullable();
            $table->string('nota', 255)->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('registrado_en');
            $table->timestamps();

            $table->index(['comprador_id', 'cuenta']);
            $table->index('registrado_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_cartera');
    }
};
