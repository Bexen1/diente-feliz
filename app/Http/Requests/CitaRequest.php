<?php

namespace App\Http\Requests;

use App\Models\Cita;
use App\Services\CitaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reglas = [
            'doctor_id' => ['required', 'integer', 'exists:doctores,id'],
            'box_id' => ['required', 'integer', 'exists:box,id'],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora' => ['required', 'date_format:H:i', 'after_or_equal:'.CitaService::HORA_APERTURA, 'before:'.CitaService::HORA_CIERRE],
            'duracion' => ['required', 'integer', Rule::in(Cita::DURACIONES)],
        ];

        if ($this->isMethod('post')) {
            $reglas['paciente_id'] = ['required', 'integer', Rule::exists('pacientes', 'id')->where('activo', true)];
        }

        return $reglas;
    }

    public function messages(): array
    {
        return ['duracion.in' => 'La duración debe ser de 30, 45 o 60 minutos.'];
    }
}
