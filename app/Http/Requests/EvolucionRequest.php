<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EvolucionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reglas = [
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'diagnostico' => ['required', 'string', 'max:2000'],
            'tratamiento_realizado' => ['nullable', 'string', 'max:2000'],
        ];

        if (! $this->user()->doctor) {
            $reglas['doctor_id'] = ['required', 'integer', Rule::exists('doctores', 'id')];
        }

        return $reglas;
    }

    public function attributes(): array
    {
        return ['tratamiento_realizado' => 'tratamiento realizado'];
    }
}
