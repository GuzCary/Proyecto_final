document.addEventListener('DOMContentLoaded', () => {
    verificarSesion('admin', () => {
        activarLogout();
        cargarBajas();
        configurarFiltros();
    });
});

const tabla = document.getElementById('tabla-bajas');
const filtroTexto = document.getElementById('filtro-texto');
const filtroRol = document.getElementById('filtro-rol');
const mensaje = document.getElementById('mensaje');

const nombresRol = { admin: 'Administrador', user: 'Vendedor', limp: 'Limpieza' };

let bajasGuardadas = [];

async function cargarBajas() {
    try {
        const data = await pedirJSON('../../../backend/usuarios/listar_bajas.php');

        if (data.status !== 'success') {
            tabla.innerHTML = `<tr><td colspan="7">Error: ${data.message}</td></tr>`;
            return;
        }

        bajasGuardadas = data.bajas || [];
        mostrarBajas();
    } catch (e) {
        tabla.innerHTML = '<tr><td colspan="7">Error al cargar el historial de bajas.</td></tr>';
    }
}

// dibuja la tabla aplicando los filtros (se filtra en el navegador, sin volver a pedir datos)
function mostrarBajas() {
    const texto = filtroTexto.value.trim().toLowerCase();
    const rol = filtroRol.value;

    const filtradas = bajasGuardadas.filter(b => {
        const coincideTexto = texto === ''
            || b.usuario.toLowerCase().includes(texto)
            || (b.motivo || '').toLowerCase().includes(texto);
        const coincideRol = rol === '' || b.rol === rol;
        return coincideTexto && coincideRol;
    });

    tabla.innerHTML = '';

    if (filtradas.length === 0) {
        tabla.innerHTML = `<tr><td colspan="7" class="texto-vacio">${bajasGuardadas.length === 0 ? 'No hay usuarios dados de baja.' : 'Ninguna baja coincide con el filtro.'}</td></tr>`;
        return;
    }

    filtradas.forEach(b => {
        const indice = bajasGuardadas.indexOf(b);
        const accion = b.nombreEnUso
            ? '<span class="etiqueta alerta" title="Otro empleado activo usa este nombre">Nombre en uso</span>'
            : `<button class="btn-edit" data-indice="${indice}">Reactivar</button>`;

        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td><strong>${b.usuario}</strong></td>
            <td>${nombresRol[b.rol] || b.rol}</td>
            <td>${b.sucursal || 'N/A'}</td>
            <td>${b.fechaDeContrato || 'N/A'}</td>
            <td>${b.fechaBaja || 'N/A'}</td>
            <td>${b.motivo || 'Sin motivo'}</td>
            <td>${accion}</td>
        `;
        tabla.appendChild(fila);
    });
}

function configurarFiltros() {
    filtroTexto.addEventListener('input', mostrarBajas);
    filtroRol.addEventListener('change', mostrarBajas);

    tabla.addEventListener('click', (e) => {
        const boton = e.target.closest('button[data-indice]');
        if (!boton) return;
        const b = bajasGuardadas[boton.dataset.indice];
        if (b) reactivar(b);
    });
}

async function reactivar(b) {
    if (!confirm(`¿Reactivar a "${decodificar(b.usuario)}"? Va a poder volver a iniciar sesión con su contraseña anterior.`)) return;

    const fd = new FormData();
    fd.append('id', b.id_usuario);

    const data = await pedirJSON('../../../backend/usuarios/reactivar_usuario.php', {
        method: 'POST',
        body: fd
    });
    mostrarMensaje(data.status, data.message);

    if (data.status === 'success') {
        cargarBajas();
    }
}

function decodificar(texto) {
    const t = document.createElement('textarea');
    t.innerHTML = texto;
    return t.value;
}

function mostrarMensaje(tipo, texto) {
    mensaje.style.display = 'block';
    mensaje.style.background = tipo === 'success' ? '#d4edda' : '#f8d7da';
    mensaje.style.color = tipo === 'success' ? '#155724' : '#721c24';
    mensaje.textContent = decodificar(texto);
    // si habia un mensaje anterior, cancelamos su temporizador para que no oculte este
    clearTimeout(mensaje.temporizador);
    mensaje.temporizador = setTimeout(() => mensaje.style.display = 'none', 4000);
}
