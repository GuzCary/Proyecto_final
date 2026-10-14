document.addEventListener('DOMContentLoaded', () => {
    verificarSesion('admin', () => {
        activarLogout();
        cargarUsuarios();
        configurarFormulario();
    });
});

const form = document.getElementById('form-usuario');
const idInput = document.getElementById('id_encriptado');
const usuarioInput = document.getElementById('usuario');
const contraseniaInput = document.getElementById('contrasenia');
const rolInput = document.getElementById('rol');
const formTitulo = document.getElementById('form-titulo');
const btnGuardar = document.getElementById('btn-guardar');
const btnCancelar = document.getElementById('btn-cancelar');
const textoPass = document.getElementById('texto-pass');
const contenedor = document.getElementById('contenedor-usuarios');
const mensaje = document.getElementById('mensaje');

async function cargarUsuarios() {
    try {
        const res = await fetch('../../../backend/usuarios/listar_usuarios.php');
        const data = await res.json();
        contenedor.innerHTML = '';

        if (!data.usuarios || data.usuarios.length === 0) {
            contenedor.innerHTML = '<p>No hay usuarios registrados.</p>';
            return;
        }

        data.usuarios.forEach(u => {
            const div = document.createElement('div');
            div.classList.add('item-row');
            div.innerHTML = `
                <div>
                    <strong>${u.usuario}</strong> [Rol: ${u.rol}]
                    <br><small>Fecha de alta: ${u.fechaDeContrato || 'N/A'}</small>
                </div>
                <div class="item-actions">
                    <button class="btn-edit" onclick="editar('${u.id}', '${u.usuario}', '${u.rol}')">Editar</button>
                    <button class="btn-danger" onclick="eliminar('${u.id}')">Dar de baja</button>
                </div>
            `;
            contenedor.appendChild(div);
        });
    } catch (e) {
        contenedor.innerHTML = '<p>Error al cargar usuarios.</p>';
    }
}

function configurarFormulario() {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Si hay id_encriptado enviamos a modificar, si no a agregar
        const url = idInput.value
            ? '../../../backend/usuarios/modificar_usuario.php'
            : '../../../backend/usuarios/agregar_usuario.php';

        const res = await fetch(url, {
            method: 'POST',
            body: new FormData(form)
        });
        const data = await res.json();
        mostrarMensaje(data.status, data.message);

        if (data.status === 'success') {
            limpiar();
            cargarUsuarios();
        }
    });

    btnCancelar.addEventListener('click', limpiar);
}

function editar(id, usuario, rol) {
    idInput.value = id;
    usuarioInput.value = usuario;
    rolInput.value = rol;

    // Al editar la contraseña pasa a ser opcional
    contraseniaInput.required = false;
    contraseniaInput.value = '';
    textoPass.textContent = '(dejar en blanco para no cambiar)';

    formTitulo.textContent = 'Editar Usuario';
    btnGuardar.textContent = 'Guardar Cambios';
    btnCancelar.style.display = 'inline-block';
    usuarioInput.focus();
}

function limpiar() {
    idInput.value = '';
    form.reset();
    contraseniaInput.required = true;
    textoPass.textContent = '(obligatoria)';
    formTitulo.textContent = 'Agregar Usuario';
    btnGuardar.textContent = 'Registrar';
    btnCancelar.style.display = 'none';
}

async function eliminar(id) {
    // Pedimos el motivo real de la baja para guardarlo en la base de datos
    const motivo = prompt('Ingresa el motivo de la baja (ej: Renuncia, Despido, Fin de contrato):');
    if (!motivo || motivo.trim() === '') {
        return;
    }

    const fd = new FormData();
    fd.append('id', id);
    fd.append('tipo', motivo.trim());

    const res = await fetch('../../../backend/usuarios/eliminar_usuario.php', {
        method: 'POST',
        body: fd
    });
    const data = await res.json();
    mostrarMensaje(data.status, data.message);

    if (data.status === 'success') {
        cargarUsuarios();
    }
}

function mostrarMensaje(tipo, texto) {
    mensaje.style.display = 'block';
    mensaje.style.background = tipo === 'success' ? '#d4edda' : '#f8d7da';
    mensaje.style.color = tipo === 'success' ? '#155724' : '#721c24';
    mensaje.textContent = texto;
    setTimeout(() => mensaje.style.display = 'none', 2500);
}
