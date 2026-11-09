<?php

namespace App\Http\Requests;

use App\Models\Horario;
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
            'dias' => ['required', 'array', 'min:1'],
            'dias.*' => ['integer', 'distinct', 'between:1,7'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
        ];
    }

    public function messages(): array
    {
        return ['dias.required' => 'Seleccione al menos un día.'];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $conCruce = $this->route('doctor')->horarios()
                    ->whereIn('dia_semana', $this->input('dias'))
                    ->where('hora_inicio', '<', $this->input('hora_fin'))
                    ->where('hora_fin', '>', $this->input('hora_inicio'))
                    ->pluck('dia_semana')
                    ->unique()
                    ->map(fn ($dia) => Horario::DIAS[$dia])
                    ->implode(', ');

                if ($conCruce !== '') {
                    $validator->errors()->add('hora_inicio', "El bloque se cruza con otro bloque del {$conCruce}.");
                }
            },
        ];
    }
}
