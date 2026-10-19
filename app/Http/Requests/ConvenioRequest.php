<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConvenioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institucion' => ['required', 'string', 'max:150'],
            'porcentaje_descuento' => ['required', 'numeric', 'between:0,100'],
        ];
    }
}
