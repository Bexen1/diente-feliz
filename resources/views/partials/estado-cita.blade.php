@php
    $estilos = [
        'agendada' => ['bg-secondary-subtle text-secondary-emphasis', 'bi-clock'],
        'confirmada' => ['bg-primary-subtle text-primary-emphasis', 'bi-telephone-check'],
        'reagendada' => ['bg-warning-subtle text-warning-emphasis', 'bi-arrow-repeat'],
        'atendida' => ['bg-success-subtle text-success-emphasis', 'bi-check-circle'],
        'cancelada' => ['bg-danger-subtle text-danger-emphasis', 'bi-x-circle'],
    ];
    [$clases, $icono] = $estilos[$estado] ?? ['bg-secondary-subtle text-secondary-emphasis', 'bi-circle'];
@endphp
<span class="badge {{ $clases }}"><i class="bi {{ $icono }}"></i> {{ ucfirst($estado) }}</span>
