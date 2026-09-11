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

async function cargarCategorias() {
    try {
        const res = await fetch('../../../backend/categorias/listar_categorias.php');
        const data = await res.json();
        contenedor.innerHTML = '';

        if (!data.categorias || data.categorias.length === 0) {
            contenedor.innerHTML = '<p>No hay categorías registradas.</p>';
            return;
        }

        data.categorias.forEach(c => {
            const div = document.createElement('div');
            div.classList.add('item-row');
            div.innerHTML = `
                <span><strong>${c.nombre}</strong></span>
                <div class="item-actions">
                    <button class="btn-edit" onclick="editar('${c.id_encriptado}', '${c.nombre}')">Editar</button>
                    <button class="btn-danger" onclick="eliminar('${c.id_encriptado}')">Eliminar</button>
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
        const res = await fetch('../../../backend/categorias/registrar_categoria.php', {
            method: 'POST',
            body: new FormData(form)
        });
        const data = await res.json();
        mostrarMensaje(data.status, data.message);
        if (data.status === 'success') {
            limpiar();
            cargarCategorias();
        }
    });

    btnCancelar.addEventListener('click', limpiar);
}

function editar(id, nombre) {
    idInput.value = id;
    nombreInput.value = nombre;
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
    const res = await fetch('../../../backend/categorias/eliminar_categoria.php', {
        method: 'POST',
        body: fd
    });
    const data = await res.json();
    mostrarMensaje(data.status, data.message);
    if (data.status === 'success') cargarCategorias();
}

function mostrarMensaje(tipo, texto) {
    mensaje.style.display = 'block';
    mensaje.style.background = tipo === 'success' ? '#d4edda' : '#f8d7da';
    mensaje.style.color = tipo === 'success' ? '#155724' : '#721c24';
    mensaje.textContent = texto;
    setTimeout(() => mensaje.style.display = 'none', 2500);
}