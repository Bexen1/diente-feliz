<?php

namespace App\Services;

use App\Models\Paciente;
use App\Models\Pago;
use App\Models\Presupuesto;
use App\Models\Tratamiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PresupuestoService
{
    public function calcular(Paciente $paciente, array $cantidades): array
    {
        $cantidades = array_filter($cantidades, fn ($cantidad) => (int) $cantidad > 0);
        $tratamientos = Tratamiento::whereIn('id', array_keys($cantidades))->get()->keyBy('id');

        $lineas = [];
        foreach ($cantidades as $tratamientoId => $cantidad) {
            $tratamiento = $tratamientos[$tratamientoId];
            $lineas[] = [
                'tratamiento_id' => $tratamiento->id,
                'cantidad' => (int) $cantidad,
                'subtotal' => round((float) $tratamiento->costo_base * (int) $cantidad, 2),
            ];
        }

        $subtotal = array_sum(array_column($lineas, 'subtotal'));
        $porcentaje = (float) ($paciente->convenio?->porcentaje_descuento ?? 0);
        $descuento = round($subtotal * $porcentaje / 100);

        return [
            'lineas' => $lineas,
            'subtotal' => $subtotal,
            'porcentaje' => $porcentaje,
            'descuento' => $descuento,
            'total' => $subtotal - $descuento,
        ];
    }

    public function crear(array $datos): Presupuesto
    {
        return DB::transaction(function () use ($datos) {
            $paciente = Paciente::with('convenio')->findOrFail($datos['paciente_id']);
            $calculo = $this->calcular($paciente, $datos['cantidades']);

            $presupuesto = Presupuesto::create([
                'paciente_id' => $paciente->id,
                'doctor_id' => $datos['doctor_id'],
                'estado' => 'borrador',
                'total_calculado' => $calculo['total'],
            ]);
            $presupuesto->detalles()->createMany($calculo['lineas']);

            AuditoriaService::registrar('crear', 'presupuestos', $presupuesto->id, null, [
                'total_calculado' => $calculo['total'], 'lineas' => $calculo['lineas'],
            ]);

            return $presupuesto;
        });
    }

    public function actualizar(Presupuesto $presupuesto, array $datos): void
    {
        $this->exigirEstado($presupuesto, 'borrador', 'Solo se puede editar un presupuesto en borrador.');

        DB::transaction(function () use ($presupuesto, $datos) {
            $antes = ['doctor_id' => $presupuesto->doctor_id, 'total_calculado' => $presupuesto->total_calculado];
            $calculo = $this->calcular($presupuesto->paciente()->with('convenio')->first(), $datos['cantidades']);

            $presupuesto->detalles()->delete();
            $presupuesto->detalles()->createMany($calculo['lineas']);
            $presupuesto->update(['doctor_id' => $datos['doctor_id'], 'total_calculado' => $calculo['total']]);

            AuditoriaService::registrar('actualizar', 'presupuestos', $presupuesto->id, $antes, [
                'doctor_id' => $presupuesto->doctor_id, 'total_calculado' => $calculo['total'],
            ]);
        });
    }

    public function aceptar(Presupuesto $presupuesto): void
    {
        $this->exigirEstado($presupuesto, 'borrador', 'Solo se puede aceptar un presupuesto en borrador.');

        $presupuesto->update(['estado' => 'aceptado']);
        AuditoriaService::registrar('aceptar', 'presupuestos', $presupuesto->id, ['estado' => 'borrador'], ['estado' => 'aceptado']);
    }

    public function anular(Presupuesto $presupuesto, string $motivo): void
    {
        if (! in_array($presupuesto->estado, ['borrador', 'aceptado'], true)) {
            throw ValidationException::withMessages(['estado' => 'Este presupuesto ya no se puede anular.']);
        }

        if ($presupuesto->pagos()->where('estado', 'vigente')->exists()) {
            throw ValidationException::withMessages(['estado' => 'No se puede anular: tiene pagos vigentes. Anule primero los pagos.']);
        }

        $antes = ['estado' => $presupuesto->estado];
        $presupuesto->update(['estado' => 'anulado']);
        AuditoriaService::registrar('anular', 'presupuestos', $presupuesto->id, $antes, ['estado' => 'anulado', 'motivo' => $motivo]);
    }

    public function registrarPago(Presupuesto $presupuesto, float $monto, string $fecha, string $metodo = 'efectivo'): Pago
    {
        return DB::transaction(function () use ($presupuesto, $monto, $fecha, $metodo) {
            $presupuesto = Presupuesto::lockForUpdate()->findOrFail($presupuesto->id);

            if ($presupuesto->estado !== 'aceptado') {
                throw ValidationException::withMessages(['monto' => 'Solo se registran pagos en presupuestos aceptados con saldo pendiente.']);
            }

            $saldo = $presupuesto->saldoPendiente();
            if ($monto > $saldo) {
                throw ValidationException::withMessages([
                    'monto' => 'El monto supera el saldo pendiente de $'.number_format($saldo, 0, ',', '.').'.',
                ]);
            }

            $pago = $presupuesto->pagos()->create(['monto' => $monto, 'fecha' => $fecha, 'estado' => 'vigente', 'metodo' => $metodo]);
            AuditoriaService::registrar('crear', 'pagos', $pago->id, null, [
                'presupuesto_id' => $presupuesto->id, 'monto' => $monto, 'metodo' => $metodo, 'saldo_restante' => $saldo - $monto,
            ]);

            if ($saldo - $monto <= 0) {
                $presupuesto->update(['estado' => 'pagado']);
                AuditoriaService::registrar('cambiar_estado', 'presupuestos', $presupuesto->id, ['estado' => 'aceptado'], ['estado' => 'pagado']);
            }

            return $pago;
        });
    }

    public function anularPago(Pago $pago, string $motivo): void
    {
        if ($pago->estado === 'anulado') {
            throw ValidationException::withMessages(['motivo' => 'El pago ya está anulado.']);
        }

        DB::transaction(function () use ($pago, $motivo) {
            $presupuesto = Presupuesto::lockForUpdate()->findOrFail($pago->presupuesto_id);

            $pago->update(['estado' => 'anulado']);
            AuditoriaService::registrar('anular', 'pagos', $pago->id,
                ['estado' => 'vigente', 'monto' => $pago->monto], ['estado' => 'anulado', 'motivo' => $motivo]);

            if ($presupuesto->estado === 'pagado') {
                $presupuesto->update(['estado' => 'aceptado']);
                AuditoriaService::registrar('cambiar_estado', 'presupuestos', $presupuesto->id, ['estado' => 'pagado'], ['estado' => 'aceptado']);
            }
        });
    }

    private function exigirEstado(Presupuesto $presupuesto, string $estado, string $mensaje): void
    {
        if ($presupuesto->estado !== $estado) {
            throw ValidationException::withMessages(['estado' => $mensaje]);
        }
    }
}
