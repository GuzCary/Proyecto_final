document.addEventListener('DOMContentLoaded', () => {
    verificarSesion('admin', () => {
        activarLogout();
        cargarEmpleados();
        cargarSanciones();
        configurarFormulario();
    });
});

const form = document.getElementById('form-sancion');
const selectEmpleado = document.getElementById('id_usuario');
const inputFecha = document.getElementById('fecha');
const tabla = document.getElementById('tabla-sanciones');
const mensaje = document.getElementById('mensaje');

const nombresRol = { admin: 'Administrador', user: 'Vendedor', limp: 'Limpieza' };
const nombresTipo = { Observacion: 'Observación', Advertencia: 'Advertencia', Suspension: 'Suspensión', Descuento: 'Descuento' };
const claseTipo = { Observacion: '', Advertencia: 'alerta', Suspension: 'mal', Descuento: 'mal' };

// fecha de hoy en formato AAAA-MM-DD (hora local, no UTC)
function hoy() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

// carga los empleados activos (sin incluir al admin logueado)
async function cargarEmpleados() {
    try {
        const data = await pedirJSON('../../../backend/usuarios/listar_usuarios.php');
        selectEmpleado.innerHTML = '<option value="">Seleccioná un empleado</option>';

        (data.usuarios || []).filter(u => !u.esYo).forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.innerHTML = `${u.usuario} (${nombresRol[u.rol] || u.rol})`;
            selectEmpleado.appendChild(opt);
        });
    } catch (e) {
        selectEmpleado.innerHTML = '<option value="">Error al cargar empleados</option>';
    }
}

async function cargarSanciones() {
    try {
        const data = await pedirJSON('../../../backend/sanciones/listar_sanciones.php');
        tabla.innerHTML = '';

        if (data.status !== 'success') {
            tabla.innerHTML = `<tr><td colspan="6">Error: ${data.message}</td></tr>`;
            return;
        }

        if (!data.sanciones || data.sanciones.length === 0) {
            tabla.innerHTML = '<tr><td colspan="6" class="texto-vacio">No hay sanciones registradas.</td></tr>';
            return;
        }

        data.sanciones.forEach(s => {
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td>${s.fecha}</td>
                <td><strong>${s.empleado}</strong></td>
                <td><span class="etiqueta ${claseTipo[s.tipo] || ''}">${nombresTipo[s.tipo] || s.tipo}</span></td>
                <td>${s.motivo}</td>
                <td>${s.consecuencia || '-'}</td>
                <td>${s.administrador}</td>
            `;
            tabla.appendChild(fila);
        });
    } catch (e) {
        tabla.innerHTML = '<tr><td colspan="6">Error al cargar sanciones.</td></tr>';
    }
}

function configurarFormulario() {
    // por defecto la fecha es hoy y no se permiten fechas futuras
    inputFecha.value = hoy();
    inputFecha.max = hoy();

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const empleado = selectEmpleado.options[selectEmpleado.selectedIndex].text;
        if (!confirm(`¿Registrar la sanción para ${empleado}? Las sanciones no se pueden editar ni borrar.`)) return;

        const data = await pedirJSON('../../../backend/sanciones/registrar_sancion.php', {
            method: 'POST',
            body: new FormData(form)
        });
        mostrarMensaje(data.status, data.message);

        if (data.status === 'success') {
            form.reset();
            inputFecha.value = hoy();
            cargarSanciones();
        }
    });
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
