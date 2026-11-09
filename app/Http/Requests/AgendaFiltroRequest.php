<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgendaFiltroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctores,id'],
            'box_id' => ['nullable', 'integer', 'exists:box,id'],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursales,id'],
            'vista' => ['nullable', 'in:dia,semana'],
        ];
    }
}
