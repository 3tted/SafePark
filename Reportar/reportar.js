function selectTipo(card) {
    document.querySelectorAll('.tipo-card').forEach(c => c.classList.remove('sel'));
    card.classList.add('sel');
}

function enviarReporte() {
    const area = document.querySelector('.form-input[type="text"]').value.trim();
    const desc = document.querySelector('textarea.form-input').value.trim();

    if (!area) {
        alert('Por favor indica el área o zona.');
        return;
    }
    if (!desc) {
        alert('Por favor agrega una descripción.');
        return;
    }

    alert('✅ Reporte enviado exitosamente. Será revisado por el equipo de moderación.');

    // Limpiar formulario
    document.querySelector('.form-input[type="text"]').value = '';
    document.querySelector('textarea.form-input').value = '';
    document.querySelectorAll('.tipo-card').forEach((c, i) => {
        c.classList.toggle('sel', i === 0);
    });
}
