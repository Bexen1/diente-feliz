@php
    $estilos = [
        'borrador' => ['bg-secondary-subtle text-secondary-emphasis', 'bi-pencil-square'],
        'aceptado' => ['bg-primary-subtle text-primary-emphasis', 'bi-hand-thumbs-up'],
        'pagado' => ['bg-success-subtle text-success-emphasis', 'bi-check-circle'],
        'anulado' => ['bg-danger-subtle text-danger-emphasis', 'bi-x-circle'],
    ];
    [$clases, $icono] = $estilos[$estado] ?? ['bg-secondary-subtle text-secondary-emphasis', 'bi-circle'];
@endphp
<span class="badge {{ $clases }}"><i class="bi {{ $icono }}"></i> {{ ucfirst($estado) }}</span>
