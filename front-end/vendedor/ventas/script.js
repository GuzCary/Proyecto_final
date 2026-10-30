document.addEventListener('DOMContentLoaded', () => {
    // el administrador tambien puede entrar a este panel (desde Config del panel admin)
    verificarSesion(['user', 'admin'], (datos) => {
        if (datos.usuario_rol === 'admin') {
            document.getElementById('link-admin').hidden = false;
        }
        activarLogout();
        cargarVehiculosDisponibles();
        cargarHistorialVentas();
    });
});

const contenedorDisponibles = document.getElementById('contenedor-disponibles');
const contenedorHistorial = document.getElementById('contenedor-historial');
const mensaje = document.getElementById('mensaje');

// un solo listener para los botones "Registrar Venta"
contenedorDisponibles.addEventListener('click', (e) => {
    const boton = e.target.closest('button[data-id]');
    if (boton) vender(boton.dataset.id);
});

async function cargarVehiculosDisponibles() {
    try {
        const data = await pedirJSON('../../../backend/vehiculos/listar_vehiculos.php');
        contenedorDisponibles.innerHTML = '';

        if (data.status !== 'success') {
            contenedorDisponibles.innerHTML = `<p>Error: ${data.message}</p>`;
            return;
        }

        // Disponibles para vender: los que NO están en la tabla Ventas
        const disponibles = (data.vehiculos || []).filter(v => v.vendido == 0);

        if (disponibles.length === 0) {
            contenedorDisponibles.innerHTML = '<p>No hay vehículos disponibles para vender.</p>';
            return;
        }

        disponibles.forEach(v => {
            const div = document.createElement('div');
            div.classList.add('item-venta');
            div.innerHTML = `
                <strong>${v.marca} ${v.modelo}</strong>
                <span>Precio: $${v.precio}</span>
                <small>Año: ${v.anio || 'N/A'} | Km: ${v.km || 0}</small>
                <button class="btn-vender" data-id="${v.id}">Registrar Venta</button>
            `;
            contenedorDisponibles.appendChild(div);
        });
    } catch (e) {
        contenedorDisponibles.innerHTML = '<p>Error al cargar vehículos.</p>';
    }
}

// temporizadores de los botones "Eliminar venta" (se cancelan al recargar el historial)
let temporizadores = [];

async function cargarHistorialVentas() {
    try {
        const data = await pedirJSON('../../../backend/ventas/listar_ventas.php');
        contenedorHistorial.innerHTML = '';
        temporizadores.forEach(t => clearInterval(t));
        temporizadores = [];

        if (data.status !== 'success') {
            contenedorHistorial.innerHTML = `<p>Error: ${data.message}</p>`;
            return;
        }

        if (!data.ventas || data.ventas.length === 0) {
            contenedorHistorial.innerHTML = '<p>No hay ventas registradas.</p>';
            return;
        }

        data.ventas.forEach(v => {
            const div = document.createElement('div');
            div.classList.add('item-historial');
            div.innerHTML = `
                <div>
                    <strong>${v.marca} ${v.modelo}</strong> - $${v.precio}
                    <br><small>Vendedor: ${v.vendedor}</small>
                </div>
                <div class="historial-derecha">
                    <span>Fecha: ${v.fecha}</span>
                </div>
            `;

            // durante el primer minuto se muestra el boton para eliminar la venta
            if (v.segundosParaEliminar > 0) {
                agregarBotonEliminar(div.querySelector('.historial-derecha'), v);
            }

            contenedorHistorial.appendChild(div);
        });
    } catch (e) {
        contenedorHistorial.innerHTML = '<p>Error al cargar historial.</p>';
    }
}

async function vender(idVehiculo) {
    if (!confirm('¿Confirmar la venta de este vehículo?')) return;

    const fd = new FormData();
    fd.append('id_vehiculo', idVehiculo);

    const data = await pedirJSON('../../../backend/ventas/registrar_venta.php', {
        method: 'POST',
        body: fd
    });
    mostrarMensaje(data.status, data.message);

    if (data.status === 'success') {
        cargarVehiculosDisponibles();
        cargarHistorialVentas();
    }
}

// agrega el boton "Eliminar venta" con una cuenta regresiva; al llegar a 0 el boton desaparece
function agregarBotonEliminar(contenedor, venta) {
    // calculamos el vencimiento con el reloj del navegador a partir de los segundos que informa el servidor
    const vence = Date.now() + venta.segundosParaEliminar * 1000;

    const boton = document.createElement('button');
    boton.className = 'btn-eliminar-venta';
    contenedor.appendChild(boton);

    function actualizar() {
        const restantes = Math.ceil((vence - Date.now()) / 1000);
        if (restantes <= 0) {
            boton.remove();
            clearInterval(temporizador);
            return;
        }
        boton.textContent = `Eliminar venta (${restantes}s)`;
    }

    const temporizador = setInterval(actualizar, 1000);
    temporizadores.push(temporizador);
    actualizar();

    boton.addEventListener('click', () => eliminarVenta(venta));
}

async function eliminarVenta(venta) {
    if (!confirm(`¿Eliminar la venta de ${venta.marca} ${venta.modelo}? El vehículo vuelve a quedar disponible.`)) return;

    const fd = new FormData();
    fd.append('id_venta', venta.id_venta);

    const data = await pedirJSON('../../../backend/ventas/eliminar_venta.php', {
        method: 'POST',
        body: fd
    });
    mostrarMensaje(data.status, data.message);

    // recargamos siempre: si ya paso el minuto, el boton desaparece
    cargarVehiculosDisponibles();
    cargarHistorialVentas();
}

function mostrarMensaje(tipo, texto) {
    mensaje.style.display = 'block';
    mensaje.style.background = tipo === 'success' ? '#d4edda' : '#f8d7da';
    mensaje.style.color = tipo === 'success' ? '#155724' : '#721c24';
    mensaje.textContent = texto;
    // si habia un mensaje anterior, cancelamos su temporizador para que no oculte este
    clearTimeout(mensaje.temporizador);
    mensaje.temporizador = setTimeout(() => mensaje.style.display = 'none', 4000);
}
