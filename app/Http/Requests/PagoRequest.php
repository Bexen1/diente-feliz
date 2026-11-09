<?php

namespace App\Http\Requests;

use App\Models\Pago;
use Illuminate\Foundation\Http\FormRequest;

class PagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto' => ['required', 'integer', 'min:1', 'max:99999999'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'metodo' => ['required', 'in:'.implode(',', array_keys(Pago::METODOS))],
        ];
    }
}
