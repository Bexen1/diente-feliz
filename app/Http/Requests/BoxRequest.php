<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BoxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:50'],
            'sucursal_id' => ['required', 'integer', 'exists:sucursales,id'],
            'activo' => ['required', 'boolean'],
        ];
    }
}
