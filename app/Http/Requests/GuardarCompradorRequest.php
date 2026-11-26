<?php

namespace App\Http\Requests;

use App\Enums\TipoDocumento;
use App\Models\Comprador;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Crear (cualquier usuario) o editar (solo administrador) un comprador.
 */
class GuardarCompradorRequest extends FormRequest
{
    protected $errorBag = 'comprador';

    public function authorize(): bool
    {
        return $this->route('comprador') === null || $this->user()->can('admin');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Comprador|null $comprador */
        $comprador = $this->route('comprador');

        return [
            'nombre' => ['required', 'string', 'max:150'],
            'tipo_documento' => ['required', Rule::enum(TipoDocumento::class)],
            'documento' => ['required', 'string', 'max:30', 'regex:/^[0-9][0-9.\-]*$/', Rule::unique('compradores', 'documento')->ignore($comprador)],
            'ciudad' => ['required', 'string', 'max:80'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => is_string($this->nombre) ? trim(preg_replace('/\s+/', ' ', $this->nombre)) : $this->nombre,
            'documento' => is_string($this->documento) ? preg_replace('/\s+/', '', $this->documento) : $this->documento,
            'ciudad' => is_string($this->ciudad) ? trim($this->ciudad) : $this->ciudad,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'documento.regex' => 'El documento solo puede tener números, puntos y guion (ej. 900.412.118-3).',
            'documento.unique' => 'Ya existe un comprador con ese documento.',
        ];
    }
}
