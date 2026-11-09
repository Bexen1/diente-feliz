<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmacionRequest;
use App\Http\Requests\PagoRequest;
use App\Models\Pago;
use App\Models\Presupuesto;
use App\Services\PresupuestoService;

class PagoController extends Controller
{
    public function __construct(private PresupuestoService $servicio) {}

    public function store(PagoRequest $request, Presupuesto $presupuesto)
    {
        $monto = (float) $request->validated('monto');
        $pago = $this->servicio->registrarPago($presupuesto, $monto, $request->validated('fecha'), $request->validated('metodo'));

        return redirect()->route('presupuestos.show', $presupuesto)
            ->with('exito', 'Pago de $'.number_format($monto, 0, ',', '.').' registrado.')
            ->with('comprobante', $pago->id);
    }

    public function comprobante(Pago $pago)
    {
        $pago->load(['presupuesto.paciente.convenio', 'presupuesto.doctor.usuario']);

        return view('pagos.comprobante', [
            'pago' => $pago,
            'saldo' => $pago->presupuesto->saldoPendiente(),
        ]);
    }

    public function confirmarAnulacion(Pago $pago)
    {
        $pago->load('presupuesto.paciente');

        return view('confirmar', [
            'titulo' => 'Anular pago',
            'mensaje' => 'El pago no se borra: queda marcado como anulado y su monto vuelve al saldo pendiente.',
            'detalles' => [
                'Paciente' => $pago->presupuesto->paciente->nombre,
                'Presupuesto' => 'N.° '.$pago->presupuesto_id,
                'Fecha del pago' => $pago->fecha->format('d/m/Y'),
                'Monto' => '$'.number_format((float) $pago->monto, 0, ',', '.'),
            ],
            'accion' => route('pagos.anular', $pago),
            'metodo' => 'PATCH',
            'boton' => 'Sí, anular el pago',
            'volver' => route('presupuestos.show', $pago->presupuesto_id),
        ]);
    }

    public function anular(ConfirmacionRequest $request, Pago $pago)
    {
        $this->servicio->anularPago($pago, $request->validated('motivo'));

        return redirect()->route('presupuestos.show', $pago->presupuesto_id)->with('exito', 'Pago anulado.');
    }
}
