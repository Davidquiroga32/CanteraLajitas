<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retenciones que el comprador descuenta al pagar (Retención en la fuente y ReteICA).
     */
    public function up(): void
    {
        Schema::table('compradores', function (Blueprint $table) {
            $table->boolean('retefuente')->default(false)->after('ciudad');
            $table->boolean('reteica')->default(false)->after('retefuente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compradores', function (Blueprint $table) {
            $table->dropColumn(['retefuente', 'reteica']);
        });
    }
};
