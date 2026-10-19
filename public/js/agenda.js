(function () {
    var agenda = document.getElementById('agenda');
    var formulario = document.getElementById('form-cita');
    if (!agenda || !formulario) return;

    agenda.addEventListener('click', function (evento) {
        var celda = evento.target.closest('.agenda-celda');
        if (!celda) return;

        formulario.querySelector('#box_id').value = celda.dataset.box;
        formulario.querySelector('#hora').value = celda.dataset.hora;

        agenda.querySelectorAll('.agenda-celda.seleccionada').forEach(function (c) {
            c.classList.remove('seleccionada');
        });
        celda.classList.add('seleccionada');

        var paciente = formulario.querySelector('#paciente_id');
        paciente.focus();
        formulario.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
})();
