(function () {
    var odontograma = document.getElementById('odontograma');
    var formulario = document.getElementById('form-hallazgo');
    if (!odontograma || !formulario) return;

    var selectPieza = formulario.querySelector('#pieza');
    var selectEstado = formulario.querySelector('#estado');
    var casillasCaras = formulario.querySelectorAll('input[name="caras[]"]');

    function seleccionar(boton) {
        odontograma.querySelectorAll('.pieza').forEach(function (pieza) {
            pieza.setAttribute('aria-pressed', pieza === boton ? 'true' : 'false');
        });

        selectPieza.value = boton.dataset.pieza;
        selectEstado.value = boton.dataset.estado;

        var caras = boton.dataset.caras ? boton.dataset.caras.split(',') : [];
        casillasCaras.forEach(function (casilla) {
            casilla.checked = caras.indexOf(casilla.value) !== -1;
        });
    }

    odontograma.addEventListener('click', function (evento) {
        var boton = evento.target.closest('.pieza');
        if (boton) seleccionar(boton);
    });

    selectPieza.addEventListener('change', function () {
        var boton = odontograma.querySelector('.pieza[data-pieza="' + selectPieza.value + '"]');
        if (boton) seleccionar(boton);
    });
})();
