<?php

namespace App\Http\Requests;

use App\Services\ReporteService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['nullable', Rule::in(array_keys(ReporteService::TIPOS))],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'formato' => ['nullable', Rule::in(['pantalla', 'imprimir', 'csv', 'pdf'])],
        ];
    }
}
