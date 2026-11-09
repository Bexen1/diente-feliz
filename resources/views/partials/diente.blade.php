@php
    $cuadrante = intdiv($pieza, 10);
    $superior = in_array($cuadrante, [1, 2, 5, 6], true);
    $mesialDerecha = in_array($cuadrante, [1, 4, 5, 8], true);
    $estadoDiente = $hallazgo['estado'] ?? 'sano';
    $carasDiente = $hallazgo['caras'] ?? [];
    $colorCara = ['caries' => '#ef4444', 'restauracion' => '#3b82f6'][$estadoDiente] ?? null;

    $zonas = [
        'arriba' => $superior ? 'vestibular' : 'lingual',
        'abajo' => $superior ? 'lingual' : 'vestibular',
        'izquierda' => $mesialDerecha ? 'distal' : 'mesial',
        'derecha' => $mesialDerecha ? 'mesial' : 'distal',
        'centro' => 'oclusal',
    ];

    $relleno = function (string $cara) use ($estadoDiente, $carasDiente, $colorCara) {
        if ($colorCara && (in_array($cara, $carasDiente, true) || ($carasDiente === [] && $cara === 'oclusal'))) {
            return $colorCara;
        }
        if ($estadoDiente === 'endodoncia') {
            return $cara === 'oclusal' ? '#f59e0b' : '#fef3c7';
        }
        if ($estadoDiente === 'ausente') {
            return '#e5e7eb';
        }

        return '#ffffff';
    };
@endphp
<svg viewBox="0 0 30 30" width="28" height="28" class="diente-svg" aria-hidden="true">
    <g stroke="#9aa9a7" stroke-width="0.8" stroke-linejoin="round">
        <polygon points="0,0 30,0 21,9 9,9" fill="{{ $relleno($zonas['arriba']) }}"/>
        <polygon points="9,21 21,21 30,30 0,30" fill="{{ $relleno($zonas['abajo']) }}"/>
        <polygon points="0,0 9,9 9,21 0,30" fill="{{ $relleno($zonas['izquierda']) }}"/>
        <polygon points="30,0 30,30 21,21 21,9" fill="{{ $relleno($zonas['derecha']) }}"/>
        <rect x="9" y="9" width="12" height="12" fill="{{ $relleno($zonas['centro']) }}"/>
    </g>
    @if (in_array($estadoDiente, ['ausente', 'extraccion'], true))
        <g stroke="{{ $estadoDiente === 'extraccion' ? '#dc2626' : '#6b7280' }}" stroke-width="2.6" stroke-linecap="round">
            <line x1="3" y1="3" x2="27" y2="27"/>
            <line x1="27" y1="3" x2="3" y2="27"/>
        </g>
    @endif
</svg>
