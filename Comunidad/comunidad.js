function cambiarTab(tab, id) {
    document.querySelectorAll('.feed-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    document.querySelectorAll('.feed-panel').forEach(p => p.style.display = 'none');
    document.getElementById('tab-' + id).style.display = 'block';
}

function getIds(el) {
    const src = el.closest('[data-id-reporte]') || el;
    return {
        id_reporte: src.dataset.idReporte || '',
        id_evento:  src.dataset.idEvento  || ''
    };
}

let pickerActivo = null;
let pickerEl = null;

function togglePicker(btn) {
    if (pickerActivo === btn) { cerrarPicker(); return; }
    cerrarPicker();
    pickerActivo = btn;

    // El botón se guarda en una constante local en vez de leer pickerActivo al
    // elegir el emoji. En pantallas táctiles el detector de "clic fuera" puede
    // adelantarse a la selección y dejar pickerActivo en null; entonces la
    // reacción fallaba en silencio y había que tocar dos veces.
    const destino = btn;

    pickerEl = new EmojiMart.Picker({
        locale: 'es',
        theme: 'light',
        onEmojiSelect: (emoji) => {
            reaccionar(destino, emoji.native);
            cerrarPicker();
        }
    });

    Object.assign(pickerEl.style, {
        position: 'absolute',
        zIndex: '9999',
        borderRadius: '12px',
        boxShadow: '0 8px 32px rgba(0,0,0,0.18)'
    });

    document.body.appendChild(pickerEl);

    const rect = btn.getBoundingClientRect();
    const top  = rect.bottom + window.scrollY + 6;
    const left = Math.min(rect.left + window.scrollX, window.innerWidth - 360);
    pickerEl.style.top  = top + 'px';
    pickerEl.style.left = Math.max(8, left) + 'px';
}

function cerrarPicker() {
    if (pickerEl) { pickerEl.remove(); pickerEl = null; }
    pickerActivo = null;
}

document.addEventListener('click', e => {
    if (!pickerActivo) return;
    // e.target puede no ser un elemento (nodo de texto); sin esta comprobación
    // el detector truena y deja el picker pegado en pantalla.
    const el = e.target instanceof Element ? e.target : null;
    if (!el) return;
    if (!el.closest('em-emoji-picker') && !el.classList.contains('reaction-add')) {
        cerrarPicker();
    }
});

function reaccionar(triggerEl, emoji) {
    const reactionsBar = triggerEl && triggerEl.closest ? triggerEl.closest('.feed-reactions') : null;
    if (!reactionsBar) return;   // el botón ya no está en la página

    const { id_reporte, id_evento } = getIds(reactionsBar);

    const payload = { id_usuario: ID_USUARIO, emoji };
    if (id_reporte) payload.id_reporte = id_reporte;
    if (id_evento)  payload.id_evento  = id_evento;

    fetch(API_URL + '/reacciones', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) return;

            let btn = Array.from(reactionsBar.querySelectorAll('.reaction-btn:not(.comment-toggle-btn)'))
                          .find(b => b.textContent.startsWith(emoji));

            if (data.total === 0 && btn) {
                btn.remove();
            } else if (btn) {
                btn.textContent = emoji + ' ' + data.total;
                btn.classList.toggle('reaction-active', data.accion === 'added');
            } else if (data.total > 0) {
                const addBtn = reactionsBar.querySelector('.reaction-add');
                const nuevo = document.createElement('button');
                nuevo.className = 'reaction-btn reaction-active';
                nuevo.textContent = emoji + ' ' + data.total;
                nuevo.setAttribute('data-id-reporte', id_reporte);
                nuevo.setAttribute('data-id-evento', id_evento);
                nuevo.onclick = () => reaccionar(nuevo, emoji);
                reactionsBar.insertBefore(nuevo, addBtn);
            }
        })
        .catch(() => {});
}

function toggleComentarios(btn) {
    const feedContent = btn.closest('.feed-content') || btn.closest('.evento-info-big');
    const section = feedContent.querySelector('.comentarios-section');
    const abierto = section.style.display === 'block';
    section.style.display = abierto ? 'none' : 'block';

    if (!abierto && !section.dataset.cargado) {
        section.dataset.cargado = '1';
        const { id_reporte, id_evento } = getIds(section);
        const params = new URLSearchParams();
        if (id_reporte) params.set('id_reporte', id_reporte);
        if (id_evento)  params.set('id_evento', id_evento);

        fetch(API_URL + '/comentarios?' + params)
            .then(r => r.json())
            .then(lista => {
                const lista_el = section.querySelector('.comentarios-lista');
                if (!lista.length) {
                    lista_el.innerHTML = '<div class="sin-comentarios">Sin comentarios aún. ¡Sé el primero!</div>';
                    return;
                }
                lista_el.innerHTML = lista.map(c => comentarioHTML(c)).join('');
            })
            .catch(() => {});
    }
}

function comentarioHTML(c) {
    const fecha = new Date(c.fecha).toLocaleDateString('es-MX', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' });
    return `<div class="comentario-item">
        <span class="comentario-autor">${c.nombre}</span>
        <span class="comentario-texto">${c.texto}</span>
        <span class="comentario-fecha">${fecha}</span>
    </div>`;
}

function enviarComentario(sendBtn) {
    const form   = sendBtn.closest('.comentario-form');
    const input  = form.querySelector('.comentario-input');
    const texto  = input.value.trim();
    if (!texto) return;

    const section = sendBtn.closest('.comentarios-section');
    const { id_reporte, id_evento } = getIds(section);

    const payload = { id_usuario: ID_USUARIO, texto };
    if (id_reporte) payload.id_reporte = id_reporte;
    if (id_evento)  payload.id_evento  = id_evento;

    sendBtn.disabled = true;
    fetch(API_URL + '/comentarios', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) return;
            const lista_el = section.querySelector('.comentarios-lista');
            const sinMsg = lista_el.querySelector('.sin-comentarios');
            if (sinMsg) sinMsg.remove();
            lista_el.insertAdjacentHTML('beforeend', comentarioHTML(data.comentario));
            lista_el.scrollTop = lista_el.scrollHeight;
            input.value = '';
        })
        .catch(() => {})
        .finally(() => { sendBtn.disabled = false; });
}
