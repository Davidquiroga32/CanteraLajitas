<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mensajes de validación
    |--------------------------------------------------------------------------
    |
    | Mensajes cortos y claros. Las reglas que no estén aquí usan el
    | idioma de respaldo (inglés).
    |
    */

    'accepted' => 'Debes aceptar :attribute.',
    'after' => ':Attribute debe ser una fecha posterior a :date.',
    'after_or_equal' => ':Attribute debe ser una fecha igual o posterior a :date.',
    'alpha_dash' => ':Attribute solo puede tener letras, números, guiones y guiones bajos.',
    'array' => ':Attribute debe ser una lista.',
    'before' => ':Attribute debe ser una fecha anterior a :date.',
    'before_or_equal' => ':Attribute debe ser una fecha igual o anterior a :date.',
    'between' => [
        'array' => ':Attribute debe tener entre :min y :max elementos.',
        'file' => ':Attribute debe pesar entre :min y :max kilobytes.',
        'numeric' => ':Attribute debe estar entre :min y :max.',
        'string' => ':Attribute debe tener entre :min y :max caracteres.',
    ],
    'boolean' => ':Attribute debe ser sí o no.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'current_password' => 'La contraseña no es correcta.',
    'date' => ':Attribute no es una fecha válida.',
    'date_format' => ':Attribute no tiene el formato :format.',
    'decimal' => ':Attribute debe tener :decimal decimales.',
    'different' => ':Attribute y :other deben ser diferentes.',
    'digits' => ':Attribute debe tener :digits dígitos.',
    'digits_between' => ':Attribute debe tener entre :min y :max dígitos.',
    'distinct' => ':Attribute está repetido.',
    'email' => ':Attribute debe ser un correo válido.',
    'exists' => ':Attribute seleccionado no es válido.',
    'filled' => 'Falta :attribute.',
    'gt' => [
        'numeric' => ':Attribute debe ser mayor que :value.',
    ],
    'gte' => [
        'numeric' => ':Attribute debe ser mayor o igual que :value.',
    ],
    'image' => ':Attribute debe ser una imagen.',
    'in' => ':Attribute seleccionado no es válido.',
    'integer' => ':Attribute debe ser un número entero.',
    'lowercase' => ':Attribute debe estar en minúsculas.',
    'lt' => [
        'numeric' => ':Attribute debe ser menor que :value.',
    ],
    'lte' => [
        'numeric' => ':Attribute debe ser menor o igual que :value.',
    ],
    'max' => [
        'array' => ':Attribute no puede tener más de :max elementos.',
        'file' => ':Attribute no puede pesar más de :max kilobytes.',
        'numeric' => ':Attribute no puede ser mayor que :max.',
        'string' => ':Attribute no puede tener más de :max caracteres.',
    ],
    'min' => [
        'array' => ':Attribute debe tener al menos :min elementos.',
        'file' => ':Attribute debe pesar al menos :min kilobytes.',
        'numeric' => ':Attribute debe ser al menos :min.',
        'string' => ':Attribute debe tener al menos :min caracteres.',
    ],
    'numeric' => ':Attribute debe ser un número.',
    'password' => [
        'letters' => ':Attribute debe tener al menos una letra.',
        'mixed' => ':Attribute debe tener mayúsculas y minúsculas.',
        'numbers' => ':Attribute debe tener al menos un número.',
        'symbols' => ':Attribute debe tener al menos un símbolo.',
        'uncompromised' => 'Esta :attribute aparece en filtraciones de datos. Elige otra.',
    ],
    'present' => 'Falta :attribute.',
    'regex' => 'El formato de :attribute no es válido.',
    'required' => 'Falta :attribute.',
    'required_if' => 'Falta :attribute.',
    'required_with' => 'Falta :attribute.',
    'required_without' => 'Falta :attribute.',
    'same' => ':Attribute y :other deben coincidir.',
    'size' => [
        'numeric' => ':Attribute debe ser :size.',
        'string' => ':Attribute debe tener :size caracteres.',
    ],
    'string' => ':Attribute debe ser texto.',
    'unique' => 'Ya existe un registro con ese :attribute.',
    'uppercase' => ':Attribute debe estar en mayúsculas.',

    /*
    |--------------------------------------------------------------------------
    | Nombres de los campos
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'el nombre',
        'email' => 'el correo',
        'password' => 'la contraseña',
        'current_password' => 'la contraseña actual',
        'cedula' => 'la cédula',
        'cargo' => 'el cargo',
        'role' => 'el rol',
        'nombre' => 'el nombre',
        'documento' => 'el documento',
        'tipo_documento' => 'el tipo de documento',
        'ciudad' => 'la ciudad',
        'conductor' => 'el conductor',
        'placa' => 'la placa',
        'maquina' => 'la máquina que carga',
        'destino' => 'el destino del material',
        'forma_pago' => 'la forma de pago',
        'precio_m3' => 'el precio por m³',
        'stock_m3' => 'el stock',
        'valor' => 'el valor',
    ],

];
