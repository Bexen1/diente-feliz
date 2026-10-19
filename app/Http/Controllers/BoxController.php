<?php

namespace App\Http\Controllers;

use App\Http\Requests\BoxRequest;
use App\Models\Box;
use App\Models\Sucursal;
use App\Services\AuditoriaService;

class BoxController extends Controller
{
    public function index()
    {
        return view('box.index', [
            'boxes' => Box::with('sucursal')->orderBy('sucursal_id')->orderBy('nombre')->get(),
        ]);
    }

    public function create()
    {
        return view('box.form', ['box' => new Box, 'sucursales' => Sucursal::orderBy('nombre')->get()]);
    }

    public function store(BoxRequest $request)
    {
        $box = Box::create($request->validated());
        AuditoriaService::creado($box);

        return redirect()->route('box.index')->with('exito', 'Box creado.');
    }

    public function edit(Box $box)
    {
        return view('box.form', ['box' => $box, 'sucursales' => Sucursal::orderBy('nombre')->get()]);
    }

    public function update(BoxRequest $request, Box $box)
    {
        $original = $box->getOriginal();
        $box->update($request->validated());
        AuditoriaService::actualizado($box, $original);

        return redirect()->route('box.index')->with('exito', 'Box actualizado.');
    }
}
