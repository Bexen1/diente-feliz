<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmacionRequest;
use App\Http\Requests\SucursalRequest;
use App\Models\Sucursal;
use App\Services\AuditoriaService;

class SucursalController extends Controller
{
    public function index()
    {
        return view('sucursales.index', [
            'sucursales' => Sucursal::withCount('boxes')->orderBy('nombre')->get(),
        ]);
    }

    public function create()
    {
        return view('sucursales.form', ['sucursal' => new Sucursal]);
    }

    public function store(SucursalRequest $request)
    {
        $sucursal = Sucursal::create($request->validated());
        AuditoriaService::creado($sucursal);

        return redirect()->route('sucursales.index')->with('exito', 'Sucursal creada.');
    }

    public function edit(Sucursal $sucursal)
    {
        return view('sucursales.form', compact('sucursal'));
    }

    public function update(SucursalRequest $request, Sucursal $sucursal)
    {
        $original = $sucursal->getOriginal();
        $sucursal->update($request->validated());
        AuditoriaService::actualizado($sucursal, $original);

        return redirect()->route('sucursales.index')->with('exito', 'Sucursal actualizada.');
    }

    public function confirmarEliminacion(Sucursal $sucursal)
    {
        return view('confirmar', [
            'titulo' => 'Eliminar sucursal',
            'mensaje' => 'La sucursal se eliminará definitivamente. Solo es posible si no tiene box asociados.',
            'detalles' => ['Nombre' => $sucursal->nombre, 'Dirección' => $sucursal->direccion],
            'accion' => route('sucursales.destroy', $sucursal),
            'metodo' => 'DELETE',
            'boton' => 'Sí, eliminar la sucursal',
            'volver' => route('sucursales.index'),
        ]);
    }

    public function destroy(ConfirmacionRequest $request, Sucursal $sucursal)
    {
        if ($sucursal->boxes()->exists()) {
            return redirect()->route('sucursales.index')->with('error', 'No se puede eliminar: la sucursal tiene box asociados.');
        }

        $antes = $sucursal->getAttributes();
        $sucursal->delete();
        AuditoriaService::registrar('eliminar', 'sucursales', $sucursal->id, $antes, ['motivo' => $request->validated('motivo')]);

        return redirect()->route('sucursales.index')->with('exito', 'Sucursal eliminada.');
    }
}
