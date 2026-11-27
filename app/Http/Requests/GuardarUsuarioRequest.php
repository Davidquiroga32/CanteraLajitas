<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * Crear o editar un usuario del equipo (solo administrador). La contraseña
 * solo se pide al crear; después se cambia con "Asignar contraseña".
 */
class GuardarUsuarioRequest extends FormRequest
{
    protected $errorBag = 'usuario';

    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User|null $usuario */
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:120'],
            'cedula' => ['required', 'string', 'regex:/^[1-9][0-9.]{4,14}$/', Rule::unique('users', 'cedula')->ignore($usuario)],
            'cargo' => ['nullable', 'string', 'max:80'],
            'role' => ['required', Rule::enum(Rol::class)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario)],
            'activo' => ['boolean'],
            'password' => [Rule::excludeIf($usuario !== null), 'required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * La cédula se guarda con puntos de miles (40.387.122) sin importar cómo se escriba.
     */
    protected function prepareForValidation(): void
    {
        $cedula = is_string($this->cedula) ? preg_replace('/\D/', '', $this->cedula) : $this->cedula;

        $this->merge([
            'name' => is_string($this->name) ? trim(preg_replace('/\s+/', ' ', $this->name)) : $this->name,
            'cedula' => is_string($cedula) && $cedula !== '' ? number_format((int) $cedula, 0, '', '.') : $cedula,
            'cargo' => is_string($this->cargo) ? trim($this->cargo) : $this->cargo,
            'email' => is_string($this->email) ? mb_strtolower(trim($this->email)) : $this->email,
            'activo' => $this->boolean('activo', true),
        ]);
    }

    /**
     * El administrador no puede quitarse a sí mismo el rol ni desactivarse,
     * así siempre queda al menos un administrador activo.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var User|null $usuario */
                $usuario = $this->route('usuario');

                if ($usuario === null || ! $usuario->is($this->user())) {
                    return;
                }

                if ($this->role !== Rol::Admin->value) {
                    $validator->errors()->add('role', 'No puedes quitarte tu propio rol de administrador.');
                }

                if (! $this->activo) {
                    $validator->errors()->add('activo', 'No puedes desactivar tu propio usuario.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'el nombre',
            'cedula' => 'la cédula',
            'cargo' => 'el cargo',
            'role' => 'el rol',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cedula.regex' => 'La cédula solo puede tener números (ej. 1.121.845.330).',
            'cedula.unique' => 'Ya existe un usuario con esa cédula.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
        ];
    }
}
