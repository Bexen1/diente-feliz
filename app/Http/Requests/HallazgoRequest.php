<?php

namespace App\Http\Requests;

use App\Services\OdontogramaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HallazgoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pieza' => ['required', 'integer', Rule::in(OdontogramaService::piezas())],
            'estado' => ['required', Rule::in(array_keys(OdontogramaService::ESTADOS))],
            'caras' => ['nullable', 'array'],
            'caras.*' => [Rule::in(OdontogramaService::CARAS)],
        ];
    }
}
