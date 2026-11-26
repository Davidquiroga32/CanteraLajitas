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
        Schema::create('remisiones', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('numero')->unique();
            $table->dateTime('emitida_en');
            $table->foreignId('comprador_id')->constrained('compradores')->restrictOnDelete();
            $table->foreignId('vendedor_id')->constrained('users')->restrictOnDelete();
            $table->string('conductor', 120);
            $table->string('placa', 12);
            $table->string('maquina', 80);
            $table->string('destino', 150);
            $table->string('forma_pago', 20);
            $table->string('observaciones', 255)->nullable();
            $table->unsignedBigInteger('total');
            $table->string('estado', 15)->default('emitida');
            $table->dateTime('anulada_en')->nullable();
            $table->foreignId('anulada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motivo_anulacion', 255)->nullable();
            $table->timestamps();

            $table->index('emitida_en');
            $table->index(['vendedor_id', 'emitida_en']);
            $table->index(['comprador_id', 'emitida_en']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remisiones');
    }
};
