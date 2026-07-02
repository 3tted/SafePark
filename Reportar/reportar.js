function selectTipo(card, valor) {
    document.querySelectorAll('.tipo-card').forEach(c => c.classList.remove('sel'));
    card.classList.add('sel');
    document.getElementById('input-tipo').value = valor;
}
