<?php

namespace App\Http\Requests;

use App\Models\Cita;
use App\Models\Paciente;
use App\Rules\Rut;
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
            $reglas['rut'] = ['required', 'string', new Rut, Rule::exists('pacientes', 'rut')->where('activo', true)];
        }

        return $reglas;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('rut')) {
            $this->merge(['rut' => Rut::normalizar($this->input('rut'))]);
        }
    }

    public function datos(): array
    {
        $datos = $this->safe()->except('rut');

        if ($this->isMethod('post')) {
            $datos['paciente_id'] = Paciente::where('rut', $this->validated('rut'))->value('id');
        }

        return $datos;
    }

    public function messages(): array
    {
        return [
            'duracion.in' => 'La duración debe ser de 30, 45 o 60 minutos.',
            'rut.exists' => 'No hay un paciente activo con ese RUT.',
        ];
    }

    public function attributes(): array
    {
        return ['rut' => 'RUT del paciente'];
    }
}
