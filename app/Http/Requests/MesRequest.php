<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mes' => ['nullable', 'date_format:Y-m'],
        ];
    }
}
