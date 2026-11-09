<?php

namespace App\Http\Requests;

use App\Services\OdontogramaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FichaVerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pieza' => ['nullable', 'integer', Rule::in(OdontogramaService::piezas())],
        ];
    }
}
