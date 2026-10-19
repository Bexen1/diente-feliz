<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuditoriaFiltroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tabla' => ['nullable', 'string', 'max:50'],
            'accion' => ['nullable', 'string', 'max:50'],
            'usuario_id' => ['nullable', 'integer', 'exists:usuarios,id'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
