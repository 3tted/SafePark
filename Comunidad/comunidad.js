// ============================================================
//  Comunidad — reacciones con emoji y comentarios
//
//  El feed lo pinta PHP desde el servidor; este archivo se encarga solo de lo
//  que pasa después: reaccionar, comentar y abrir el selector de emojis.
//
//  Detalle clave: cada publicación puede ser un REPORTE o un EVENTO. Ambos
//  viven mezclados en el mismo feed, así que cada bloque lleva dos atributos
//  (data-id-reporte y data-id-evento) y solo uno tiene valor.
// ============================================================

// Cambia entre las pestañas del feed (Actividad, Eventos, Logros)
function cambiarTab(tab, id) {
    document.querySelectorAll('.feed-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    document.querySelectorAll('.feed-panel').forEach(p => p.style.display = 'none');
    document.getElementById('tab-' + id).style.display = 'block';
}

// Averigua a qué publicación pertenece un botón.
//
// Sube por el árbol hasta el elemento que lleva los identificadores, para que
// funcione sin importar si se tocó el botón directamente o algo dentro de él.
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
    if (!ID_USUARIO) { window.location.href = '../Login/index.php'; return; }
    if (pickerActivo === btn) { cerrarPicker(); return; }
    cerrarPicker();
    pickerActivo = btn;

    // El botón se guarda en una constante local en vez de leer pickerActivo al
    // elegir el emoji: en pantallas táctiles el detector de "clic fuera" puede
    // adelantarse a la selección y dejar pickerActivo en null.
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
    // e.target puede ser un nodo de texto y no un elemento, así que hay que
    // comprobarlo antes de usar .closest()
    const el = e.target instanceof Element ? e.target : null;
    if (!el) return;
    if (!el.closest('em-emoji-picker') && !el.classList.contains('reaction-add')) {
        cerrarPicker();
    }
});

// Pone o quita una reacción y actualiza el contador sin recargar la página.
//
// Se llama desde dos lados: al tocar una reacción que ya existe, y al elegir un
// emoji del selector.
function reaccionar(triggerEl, emoji) {
    // Un invitado ve las reacciones pero no puede poner ninguna. En vez de no
    // hacer nada —que se siente como si estuviera roto— se le ofrece entrar.
    if (!ID_USUARIO) { window.location.href = '../Login/index.php'; return; }

    const reactionsBar = triggerEl && triggerEl.closest ? triggerEl.closest('.feed-reactions') : null;
    if (!reactionsBar) return;   // el botón ya no está en la página

    const { id_reporte, id_evento } = getIds(reactionsBar);

    // Solo se manda el identificador que aplica; el otro se omite y el API lo
    // guarda como NULL
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

            // ¿Ya existía un botón para este emoji en esta publicación?
            let btn = Array.from(reactionsBar.querySelectorAll('.reaction-btn:not(.comment-toggle-btn)'))
                          .find(b => b.textContent.startsWith(emoji));

            if (data.total === 0 && btn) {
                // Era la última reacción de ese emoji: el botón desaparece
                btn.remove();
            } else if (btn) {
                // Ya existía: solo cambia el número
                btn.textContent = emoji + ' ' + data.total;
                btn.classList.toggle('reaction-active', data.accion === 'added');
            } else if (data.total > 0) {
                // Es el primero de ese emoji: hay que crear el botón
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

// Abre o cierra la sección de comentarios de una publicación.
//
// Los comentarios se piden la PRIMERA vez que se abren, no al cargar la página:
// el feed muestra diez publicaciones y traer los comentarios de todas sería
// gastar diez llamadas que quizá nadie va a mirar.
function toggleComentarios(btn) {
    // El contenedor cambia según sea un reporte o un evento del feed
    const feedContent = btn.closest('.feed-content') || btn.closest('.evento-info-big');
    const section = feedContent.querySelector('.comentarios-section');
    const abierto = section.style.display === 'block';

    section.style.display = abierto ? 'none' : 'block';

    // "cargado" marca que ya se pidieron, para no repetir la llamada cada vez
    // que se abre y cierra
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

// Arma el HTML de un comentario. Se usa tanto al cargar los existentes como al
// agregar uno nuevo, para que ambos se vean idénticos.
function comentarioHTML(c) {
    const fecha = new Date(c.fecha).toLocaleDateString('es-MX', {
        day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
    });
    return `<div class="comentario-item">
        <span class="comentario-autor">${c.nombre}</span>
        <span class="comentario-texto">${c.texto}</span>
        <span class="comentario-fecha">${fecha}</span>
    </div>`;
}

// Publica un comentario y lo agrega a la lista sin recargar
function enviarComentario(sendBtn) {
    const form  = sendBtn.closest('.comentario-form');
    const input = form.querySelector('.comentario-input');
    const texto = input.value.trim();

    if (!texto) return;   // no se mandan comentarios vacíos

    const section = sendBtn.closest('.comentarios-section');
    const { id_reporte, id_evento } = getIds(section);

    const payload = { id_usuario: ID_USUARIO, texto };
    if (id_reporte) payload.id_reporte = id_reporte;
    if (id_evento)  payload.id_evento  = id_evento;

    // Se desactiva el botón mientras viaja la petición, para que un doble clic
    // no publique el mismo comentario dos veces
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

            // El botón lleva el número de comentarios: se le suma el nuevo para
            // que coincida con lo que se acaba de ver, sin recargar la página.
            const boton = section.parentElement.querySelector('.comment-toggle-btn');
            if (boton) {
                const actual = parseInt((boton.textContent.match(/\((\d+)\)/) || [])[1] || 0);
                boton.textContent = `💬 Comentarios (${actual + 1})`;
            }
        })
        .catch(() => {})
        .finally(() => { sendBtn.disabled = false; });
}

// ============================================================
//  Paginado del feed de actividad
//
//  PHP pinta todas las publicaciones de una vez y aquí se van mostrando de
//  diez en diez. No se piden más datos al cambiar de página: solo se ocultan
//  y se muestran las que ya están en el documento.
//
//  Se hace así, y no cortando en PHP, porque las publicaciones traen sus
//  reacciones y su caja de comentarios ya armadas; volver a pedirlas al
//  servidor en cada página significaría rehacer todo eso.
// ============================================================

const FEED_POR_PAGINA = 10;
let paginaFeed = 1;

function itemsDelFeed() {
    // Solo las del panel de actividad: los otros paneles (eventos, logros)
    // tienen su propia lista y no se paginan
    const panel = document.getElementById('tab-actividad');
    return panel ? panel.querySelectorAll('.feed-item') : [];
}

function pintarPaginaFeed() {
    const items = itemsDelFeed();
    const paginas = Math.ceil(items.length / FEED_POR_PAGINA);

    const desde = (paginaFeed - 1) * FEED_POR_PAGINA;
    const hasta = desde + FEED_POR_PAGINA;
    items.forEach((el, i) => {
        el.style.display = (i >= desde && i < hasta) ? '' : 'none';
    });

    const barra = document.getElementById('paginacion-feed');
    if (!barra) return;

    // Con diez o menos publicaciones no hace falta ningún botón
    if (paginas <= 1) { barra.innerHTML = ''; return; }

    const botones = [];
    botones.push(`<button class="pag-btn pag-flecha" onclick="irAPaginaFeed(${paginaFeed - 1})"
                   ${paginaFeed === 1 ? 'disabled' : ''}>‹</button>`);

    for (let p = 1; p <= paginas; p++) {
        // La primera, la última y las vecinas de la actual; el resto se
        // resume con puntos suspensivos para que la fila no se desborde
        if (p === 1 || p === paginas || Math.abs(p - paginaFeed) <= 1) {
            botones.push(`<button class="pag-btn ${p === paginaFeed ? 'activa' : ''}"
                           onclick="irAPaginaFeed(${p})">${p}</button>`);
        } else if (p === paginaFeed - 2 || p === paginaFeed + 2) {
            botones.push('<span class="pag-puntos">…</span>');
        }
    }

    botones.push(`<button class="pag-btn pag-flecha" onclick="irAPaginaFeed(${paginaFeed + 1})"
                   ${paginaFeed === paginas ? 'disabled' : ''}>›</button>`);

    const primero = desde + 1;
    const ultimo  = Math.min(hasta, items.length);

    barra.innerHTML = `<div class="paginacion">
            <div class="pag-botones">${botones.join('')}</div>
            <div class="pag-cuenta">${primero}-${ultimo} de ${items.length} publicaciones</div>
        </div>`;
}

function irAPaginaFeed(p) {
    const paginas = Math.ceil(itemsDelFeed().length / FEED_POR_PAGINA);
    if (p < 1 || p > paginas) return;
    paginaFeed = p;
    pintarPaginaFeed();
    // Sube al principio del feed: si no, al cambiar de página quedas viendo
    // el final de la lista nueva
    document.getElementById('tab-actividad').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

document.addEventListener('DOMContentLoaded', pintarPaginaFeed);
