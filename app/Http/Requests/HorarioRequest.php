<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class HorarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dia_semana' => ['required', 'integer', 'between:1,7'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $cruce = $this->route('doctor')->horarios()
                    ->where('dia_semana', $this->input('dia_semana'))
                    ->where('hora_inicio', '<', $this->input('hora_fin'))
                    ->where('hora_fin', '>', $this->input('hora_inicio'))
                    ->exists();

                if ($cruce) {
                    $validator->errors()->add('hora_inicio', 'El bloque se cruza con otro bloque del mismo día.');
                }
            },
        ];
    }
}
