<?php

namespace App\Http\Requests;

use App\Models\Paciente;
use App\Rules\Rut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $paciente = $this->route('paciente');

        $reglas = [
            'rut' => ['required', 'string', new Rut, Rule::unique('pacientes', 'rut')->ignore($paciente)],
            'nombre' => ['required', 'string', 'max:150'],
            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'contacto_emergencia_nombre' => ['nullable', 'string', 'max:150'],
            'contacto_emergencia_telefono' => ['nullable', 'string', 'max:20'],
            'convenio_id' => ['nullable', 'integer', 'exists:convenios,id'],
        ];

        if ($this->user()->tieneRol('Dentista')) {
            foreach (Paciente::CAMPOS_SALUD as $campo) {
                $reglas[$campo] = ['nullable', 'string', 'max:2000'];
            }
        }

        if ($this->user()->esAdministrador()) {
            $reglas['activo'] = ['sometimes', 'boolean'];
        }

        return $reglas;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['rut' => Rut::normalizar($this->input('rut'))]);
    }

    public function attributes(): array
    {
        return [
            'rut' => 'RUT',
            'contacto_emergencia_nombre' => 'nombre del contacto de emergencia',
            'contacto_emergencia_telefono' => 'teléfono del contacto de emergencia',
            'patologias' => 'patologías base',
        ];
    }
}
