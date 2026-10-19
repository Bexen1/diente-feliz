<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Rut implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^(\d{7,8})-([\dK])$/', $value, $partes)) {
            $fail('El RUT debe tener el formato 12345678-5.');

            return;
        }

        if (self::digitoVerificador($partes[1]) !== $partes[2]) {
            $fail('El RUT no es válido (dígito verificador incorrecto).');
        }
    }

    public static function digitoVerificador(string $numero): string
    {
        $suma = 0;
        $factor = 2;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $suma += (int) $numero[$i] * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }

        $resto = 11 - ($suma % 11);

        return match ($resto) {
            11 => '0',
            10 => 'K',
            default => (string) $resto,
        };
    }

    public static function normalizar(?string $rut): string
    {
        return strtoupper(str_replace(['.', ' '], '', (string) $rut));
    }
}
