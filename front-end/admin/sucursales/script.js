document.addEventListener('DOMContentLoaded', () => {
    verificarSesion('admin', () => {
        activarLogout();
        cargarSucursales();
        configurarFormulario();
    });
});

const form = document.getElementById('form-sucursal');
const idInput = document.getElementById('id_encriptado');
const nombreInput = document.getElementById('nombre');
const btnCancelar = document.getElementById('btn-cancelar');
const formTitulo = document.getElementById('form-titulo');
const contenedor = document.getElementById('contenedor-sucursales');
const mensaje = document.getElementById('mensaje');

let sucursalesGuardadas = [];

async function cargarSucursales() {
    try {
        const data = await pedirJSON('../../../backend/sucursales/listar_sucursales.php');
        contenedor.innerHTML = '';

        if (data.status !== 'success') {
            contenedor.innerHTML = `<p>Error: ${data.message}</p>`;
            return;
        }

        sucursalesGuardadas = data.sucursales || [];

        if (sucursalesGuardadas.length === 0) {
            contenedor.innerHTML = '<p>No hay sucursales registradas.</p>';
            return;
        }

        sucursalesGuardadas.forEach((s, indice) => {
            // si tiene registros asociados avisamos y deshabilitamos el boton eliminar
            const enUso = s.vehiculos > 0 || s.usuarios > 0 || s.transacciones > 0;

            const div = document.createElement('div');
            div.classList.add('item-row');
            div.innerHTML = `
                <div>
                    <strong>${s.nombre}</strong>
                    <br><small>${s.vehiculos} vehículo(s) · ${s.usuarios} usuario(s) · ${s.transacciones} transacción(es)</small>
                </div>
                <div class="item-actions">
                    <button class="btn-edit" data-accion="editar" data-indice="${indice}">Editar</button>
                    <button class="btn-danger" data-accion="eliminar" data-indice="${indice}"
                        ${enUso ? 'disabled title="Tiene registros asociados"' : ''}>Eliminar</button>
                </div>
            `;
            contenedor.appendChild(div);
        });
    } catch (e) {
        contenedor.innerHTML = '<p>Error al cargar sucursales.</p>';
    }
}

function configurarFormulario() {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = await pedirJSON('../../../backend/sucursales/registrar_sucursal.php', {
            method: 'POST',
            body: new FormData(form)
        });
        mostrarMensaje(data.status, data.message);
        if (data.status === 'success') {
            limpiar();
            cargarSucursales();
        }
    });

    btnCancelar.addEventListener('click', limpiar);

    // un solo listener para los botones de todas las sucursales
    contenedor.addEventListener('click', (e) => {
        const boton = e.target.closest('button[data-accion]');
        if (!boton) return;
        const s = sucursalesGuardadas[boton.dataset.indice];
        if (!s) return;

        if (boton.dataset.accion === 'editar') editar(s);
        if (boton.dataset.accion === 'eliminar') eliminar(s);
    });
}

function editar(s) {
    idInput.value = s.id;
    nombreInput.value = decodificar(s.nombre);
    formTitulo.textContent = 'Editar Sucursal';
    btnCancelar.style.display = 'inline-block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    nombreInput.focus();
}

function limpiar() {
    idInput.value = '';
    nombreInput.value = '';
    formTitulo.textContent = 'Agregar Sucursal';
    btnCancelar.style.display = 'none';
}

async function eliminar(s) {
    if (!confirm(`¿Deseas eliminar la sucursal "${decodificar(s.nombre)}"?`)) return;

    const fd = new FormData();
    fd.append('id_encriptado', s.id);
    const data = await pedirJSON('../../../backend/sucursales/eliminar_sucursal.php', {
        method: 'POST',
        body: fd
    });
    mostrarMensaje(data.status, data.message);
    if (data.status === 'success') {
        // si justo se estaba editando la sucursal borrada, limpiamos el formulario
        if (idInput.value === s.id) limpiar();
        cargarSucursales();
    }
}

// convierte entidades HTML (&amp; &quot; ...) de vuelta a texto normal
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
