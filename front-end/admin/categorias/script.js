document.addEventListener('DOMContentLoaded', () => {
    verificarSesion('admin', () => {
        activarLogout();
        cargarCategorias();
        configurarFormulario();
    });
});

const form = document.getElementById('form-categoria');
const idInput = document.getElementById('id_encriptado');
const nombreInput = document.getElementById('nombre');
const btnCancelar = document.getElementById('btn-cancelar');
const formTitulo = document.getElementById('form-titulo');
const contenedor = document.getElementById('contenedor-categorias');
const mensaje = document.getElementById('mensaje');

let categoriasGuardadas = [];

async function cargarCategorias() {
    try {
        const data = await pedirJSON('../../../backend/categorias/listar_categorias.php');
        contenedor.innerHTML = '';

        if (!data.categorias || data.categorias.length === 0) {
            contenedor.innerHTML = '<p>No hay categorías registradas.</p>';
            return;
        }

        categoriasGuardadas = data.categorias;
        data.categorias.forEach((c, indice) => {
            const div = document.createElement('div');
            div.classList.add('item-row');
            div.innerHTML = `
                <span><strong>${c.nombre}</strong></span>
                <div class="item-actions">
                    <button class="btn-edit" data-accion="editar" data-indice="${indice}">Editar</button>
                    <button class="btn-danger" data-accion="eliminar" data-indice="${indice}">Eliminar</button>
                </div>
            `;
            contenedor.appendChild(div);
        });
    } catch (e) {
        contenedor.innerHTML = '<p>Error al cargar categorías.</p>';
    }
}

function configurarFormulario() {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = await pedirJSON('../../../backend/categorias/registrar_categoria.php', {
            method: 'POST',
            body: new FormData(form)
        });
        mostrarMensaje(data.status, data.message);
        if (data.status === 'success') {
            limpiar();
            cargarCategorias();
        }
    });

    btnCancelar.addEventListener('click', limpiar);

    // un solo listener para los botones de todas las categorias
    contenedor.addEventListener('click', (e) => {
        const boton = e.target.closest('button[data-accion]');
        if (!boton) return;
        const c = categoriasGuardadas[boton.dataset.indice];
        if (!c) return;
        if (boton.dataset.accion === 'editar') editar(c.id_encriptado, c.nombre);
        if (boton.dataset.accion === 'eliminar') eliminar(c.id_encriptado);
    });
}

function editar(id, nombre) {
    idInput.value = id;
    nombreInput.value = decodificar(nombre);
    formTitulo.textContent = 'Editar Categoría';
    btnCancelar.style.display = 'inline-block';
    nombreInput.focus();
}

function limpiar() {
    idInput.value = '';
    nombreInput.value = '';
    formTitulo.textContent = 'Agregar Categoría';
    btnCancelar.style.display = 'none';
}

async function eliminar(id) {
    if (!confirm('¿Deseas eliminar esta categoría?')) return;
    const fd = new FormData();
    fd.append('id_encriptado', id);
    const data = await pedirJSON('../../../backend/categorias/eliminar_categoria.php', {
        method: 'POST',
        body: fd
    });
    mostrarMensaje(data.status, data.message);
    if (data.status === 'success') cargarCategorias();
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
    mensaje.textContent = texto;
    // si habia un mensaje anterior, cancelamos su temporizador para que no oculte este
    clearTimeout(mensaje.temporizador);
    mensaje.temporizador = setTimeout(() => mensaje.style.display = 'none', 2500);
}
