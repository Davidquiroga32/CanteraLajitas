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
        Schema::table('users', function (Blueprint $table) {
            $table->string('cedula', 20)->nullable()->unique()->after('name');
            $table->string('cargo', 80)->nullable()->after('cedula');
            $table->string('role', 20)->default('vendedor')->after('cargo');
            $table->boolean('activo')->default(true)->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['cedula']);
            $table->dropColumn(['cedula', 'cargo', 'role', 'activo']);
        });
    }
};
