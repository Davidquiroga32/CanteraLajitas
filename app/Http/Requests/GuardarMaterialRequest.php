<?php

namespace App\Http\Requests;

use App\Models\Material;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Crear o editar un material (solo administrador). El stock inicial solo se
 * pide al crear; después se cambia con un ajuste de stock.
 */
class GuardarMaterialRequest extends FormRequest
{
    protected $errorBag = 'material';

    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Material|null $material */
        $material = $this->route('material');

        return [
            'nombre' => ['required', 'string', 'max:100', Rule::unique('materiales', 'nombre')->ignore($material)],
            'precio_m3' => ['required', 'integer', 'min:1', 'max:100000000'],
            'stock_minimo_m3' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'activo' => ['boolean'],
            'stock_m3' => [Rule::excludeIf($material !== null), 'required', 'numeric', 'min:0', 'max:1000000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'activo' => $this->boolean('activo', true),
            'precio_m3' => $this->limpiarNumero($this->input('precio_m3')),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'stock_minimo_m3' => 'el stock mínimo',
            'stock_m3' => 'el stock inicial',
            'color' => 'el color',
        ];
    }

    /**
     * Acepta "85.000" o "$ 85.000" y lo deja en 85000.
     */
    private function limpiarNumero(mixed $valor): mixed
    {
        return is_string($valor) ? preg_replace('/[^\d]/', '', $valor) : $valor;
    }
}
