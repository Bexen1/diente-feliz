<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstadoCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('modificar', $this->route('cita'));
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in(['confirmada', 'atendida', 'cancelada', 'no_asistio'])],
            'motivo' => ['required_if:estado,cancelada', 'nullable', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ['motivo.required_if' => 'Indique el motivo de la cancelación.'];
    }
}
