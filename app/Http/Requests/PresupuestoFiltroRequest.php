<?php

namespace App\Http\Requests;

use App\Models\Presupuesto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PresupuestoFiltroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['nullable', Rule::in(Presupuesto::ESTADOS)],
            'paciente_id' => ['nullable', 'integer', 'exists:pacientes,id'],
        ];
    }
}
