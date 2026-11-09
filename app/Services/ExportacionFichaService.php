<?php

namespace App\Services;

use App\Models\Paciente;

class ExportacionFichaService
{
    public function datos(Paciente $paciente): array
    {
        $paciente->load([
            'convenio',
            'ficha.evoluciones' => fn ($q) => $q->with('doctor.usuario')->orderBy('fecha')->orderBy('id'),
            'ficha.radiografias' => fn ($q) => $q->orderBy('fecha'),
            'citas' => fn ($q) => $q->with(['doctor.usuario', 'box.sucursal'])->orderBy('hora_inicio'),
            'presupuestos' => fn ($q) => $q->with(['doctor.usuario', 'detalles.tratamiento', 'pagos'])->orderBy('created_at'),
        ]);

        $ficha = $paciente->ficha;

        return [
            'generado' => now()->format('Y-m-d H:i:s'),
            'clinica' => 'Clínica Dental Diente Feliz',
            'paciente' => [
                'rut' => $paciente->rut,
                'nombre' => $paciente->nombre,
                'fecha_nacimiento' => $paciente->fecha_nacimiento?->format('Y-m-d'),
                'telefono' => $paciente->telefono,
                'email' => $paciente->email,
                'direccion' => $paciente->direccion,
                'contacto_emergencia' => [
                    'nombre' => $paciente->contacto_emergencia_nombre,
                    'telefono' => $paciente->contacto_emergencia_telefono,
                ],
                'convenio' => $paciente->convenio?->institucion,
                'activo' => $paciente->activo,
            ],
            'antecedentes_salud' => [
                'alergias' => $paciente->alergias,
                'patologias' => $paciente->patologias,
                'medicamentos' => $paciente->medicamentos,
            ],
            'ficha' => [
                'observaciones_generales' => $ficha?->evolucion_general,
                'odontograma' => $ficha?->estado_odontograma_json ?: (object) [],
            ],
            'atenciones' => ($ficha?->evoluciones ?? collect())->map(fn ($e) => [
                'fecha' => $e->fecha->format('Y-m-d'),
                'doctor' => $e->doctor->nombre,
                'diagnostico' => $e->diagnostico,
                'tratamiento_realizado' => $e->tratamiento_realizado,
            ])->values()->all(),
            'radiografias' => ($ficha?->radiografias ?? collect())->map(fn ($r) => [
                'fecha' => $r->fecha->format('Y-m-d'),
                'diagnostico' => $r->diagnostico,
                'archivo' => basename($r->ruta_archivo),
            ])->values()->all(),
            'citas' => $paciente->citas->map(fn ($c) => [
                'inicio' => $c->hora_inicio->format('Y-m-d H:i'),
                'fin' => $c->hora_fin->format('Y-m-d H:i'),
                'doctor' => $c->doctor->nombre,
                'box' => $c->box->nombre,
                'sucursal' => $c->box->sucursal->nombre,
                'estado' => $c->estado,
            ])->values()->all(),
            'presupuestos' => $paciente->presupuestos->map(fn ($p) => [
                'numero' => $p->id,
                'fecha' => $p->created_at->format('Y-m-d'),
                'doctor' => $p->doctor->nombre,
                'estado' => $p->estado,
                'total' => (float) $p->total_calculado,
                'tratamientos' => $p->detalles->map(fn ($d) => [
                    'tratamiento' => $d->tratamiento->nombre,
                    'cantidad' => $d->cantidad,
                    'subtotal' => (float) $d->subtotal,
                ])->values()->all(),
                'pagos' => $p->pagos->map(fn ($pago) => [
                    'fecha' => $pago->fecha->format('Y-m-d'),
                    'monto' => (float) $pago->monto,
                    'estado' => $pago->estado,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
