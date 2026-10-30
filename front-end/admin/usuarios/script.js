document.addEventListener('DOMContentLoaded', () => {
    verificarSesion('admin', () => {
        activarLogout();
        cargarSucursales();
        cargarUsuarios();
        configurarFormulario();
    });
});

const form = document.getElementById('form-usuario');
const idInput = document.getElementById('id_encriptado');
const usuarioInput = document.getElementById('usuario');
const contraseniaInput = document.getElementById('contrasenia');
const rolInput = document.getElementById('rol');
const sucursalInput = document.getElementById('id_sucursal');
const salarioInput = document.getElementById('salarioPorHora');
const formTitulo = document.getElementById('form-titulo');
const btnGuardar = document.getElementById('btn-guardar');
const btnCancelar = document.getElementById('btn-cancelar');
const textoPass = document.getElementById('texto-pass');
const textoSalario = document.getElementById('texto-salario');
const contenedor = document.getElementById('contenedor-usuarios');
const mensaje = document.getElementById('mensaje');

// guardamos la lista para buscar los datos al editar
let usuariosGuardados = [];

const nombresRol = { admin: 'Administrador', user: 'Vendedor', limp: 'Limpieza' };

// Carga las sucursales en el select (los valores son los ids encriptados)
async function cargarSucursales() {
    try {
        const res = await fetch('../../../backend/sucursales/listar_sucursales.php');
        const data = await res.json();
        sucursalInput.innerHTML = '';

        if (data.status !== 'success' || !data.sucursales || data.sucursales.length === 0) {
            sucursalInput.innerHTML = '<option value="">No hay sucursales (crealas en Config &gt; Sucursales)</option>';
            return;
        }

        data.sucursales.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.innerHTML = s.nombre;
            sucursalInput.appendChild(opt);
        });
    } catch (e) {
        sucursalInput.innerHTML = '<option value="">Error al cargar sucursales</option>';
    }
}

async function cargarUsuarios() {
    try {
        const res = await fetch('../../../backend/usuarios/listar_usuarios.php');
        const data = await res.json();
        contenedor.innerHTML = '';
        usuariosGuardados = data.usuarios || [];

        if (usuariosGuardados.length === 0) {
            contenedor.innerHTML = '<p>No hay usuarios registrados.</p>';
            return;
        }

        usuariosGuardados.forEach((u, indice) => {
            const div = document.createElement('div');
            div.classList.add('item-row');

            const salario = u.salarioPorHora !== null ? `$${u.salarioPorHora}/h` : 'Sin salario';

            // un admin no se puede dar de baja a si mismo, asi que no mostramos el boton
            const botonBaja = u.esYo
                ? '<small>(vos)</small>'
                : `<button class="btn-danger" data-accion="baja" data-indice="${indice}">Dar de baja</button>`;

            div.innerHTML = `
                <div>
                    <strong>${u.usuario}</strong> [${nombresRol[u.rol] || u.rol}]
                    <br><small>Sucursal: ${u.sucursal || 'N/A'} | ${salario}</small>
                    <br><small>Fecha de alta: ${u.fechaDeContrato || 'N/A'}</small>
                </div>
                <div class="item-actions">
                    <button class="btn-edit" data-accion="editar" data-indice="${indice}">Editar</button>
                    ${botonBaja}
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

    // un solo listener para los botones de todas las tarjetas
    contenedor.addEventListener('click', (e) => {
        const boton = e.target.closest('button[data-accion]');
        if (!boton) return;
        const u = usuariosGuardados[boton.dataset.indice];
        if (!u) return;

        if (boton.dataset.accion === 'editar') editar(u);
        if (boton.dataset.accion === 'baja') eliminar(u);
    });
}

function editar(u) {
    idInput.value = u.id;
    // el nombre viene escapado desde el backend, lo decodificamos para el input
    usuarioInput.value = decodificar(u.usuario);
    rolInput.value = u.rol;
    sucursalInput.value = u.idSucursal || '';
    salarioInput.value = u.salarioPorHora !== null ? u.salarioPorHora : '';

    // Al editar la contraseña pasa a ser opcional
    contraseniaInput.required = false;
    contraseniaInput.value = '';
    textoPass.textContent = '(dejar en blanco para no cambiar)';
    textoSalario.textContent = '(para aumentos usá Config > Aumentos de sueldo)';

    formTitulo.textContent = 'Editar Usuario';
    btnGuardar.textContent = 'Guardar Cambios';
    btnCancelar.style.display = 'inline-block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    usuarioInput.focus();
}

function limpiar() {
    idInput.value = '';
    form.reset();
    contraseniaInput.required = true;
    textoPass.textContent = '(obligatoria)';
    textoSalario.textContent = '(opcional)';
    formTitulo.textContent = 'Agregar Usuario';
    btnGuardar.textContent = 'Registrar';
    btnCancelar.style.display = 'none';
}

async function eliminar(u) {
    // Pedimos el motivo real de la baja para guardarlo en la base de datos
    const motivo = prompt(`Motivo de la baja de "${decodificar(u.usuario)}" (ej: Renuncia, Despido, Fin de contrato):`);
    if (!motivo || motivo.trim() === '') {
        return;
    }

    const fd = new FormData();
    fd.append('id', u.id);
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
    setTimeout(() => mensaje.style.display = 'none', 3500);
}
