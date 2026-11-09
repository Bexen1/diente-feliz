<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BloqueoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['nullable', 'integer', 'exists:doctores,id'],
            'box_id' => ['nullable', 'integer', 'exists:box,id'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'motivo' => ['required', 'string', 'max:150'],
        ];
    }

    public function attributes(): array
    {
        return ['fecha_inicio' => 'fecha de inicio', 'fecha_fin' => 'fecha de término', 'motivo' => 'motivo'];
    }
}
