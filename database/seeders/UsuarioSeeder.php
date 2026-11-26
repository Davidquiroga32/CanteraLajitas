<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Usuarios de demostración (solo desarrollo). Contraseña de todos: "password".
 */
class UsuarioSeeder extends Seeder
{
    /**
     * @var list<array{name: string, cedula: string, cargo: string, role: Rol, email: string}>
     */
    private const USUARIOS = [
        ['name' => 'Rosa Elena Quintero', 'cedula' => '40.387.122', 'cargo' => 'Gerente administrativa', 'role' => Rol::Admin, 'email' => 'admin@canteralajitas.test'],
        ['name' => 'Sebastián Cárdenas', 'cedula' => '1.121.845.330', 'cargo' => 'Vendedor de despacho', 'role' => Rol::Vendedor, 'email' => 'sebastian@canteralajitas.test'],
        ['name' => 'Yuliana Moreno', 'cedula' => '1.122.903.417', 'cargo' => 'Vendedora de mostrador', 'role' => Rol::Vendedor, 'email' => 'yuliana@canteralajitas.test'],
        ['name' => 'Gilberto Pineda', 'cedula' => '86.044.519', 'cargo' => 'Báscula y despachos', 'role' => Rol::Vendedor, 'email' => 'gilberto@canteralajitas.test'],
    ];

    public function run(): void
    {
        $password = Hash::make('password');

        foreach (self::USUARIOS as $usuario) {
            User::query()->firstOrCreate(
                ['email' => $usuario['email']],
                [...$usuario, 'password' => $password, 'activo' => true, 'email_verified_at' => now()],
            );
        }
    }
}
