// Utilidades compartidas por todas las páginas
const $ = id => document.getElementById(id);

async function api(url, metodo = 'GET', datos) {
    const r = await fetch(url, {
        method: metodo,
        headers: { 'Content-Type': 'application/json' },
        body: datos ? JSON.stringify(datos) : undefined
    });
    const texto = await r.text();
    let j = {}, valido = true;
    try { j = texto ? JSON.parse(texto) : {}; } catch (e) { valido = false; }
    if (r.status === 401 && !/(login|registro)\.html$/.test(location.pathname)) location.href = 'login.html';
    if (!valido) throw new Error('Respuesta no válida del servidor (revisa PHP/MySQL): ' + texto.replace(/<[^>]*>/g, ' ').trim().slice(0, 250));
    if (!r.ok) throw new Error(j.error || 'Error del servidor');
    return j;
}

function esc(t) { const d = document.createElement('div'); d.textContent = t ?? ''; return d.innerHTML; }

// Alerta de Bootstrap descartable dentro de #mensajes
function alerta(tipo, msg) {
    $('mensajes').innerHTML = `<div class="alert alert-${tipo} alert-dismissible fade show" role="alert">${esc(msg)}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
}

// Marca un campo como válido/inválido (clases de Bootstrap)
function marcar(id, ok) { $(id).classList.toggle('is-invalid', !ok); return ok; }

// Breadcrumb: items = [['Inicio','login.html'], ['Actual']]
function migas(items) {
    $('migas').innerHTML = '<ol class="breadcrumb">' + items.map((e, i) => i === items.length - 1
        ? `<li class="breadcrumb-item active" aria-current="page">${e[0]}</li>`
        : `<li class="breadcrumb-item"><a href="${e[1]}">${e[0]}</a></li>`).join('') + '</ol>';
}

const ESTADOS = { en_stock: ['En Stock', 'success'], agotado: ['Agotado', 'danger'], descontinuado: ['Descontinuado', 'secondary'] };
function badgeEstado(e) { const [t, c] = ESTADOS[e] || [e, 'secondary']; return `<span class="badge bg-${c}">${t}</span>`; }

// Comprueba la sesión (si no hay, api() redirige al login). Devuelve el usuario.
function sesion() { return api('api/auth.php?accion=sesion'); }
function idDeUrl() { return new URLSearchParams(location.search).get('id'); }
