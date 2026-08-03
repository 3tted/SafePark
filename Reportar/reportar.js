// Selección del tipo de reporte (incidente, condición o sugerencia).
//
// Las tres tarjetas son visuales; el valor real viaja en un input oculto
// llamado "tipo", que es lo que recibe el servidor al enviar el formulario.
function selectTipo(card, valor) {
    // Apaga el resaltado de las tres tarjetas
    document.querySelectorAll('.tipo-card').forEach(c => c.classList.remove('sel'));
    // Enciende el de la tarjeta elegida
    card.classList.add('sel');
    // Guarda el valor en el campo oculto que se enviará
    document.getElementById('input-tipo').value = valor;
}
