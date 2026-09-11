document.addEventListener('DOMContentLoaded', () => {
    verificarSesion('user', () => {
        activarLogout();
        cargarVehiculosDisponibles();
        cargarHistorialVentas();
    });
});

const contenedorDisponibles = document.getElementById('contenedor-disponibles');
const contenedorHistorial = document.getElementById('contenedor-historial');
const mensaje = document.getElementById('mensaje');

async function cargarVehiculosDisponibles() {
    try {
        const res = await fetch('../../../backend/vehiculos/listar_vehiculos.php');
        const data = await res.json();
        contenedorDisponibles.innerHTML = '';

        // Disponibles para vender: los que NO están en la tabla Ventas
        const disponibles = data.vehiculos.filter(v => v.vendido == 0);

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
                <button class="btn-vender" onclick="vender('${v.id}')">Registrar Venta</button>
            `;
            contenedorDisponibles.appendChild(div);
        });
    } catch (e) {
        contenedorDisponibles.innerHTML = '<p>Error al cargar vehículos.</p>';
    }
}

async function cargarHistorialVentas() {
    try {
        const res = await fetch('../../../backend/ventas/listar_ventas.php');
        const data = await res.json();
        contenedorHistorial.innerHTML = '';

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
                <span>Fecha: ${v.fecha}</span>
            `;
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

    const res = await fetch('../../../backend/ventas/registrar_venta.php', {
        method: 'POST',
        body: fd
    });
    const data = await res.json();
    mostrarMensaje(data.status, data.message);

    if (data.status === 'success') {
        cargarVehiculosDisponibles();
        cargarHistorialVentas();
    }
}

function mostrarMensaje(tipo, texto) {
    mensaje.style.display = 'block';
    mensaje.style.background = tipo === 'success' ? '#d4edda' : '#f8d7da';
    mensaje.style.color = tipo === 'success' ? '#155724' : '#721c24';
    mensaje.textContent = texto;
    setTimeout(() => mensaje.style.display = 'none', 2500);
}