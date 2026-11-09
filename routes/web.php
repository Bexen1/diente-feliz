<?php

use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BloqueoController;
use App\Http\Controllers\BoxController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\ConvenioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\ExportacionFichaController;
use App\Http\Controllers\FichaController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PlanTratamientoController;
use App\Http\Controllers\PresupuestoController;
use App\Http\Controllers\RadiografiaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\TratamientoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['auth', 'rol'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', InicioController::class)->name('inicio');

    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::put('/perfil/tema', [PerfilController::class, 'tema'])->name('perfil.tema');

    Route::get('/agenda', [CitaController::class, 'index'])->name('citas.index');
    Route::post('/citas', [CitaController::class, 'store'])->name('citas.store');
    Route::get('/citas/{cita}/reagendar', [CitaController::class, 'edit'])->name('citas.edit');
    Route::put('/citas/{cita}', [CitaController::class, 'update'])->name('citas.update');
    Route::patch('/citas/{cita}/estado', [CitaController::class, 'cambiarEstado'])->name('citas.estado');
    Route::get('/citas/{cita}/cancelar', [CitaController::class, 'confirmarCancelacion'])->name('citas.cancelar');

    Route::resource('pacientes', PacienteController::class)->except('destroy');

    Route::middleware('rol:Dentista')->group(function () {
        Route::get('/pacientes/{paciente}/ficha', [FichaController::class, 'show'])->name('fichas.show');
        Route::put('/pacientes/{paciente}/ficha', [FichaController::class, 'update'])->name('fichas.update');
        Route::put('/pacientes/{paciente}/ficha/odontograma', [FichaController::class, 'guardarHallazgo'])->name('fichas.hallazgo');
        Route::post('/pacientes/{paciente}/ficha/evoluciones', [FichaController::class, 'agregarEvolucion'])->name('fichas.evoluciones');
        Route::post('/pacientes/{paciente}/ficha/radiografias', [RadiografiaController::class, 'store'])->name('fichas.radiografias');
        Route::get('/radiografias/{radiografia}/archivo', [RadiografiaController::class, 'archivo'])->name('radiografias.archivo');
        Route::get('/pacientes/{paciente}/ficha/exportar', [ExportacionFichaController::class, 'imprimir'])->name('fichas.exportar');
        Route::get('/pacientes/{paciente}/ficha/exportar/json', [ExportacionFichaController::class, 'descargar'])->name('fichas.exportar.json');
        Route::post('/plan/{detalle}/realizado', [PlanTratamientoController::class, 'registrar'])->name('detalles.realizado');
    });

    Route::middleware('rol:Recepcionista')->group(function () {
        Route::get('/presupuestos/create', [PresupuestoController::class, 'create'])->name('presupuestos.create');
        Route::post('/presupuestos', [PresupuestoController::class, 'store'])->name('presupuestos.store');
        Route::get('/presupuestos/{presupuesto}/edit', [PresupuestoController::class, 'edit'])->name('presupuestos.edit');
        Route::put('/presupuestos/{presupuesto}', [PresupuestoController::class, 'update'])->name('presupuestos.update');
        Route::patch('/presupuestos/{presupuesto}/aceptar', [PresupuestoController::class, 'aceptar'])->name('presupuestos.aceptar');
        Route::get('/presupuestos/{presupuesto}/anular', [PresupuestoController::class, 'confirmarAnulacion'])->name('presupuestos.anular.confirmar');
        Route::patch('/presupuestos/{presupuesto}/anular', [PresupuestoController::class, 'anular'])->name('presupuestos.anular');
        Route::post('/presupuestos/{presupuesto}/pagos', [PagoController::class, 'store'])->name('pagos.store');
        Route::get('/pagos/{pago}/comprobante', [PagoController::class, 'comprobante'])->name('pagos.comprobante');
    });
    Route::middleware('rol:Dentista,Recepcionista')->group(function () {
        Route::get('/presupuestos', [PresupuestoController::class, 'index'])->name('presupuestos.index');
        Route::get('/presupuestos/{presupuesto}', [PresupuestoController::class, 'show'])->name('presupuestos.show');
        Route::get('/presupuestos/{presupuesto}/imprimir', [PresupuestoController::class, 'imprimir'])->name('presupuestos.imprimir');
    });

    Route::get('/sucursales', [SucursalController::class, 'index'])->name('sucursales.index');
    Route::get('/box', [BoxController::class, 'index'])->name('box.index');
    Route::get('/convenios', [ConvenioController::class, 'index'])->name('convenios.index');

    Route::middleware('rol:Administrador')->group(function () {
        Route::resource('usuarios', UsuarioController::class)->except(['show', 'destroy']);
        Route::resource('roles', RolController::class)->only(['index', 'edit', 'update'])
            ->parameters(['roles' => 'rol']);
        Route::resource('doctores', DoctorController::class)->except(['show', 'destroy'])
            ->parameters(['doctores' => 'doctor']);
        Route::post('/doctores/{doctor}/horarios', [HorarioController::class, 'store'])->name('doctores.horarios.store');
        Route::delete('/doctores/{doctor}/horarios/{horario}', [HorarioController::class, 'destroy'])->name('doctores.horarios.destroy');

        Route::resource('sucursales', SucursalController::class)->except(['index', 'show'])
            ->parameters(['sucursales' => 'sucursal']);
        Route::get('/sucursales/{sucursal}/eliminar', [SucursalController::class, 'confirmarEliminacion'])->name('sucursales.eliminar');
        Route::resource('box', BoxController::class)->except(['index', 'show', 'destroy']);
        Route::resource('convenios', ConvenioController::class)->except(['index', 'show']);
        Route::get('/convenios/{convenio}/eliminar', [ConvenioController::class, 'confirmarEliminacion'])->name('convenios.eliminar');
        Route::resource('tratamientos', TratamientoController::class)->except(['show', 'destroy']);
        Route::resource('bloqueos', BloqueoController::class)->only(['index', 'create', 'store', 'destroy']);
        Route::get('/bloqueos/{bloqueo}/eliminar', [BloqueoController::class, 'confirmarEliminacion'])->name('bloqueos.eliminar');

        Route::get('/pagos/{pago}/anular', [PagoController::class, 'confirmarAnulacion'])->name('pagos.anular.confirmar');
        Route::patch('/pagos/{pago}/anular', [PagoController::class, 'anular'])->name('pagos.anular');

        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
    });
});
