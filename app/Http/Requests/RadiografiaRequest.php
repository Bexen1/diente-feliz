<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RadiografiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png', 'max:5120'],
            'fecha_radiografia' => ['required', 'date', 'before_or_equal:today'],
            'diagnostico_radiografia' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['fecha_radiografia' => 'fecha', 'diagnostico_radiografia' => 'diagnóstico'];
    }
}
