document.addEventListener('DOMContentLoaded', () => {
    verificarSesion('admin', () => {
        activarLogout();
        cargarLicencias();
        configurarFormulario();
    });
});

const form = document.getElementById('form-licencia');
const selectFuncionario = document.getElementById('id_usuario');
const inputTipo = document.getElementById('tipo');
const inputVencimiento = document.getElementById('fechaVencimiento');
const formTitulo = document.getElementById('form-titulo');
const contenedor = document.getElementById('contenedor-licencias');
const mensaje = document.getElementById('mensaje');

const nombresRol = { admin: 'Administrador', user: 'Vendedor' };
const estados = {
    vigente:      { texto: 'Vigente',      clase: 'ok' },
    por_vencer:   { texto: 'Vence pronto', clase: 'alerta' },
    vencida:      { texto: 'Vencida',      clase: 'mal' },
    sin_licencia: { texto: 'Sin licencia', clase: '' }
};

let funcionarios = [];

async function cargarLicencias() {
    try {
        const res = await fetch('../../../backend/licencias/listar_licencias.php');
        const data = await res.json();
        contenedor.innerHTML = '';

        if (data.status !== 'success') {
            contenedor.innerHTML = `<p>Error: ${data.message}</p>`;
            return;
        }

        funcionarios = data.funcionarios || [];

        // llenamos el select manteniendo la seleccion actual
        const seleccionado = selectFuncionario.value;
        selectFuncionario.innerHTML = '<option value="">Seleccioná un funcionario</option>';
        funcionarios.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f.id;
            opt.innerHTML = `${f.usuario} (${nombresRol[f.rol] || f.rol})`;
            selectFuncionario.appendChild(opt);
        });
        selectFuncionario.value = seleccionado;

        if (funcionarios.length === 0) {
            contenedor.innerHTML = '<p>No hay funcionarios activos.</p>';
            return;
        }

        funcionarios.forEach((f, indice) => {
            const estado = estados[f.estado] || estados.sin_licencia;
            const detalle = f.tipo
                ? `Categoría ${f.tipo} · vence ${f.fechaVencimiento}`
                : 'No tiene licencia cargada';

            const div = document.createElement('div');
            div.classList.add('item-row');
            div.innerHTML = `
                <div>
                    <strong>${f.usuario}</strong> [${nombresRol[f.rol] || f.rol}]
                    <br><small>${detalle}</small>
                    <br><span class="etiqueta ${estado.clase}">${estado.texto}</span>
                </div>
                <div class="item-actions">
                    <button class="btn-edit" data-indice="${indice}">${f.tipo ? 'Editar' : 'Cargar'}</button>
                </div>
            `;
            contenedor.appendChild(div);
        });
    } catch (e) {
        contenedor.innerHTML = '<p>Error al cargar licencias.</p>';
    }
}

function configurarFormulario() {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('../../../backend/licencias/registrar_licencia.php', {
            method: 'POST',
            body: new FormData(form)
        });
        const data = await res.json();
        mostrarMensaje(data.status, data.message);

        if (data.status === 'success') {
            limpiar();
            cargarLicencias();
        }
    });

    document.getElementById('btn-cancelar').addEventListener('click', limpiar);

    // al elegir un funcionario en el select, mostramos su licencia actual
    selectFuncionario.addEventListener('change', () => {
        const f = funcionarios.find(x => x.id === selectFuncionario.value);
        inputTipo.value = f && f.tipo ? decodificar(f.tipo) : '';
        inputVencimiento.value = f && f.fechaVencimiento ? f.fechaVencimiento : '';
    });

    contenedor.addEventListener('click', (e) => {
        const boton = e.target.closest('button[data-indice]');
        if (!boton) return;
        const f = funcionarios[boton.dataset.indice];
        if (!f) return;

        selectFuncionario.value = f.id;
        inputTipo.value = f.tipo ? decodificar(f.tipo) : '';
        inputVencimiento.value = f.fechaVencimiento || '';
        formTitulo.textContent = `Licencia de ${decodificar(f.usuario)}`;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        inputTipo.focus();
    });
}

function limpiar() {
    form.reset();
    formTitulo.textContent = 'Cargar Licencia';
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
    setTimeout(() => mensaje.style.display = 'none', 4000);
}
