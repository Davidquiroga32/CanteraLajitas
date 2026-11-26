<?php

namespace App\Http\Requests;

use App\Enums\FormaPago;
use App\Enums\TipoDocumento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Todos los campos de la remisión son obligatorios (no hay teléfono).
 * El vendedor es el usuario autenticado y el precio sale del catálogo.
 */
class EmitirRemisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $existente = Rule::excludeIf(fn (): bool => $this->filled('comprador_id'));

        return [
            'comprador_id' => ['nullable', 'integer', Rule::exists('compradores', 'id')],
            'comprador.nombre' => [$existente, 'required', 'string', 'max:150'],
            'comprador.tipo_documento' => [$existente, 'required', Rule::enum(TipoDocumento::class)],
            'comprador.documento' => [$existente, 'required', 'string', 'max:30', 'regex:/^[0-9][0-9.\-]*$/'],
            'comprador.ciudad' => [$existente, 'required', 'string', 'max:80'],

            'lineas' => ['required', 'array', 'min:1', 'max:10'],
            'lineas.*.material_id' => ['required', 'integer', 'distinct', Rule::exists('materiales', 'id')->where('activo', true)],
            'lineas.*.cantidad_m3' => ['required', 'numeric', 'gt:0', 'max:1000', 'decimal:0,2'],

            'conductor' => ['required', 'string', 'max:120'],
            'placa' => ['required', 'string', 'max:12', 'regex:/^[A-Za-z0-9 \-]+$/'],
            'maquina' => ['required', 'string', 'max:80'],
            'destino' => ['required', 'string', 'max:150'],
            'forma_pago' => ['required', Rule::enum(FormaPago::class)],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $texto = fn (mixed $v): mixed => is_string($v) ? trim(preg_replace('/\s+/', ' ', $v)) : $v;

        $comprador = $this->input('comprador', []);

        $this->merge([
            'comprador_id' => $this->filled('comprador_id') ? $this->input('comprador_id') : null,
            'comprador' => is_array($comprador) ? [
                ...$comprador,
                'nombre' => $texto($comprador['nombre'] ?? null),
                'documento' => is_string($comprador['documento'] ?? null) ? preg_replace('/\s+/', '', $comprador['documento']) : null,
                'ciudad' => $texto($comprador['ciudad'] ?? null),
            ] : [],
            'conductor' => $texto($this->conductor),
            'placa' => is_string($this->placa) ? mb_strtoupper($texto($this->placa)) : $this->placa,
            'maquina' => $texto($this->maquina),
            'destino' => $texto($this->destino),
            'lineas' => collect($this->input('lineas', []))->filter(fn ($l) => is_array($l) && filled($l['material_id'] ?? null))->values()->all(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'comprador.nombre' => 'el nombre del comprador',
            'comprador.tipo_documento' => 'el tipo de documento del comprador',
            'comprador.documento' => 'el NIT o cédula del comprador',
            'comprador.ciudad' => 'la ciudad del comprador',
            'lineas' => 'los materiales',
            'placa' => 'la placa del vehículo',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lineas.required' => 'Agrega al menos un material.',
            'lineas.*.material_id.required' => 'Elige el material de la línea :position.',
            'lineas.*.material_id.distinct' => 'El material de la línea :position está repetido. Suma las cantidades en una sola línea.',
            'lineas.*.material_id.exists' => 'El material de la línea :position no está disponible.',
            'lineas.*.cantidad_m3.required' => 'Falta la cantidad (m³) de la línea :position.',
            'lineas.*.cantidad_m3.gt' => 'La cantidad de la línea :position debe ser mayor que cero.',
            'lineas.*.cantidad_m3.numeric' => 'La cantidad de la línea :position debe ser un número.',
            'lineas.*.cantidad_m3.max' => 'La cantidad de la línea :position es demasiado grande.',
            'lineas.*.cantidad_m3.decimal' => 'La cantidad de la línea :position puede tener máximo 2 decimales.',
            'comprador.documento.regex' => 'El NIT o cédula solo puede tener números, puntos y guion.',
            'placa.regex' => 'La placa solo puede tener letras, números y guion (ej. FBI 713).',
            'destino.required' => 'Falta el destino del material. Si no aplica, escribe N/A.',
        ];
    }
}
