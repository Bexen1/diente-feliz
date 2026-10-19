<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TratamientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'costo_base' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'activo' => ['required', 'boolean'],
        ];
    }
}
