<?php

namespace App\Http\Requests;

use App\Models\Tratamiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PresupuestoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reglas = [
            'doctor_id' => ['required', 'integer', 'exists:doctores,id'],
            'cantidades' => ['required', 'array'],
            'cantidades.*' => ['nullable', 'integer', 'min:0', 'max:99'],
        ];

        if ($this->isMethod('post')) {
            $reglas['paciente_id'] = ['required', 'integer', Rule::exists('pacientes', 'id')->where('activo', true)];
        }

        return $reglas;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $cantidades = array_filter((array) $this->input('cantidades'), fn ($c) => (int) $c > 0);

                if ($cantidades === []) {
                    $validator->errors()->add('cantidades', 'Agregue al menos un tratamiento con cantidad mayor que cero.');

                    return;
                }

                $validos = Tratamiento::whereIn('id', array_keys($cantidades))->where('activo', true)->count();
                if ($validos !== count($cantidades)) {
                    $validator->errors()->add('cantidades', 'Hay tratamientos que no existen o están inactivos.');
                }
            },
        ];
    }
}
