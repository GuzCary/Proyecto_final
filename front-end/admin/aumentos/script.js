document.addEventListener('DOMContentLoaded', () => {
    verificarSesion('admin', () => {
        activarLogout();
        cargarEmpleados();
        cargarAumentos();
        configurarFormulario();
    });
});

const form = document.getElementById('form-aumento');
const selectEmpleado = document.getElementById('id_usuario');
const selectTipo = document.getElementById('tipo');
const inputValor = document.getElementById('valor');
const vistaPrevia = document.getElementById('vista-previa');
const btnGuardar = document.getElementById('btn-guardar');
const tabla = document.getElementById('tabla-aumentos');
const mensaje = document.getElementById('mensaje');

const nombresRol = { admin: 'Administrador', user: 'Vendedor', limp: 'Limpieza' };

// guardamos los empleados para saber el salario actual de cada uno
let empleados = [];

function formatoPlata(n) {
    return '$' + Number(n).toLocaleString('es-UY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// carga los empleados activos (sin incluir al admin logueado)
async function cargarEmpleados() {
    try {
        const res = await fetch('../../../backend/usuarios/listar_usuarios.php');
        const data = await res.json();
        empleados = (data.usuarios || []).filter(u => !u.esYo);

        selectEmpleado.innerHTML = '<option value="">Seleccioná un empleado</option>';
        empleados.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            const salario = u.salarioPorHora !== null ? `${formatoPlata(u.salarioPorHora)}/h` : 'sin salario';
            opt.innerHTML = `${u.usuario} (${nombresRol[u.rol] || u.rol}) - ${salario}`;
            selectEmpleado.appendChild(opt);
        });
        actualizarVistaPrevia();
    } catch (e) {
        selectEmpleado.innerHTML = '<option value="">Error al cargar empleados</option>';
    }
}

// muestra cuanto quedaria cobrando el empleado con el aumento elegido
function actualizarVistaPrevia() {
    const emp = empleados.find(u => u.id === selectEmpleado.value);
    btnGuardar.disabled = false;

    if (!emp) {
        vistaPrevia.textContent = 'Elegí un empleado para ver su salario actual.';
        return;
    }

    if (emp.salarioPorHora === null) {
        vistaPrevia.textContent = 'Este empleado no tiene salario por hora cargado. Asignáselo primero desde Usuarios > Editar.';
        btnGuardar.disabled = true;
        return;
    }

    const actual = emp.salarioPorHora;
    const valor = parseFloat(inputValor.value);

    if (isNaN(valor) || valor <= 0) {
        vistaPrevia.textContent = `Salario actual: ${formatoPlata(actual)} por hora.`;
        return;
    }

    const nuevo = selectTipo.value === 'Porcentaje' ? actual * (1 + valor / 100) : actual + valor;
    vistaPrevia.textContent = `Salario actual: ${formatoPlata(actual)}/h  →  nuevo salario: ${formatoPlata(nuevo)}/h`;
}

async function cargarAumentos() {
    try {
        const res = await fetch('../../../backend/aumentos/listar_aumentos.php');
        const data = await res.json();
        tabla.innerHTML = '';

        if (data.status !== 'success') {
            tabla.innerHTML = `<tr><td colspan="6">Error: ${data.message}</td></tr>`;
            return;
        }

        if (!data.aumentos || data.aumentos.length === 0) {
            tabla.innerHTML = '<tr><td colspan="6" class="texto-vacio">No hay aumentos registrados.</td></tr>';
            return;
        }

        data.aumentos.forEach(a => {
            const valor = a.tipo === 'Porcentaje' ? `+${a.valor}%` : `+${formatoPlata(a.valor)}/h`;
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td>${a.fecha}</td>
                <td><strong>${a.empleado}</strong></td>
                <td>${a.tipo}</td>
                <td class="num">${valor}</td>
                <td class="num">${a.salarioActual !== null ? formatoPlata(a.salarioActual) + '/h' : '-'}</td>
                <td>${a.administrador}</td>
            `;
            tabla.appendChild(fila);
        });
    } catch (e) {
        tabla.innerHTML = '<tr><td colspan="6">Error al cargar aumentos.</td></tr>';
    }
}

function configurarFormulario() {
    selectEmpleado.addEventListener('change', actualizarVistaPrevia);
    selectTipo.addEventListener('change', actualizarVistaPrevia);
    inputValor.addEventListener('input', actualizarVistaPrevia);

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!confirm(`¿Confirmar el aumento?\n${vistaPrevia.textContent}`)) return;

        const res = await fetch('../../../backend/aumentos/registrar_aumento.php', {
            method: 'POST',
            body: new FormData(form)
        });
        const data = await res.json();
        mostrarMensaje(data.status, data.message);

        if (data.status === 'success') {
            form.reset();
            // recargamos los empleados para que muestren el salario nuevo
            cargarEmpleados();
            cargarAumentos();
        }
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
    setTimeout(() => mensaje.style.display = 'none', 5000);
}
