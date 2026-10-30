document.addEventListener('DOMContentLoaded', () => {
    verificarSesion('admin', () => {
        activarLogout();
        cargarSucursales();
        cargarTransacciones();
        configurarFormulario();
        configurarFiltros();
    });
});

const form = document.getElementById('form-transaccion');
const selectSucursal = document.getElementById('id_sucursal');
const inputFecha = document.getElementById('fecha');
const filtroSucursal = document.getElementById('filtro-sucursal');
const filtroDesde = document.getElementById('filtro-desde');
const filtroHasta = document.getElementById('filtro-hasta');
const tabla = document.getElementById('tabla-transacciones');
const mensaje = document.getElementById('mensaje');

function formatoPlata(n) {
    return '$' + Number(n).toLocaleString('es-UY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// fecha de hoy en formato AAAA-MM-DD (hora local, no UTC)
function hoy() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

// carga las sucursales en el formulario y en el filtro
async function cargarSucursales() {
    try {
        const data = await pedirJSON('../../../backend/sucursales/listar_sucursales.php');
        const sucursales = data.sucursales || [];

        selectSucursal.innerHTML = sucursales.length === 0
            ? '<option value="">No hay sucursales (crealas en Config &gt; Sucursales)</option>'
            : '';

        sucursales.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.innerHTML = s.nombre;
            selectSucursal.appendChild(opt);
            filtroSucursal.appendChild(opt.cloneNode(true));
        });
    } catch (e) {
        selectSucursal.innerHTML = '<option value="">Error al cargar sucursales</option>';
    }
}

async function cargarTransacciones() {
    // armamos los filtros que esten completos
    const params = new URLSearchParams();
    if (filtroSucursal.value) params.append('sucursal', filtroSucursal.value);
    if (filtroDesde.value) params.append('desde', filtroDesde.value);
    if (filtroHasta.value) params.append('hasta', filtroHasta.value);
    const query = params.toString() ? '?' + params.toString() : '';

    try {
        const data = await pedirJSON('../../../backend/transacciones/listar_transacciones.php' + query);
        tabla.innerHTML = '';

        if (data.status !== 'success') {
            tabla.innerHTML = `<tr><td colspan="6">Error: ${data.message}</td></tr>`;
            return;
        }

        // totales
        document.getElementById('total-ingresos').textContent = formatoPlata(data.totales.ingresos);
        document.getElementById('total-egresos').textContent = formatoPlata(data.totales.egresos);
        const balance = document.getElementById('total-balance');
        balance.textContent = formatoPlata(data.totales.balance);
        balance.className = data.totales.balance < 0 ? 'monto-egreso' : 'monto-ingreso';

        if (!data.transacciones || data.transacciones.length === 0) {
            tabla.innerHTML = '<tr><td colspan="6" class="texto-vacio">No hay movimientos para mostrar.</td></tr>';
            return;
        }

        data.transacciones.forEach(t => {
            const esIngreso = t.tipo === 'Ingreso';
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td>${t.fecha}</td>
                <td>${t.sucursal}</td>
                <td><span class="etiqueta ${esIngreso ? 'ok' : 'mal'}">${t.tipo}</span></td>
                <td class="num ${esIngreso ? 'monto-ingreso' : 'monto-egreso'}">${esIngreso ? '+' : '-'}${formatoPlata(t.monto)}</td>
                <td>${t.justificacion}</td>
                <td>${t.funcionario}</td>
            `;
            tabla.appendChild(fila);
        });
    } catch (e) {
        tabla.innerHTML = '<tr><td colspan="6">Error al cargar transacciones.</td></tr>';
    }
}

function configurarFormulario() {
    inputFecha.value = hoy();
    inputFecha.max = hoy();

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = await pedirJSON('../../../backend/transacciones/registrar_transaccion.php', {
            method: 'POST',
            body: new FormData(form)
        });
        mostrarMensaje(data.status, data.message);

        if (data.status === 'success') {
            // dejamos la sucursal elegida para cargar varios movimientos seguidos
            const sucursal = selectSucursal.value;
            form.reset();
            selectSucursal.value = sucursal;
            inputFecha.value = hoy();
            cargarTransacciones();
        }
    });
}

function configurarFiltros() {
    document.getElementById('btn-filtrar').addEventListener('click', cargarTransacciones);
    document.getElementById('btn-limpiar').addEventListener('click', () => {
        filtroSucursal.value = '';
        filtroDesde.value = '';
        filtroHasta.value = '';
        cargarTransacciones();
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
