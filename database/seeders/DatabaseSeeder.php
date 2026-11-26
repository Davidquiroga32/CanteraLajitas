<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Configuración y catálogo de materiales se cargan siempre. Usuarios,
     * compradores y ventas de demostración solo fuera de producción.
     */
    public function run(): void
    {
        $this->call([
            ConfiguracionSeeder::class,
            MaterialSeeder::class,
        ]);

        if (app()->isProduction()) {
            return;
        }

        $this->call([
            UsuarioSeeder::class,
            CompradorSeeder::class,
            DemoVentasSeeder::class,
        ]);
    }
}
