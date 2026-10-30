document.addEventListener('DOMContentLoaded', () => {
    // el administrador tambien puede entrar a este panel (desde Config del panel admin)
    verificarSesion(['limp', 'admin'], (datos) => {
        if (datos.usuario_rol === 'admin') {
            document.getElementById('link-admin').hidden = false;
        }
        activarLogout();
        cargarProductos();
        configurarFormulario();
    });
});

const form = document.getElementById('form-producto');
const idInput = document.getElementById('id_encriptado');
const nombreInput = document.getElementById('nombre');
const formTitulo = document.getElementById('form-titulo');
const btnGuardar = document.getElementById('btn-guardar');
const btnCancelar = document.getElementById('btn-cancelar');
const contenedor = document.getElementById('contenedor-productos');
const mensaje = document.getElementById('mensaje');

let productosGuardados = [];

async function cargarProductos() {
    const data = await pedirJSON('../../../backend/productos/listar_productos.php');
    contenedor.innerHTML = '';

    if (data.status !== 'success') {
        contenedor.innerHTML = `<p>Error: ${data.message}</p>`;
        return;
    }

    productosGuardados = data.productos || [];

    if (productosGuardados.length === 0) {
        contenedor.innerHTML = '<p>No hay productos registrados.</p>';
        return;
    }

    productosGuardados.forEach((p, indice) => {
        const div = document.createElement('div');
        div.classList.add('item-producto');
        div.innerHTML = `
            <div>
                <strong>${p.nombre}</strong>
                <br><small>Stock actual: <b>${p.stock}</b></small>
            </div>
            <div class="stock-acciones">
                <input type="number" class="input-cantidad" value="1" min="1" max="100000" step="1" aria-label="Cantidad">
                <button class="btn-stock" data-accion="sumar" data-indice="${indice}">+ Sumar</button>
                <button class="btn-stock" data-accion="restar" data-indice="${indice}">- Restar</button>
            </div>
            <div class="producto-acciones">
                <button class="btn-edit" data-accion="editar" data-indice="${indice}">Editar</button>
                <button class="btn-danger" data-accion="eliminar" data-indice="${indice}">Eliminar</button>
            </div>
        `;
        contenedor.appendChild(div);
    });
}

function configurarFormulario() {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // con id_encriptado se edita el nombre, sin id se registra un producto nuevo
        const url = idInput.value
            ? '../../../backend/productos/modificar_producto.php'
            : '../../../backend/productos/productos.php';

        const data = await pedirJSON(url, { method: 'POST', body: new FormData(form) });
        mostrarMensaje(data.status, data.message);
        if (data.status === 'success') {
            limpiar();
            cargarProductos();
        }
    });

    btnCancelar.addEventListener('click', limpiar);

    // un solo listener para todos los botones de las tarjetas
    contenedor.addEventListener('click', (e) => {
        const boton = e.target.closest('button[data-accion]');
        if (!boton) return;
        const p = productosGuardados[boton.dataset.indice];
        if (!p) return;

        const accion = boton.dataset.accion;
        if (accion === 'sumar' || accion === 'restar') {
            const input = boton.closest('.item-producto').querySelector('.input-cantidad');
            modificarStock(p, input, accion === 'sumar' ? 1 : -1);
        }
        if (accion === 'editar') editar(p);
        if (accion === 'eliminar') eliminar(p);
    });
}

async function modificarStock(p, input, factor) {
    const valor = Number(input.value);
    // solo enteros positivos: el signo lo pone el boton (+ o -)
    if (!Number.isInteger(valor) || valor <= 0 || valor > 100000) {
        mostrarMensaje('error', 'La cantidad tiene que ser un número entero entre 1 y 100.000.');
        return;
    }

    const fd = new FormData();
    fd.append('id_encriptado', p.id);
    fd.append('cantidad', valor * factor);

    const data = await pedirJSON('../../../backend/productos/productos.php', { method: 'POST', body: fd });
    mostrarMensaje(data.status, data.message);

    if (data.status === 'success') {
        cargarProductos();
    }
}

function editar(p) {
    idInput.value = p.id;
    nombreInput.value = decodificar(p.nombre);
    formTitulo.textContent = 'Editar Insumo';
    btnGuardar.textContent = 'Guardar cambios';
    btnCancelar.style.display = 'inline-block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    nombreInput.focus();
}

function limpiar() {
    idInput.value = '';
    form.reset();
    formTitulo.textContent = 'Registrar Insumo';
    btnGuardar.textContent = 'Registrar';
    btnCancelar.style.display = 'none';
}

async function eliminar(p, confirmarHistorial = false) {
    if (!confirmarHistorial && !confirm(`¿Eliminar el producto "${decodificar(p.nombre)}"?`)) return;

    const fd = new FormData();
    fd.append('id_encriptado', p.id);
    if (confirmarHistorial) fd.append('confirmar_historial', '1');

    const data = await pedirJSON('../../../backend/productos/eliminar_producto.php', { method: 'POST', body: fd });

    // si el producto tiene usos registrados, el servidor pide una segunda confirmacion
    if (data.status === 'confirmar') {
        if (confirm(decodificar(data.message))) {
            eliminar(p, true);
        }
        return;
    }

    mostrarMensaje(data.status, data.message);
    if (data.status === 'success') {
        if (idInput.value === p.id) limpiar();
        cargarProductos();
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
    mensaje.textContent = decodificar(texto || '');
    // si habia un mensaje anterior, cancelamos su temporizador para que no oculte este
    clearTimeout(mensaje.temporizador);
    mensaje.temporizador = setTimeout(() => mensaje.style.display = 'none', 3500);
}
