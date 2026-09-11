document.addEventListener('DOMContentLoaded', () => {
    verificarSesion('admin', () => {
        activarLogout();
        cargarUsuarios();
        configurarFormulario();
    });
});

const form = document.getElementById('form-usuario');
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
                <button class="btn-danger" onclick="eliminar('${u.id}')">Eliminar</button>
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
        const res = await fetch('../../../backend/usuarios/agregar_usuario.php', {
            method: 'POST',
            body: new FormData(form)
        });
        const data = await res.json();
        mostrarMensaje(data.status, data.message);
        if (data.status === 'success') {
            form.reset();
            cargarUsuarios();
        }
    });
}

async function eliminar(id) {
    if (!confirm('¿Deseas eliminar este usuario?')) return;
    const fd = new FormData();
    fd.append('id', id);
    const res = await fetch('../../../backend/usuarios/eliminar_usuario.php', {
        method: 'POST',
        body: fd
    });
    const data = await res.json();
    mostrarMensaje(data.status, data.message);
    if (data.status === 'success') cargarUsuarios();
}

function mostrarMensaje(tipo, texto) {
    mensaje.style.display = 'block';
    mensaje.style.background = tipo === 'success' ? '#d4edda' : '#f8d7da';
    mensaje.style.color = tipo === 'success' ? '#155724' : '#721c24';
    mensaje.textContent = texto;
    setTimeout(() => mensaje.style.display = 'none', 2500);
}