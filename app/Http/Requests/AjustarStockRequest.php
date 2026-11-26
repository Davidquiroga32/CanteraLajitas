<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Entrada de material al patio (suma) o corrección por conteo físico (fija el valor).
 */
class AjustarStockRequest extends FormRequest
{
    protected $errorBag = 'stock';

    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:entrada,conteo'],
            'cantidad_m3' => ['required', 'numeric', 'min:0', 'max:1000000', 'decimal:0,2'],
            'nota' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tipo' => 'el tipo de ajuste',
            'cantidad_m3' => 'la cantidad',
            'nota' => 'la nota',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cantidad_m3.decimal' => 'La cantidad puede tener máximo 2 decimales.',
        ];
    }
}
