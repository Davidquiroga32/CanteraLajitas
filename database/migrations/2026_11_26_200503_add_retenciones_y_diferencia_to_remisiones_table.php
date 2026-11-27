<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Subtotal y retenciones (el total pasa a ser el neto a pagar) y cómo se pagó
     * la parte que el anticipo no alcanzó a cubrir.
     */
    public function up(): void
    {
        Schema::table('remisiones', function (Blueprint $table) {
            $table->unsignedBigInteger('subtotal')->default(0)->after('observaciones');
            $table->unsignedBigInteger('retefuente')->default(0)->after('subtotal');
            $table->unsignedBigInteger('reteica')->default(0)->after('retefuente');
            $table->string('diferencia_forma_pago', 20)->nullable()->after('total');
            $table->unsignedBigInteger('diferencia_valor')->default(0)->after('diferencia_forma_pago');
        });

        DB::table('remisiones')->update(['subtotal' => DB::raw('total')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remisiones', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'retefuente', 'reteica', 'diferencia_forma_pago', 'diferencia_valor']);
        });
    }
};
