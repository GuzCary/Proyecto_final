document.addEventListener('DOMContentLoaded', () => {
    verificarSesion('admin', () => {
        activarLogout();
        cargarCategoriasCheckbox();
        cargarVehiculos();
        configurarFormulario();
    });
});

const form = document.getElementById('form-vehiculo');
const formTitulo = document.getElementById('form-titulo');
const btnCancelar = document.getElementById('btn-cancelar');
const contenedorVehiculos = document.getElementById('contenedor-vehiculos');
const contenedorCategorias = document.getElementById('contenedor-checkbox-categorias');
const mensaje = document.getElementById('mensaje');

let categoriasDisponibles = [];
let vehiculosGuardados = [];

async function cargarCategoriasCheckbox() {
    try {
        const res = await fetch('../../../backend/categorias/listar_categorias.php');
        const data = await res.json();
        contenedorCategorias.innerHTML = '';
        categoriasDisponibles = data.categorias || [];

        categoriasDisponibles.forEach(c => {
            const label = document.createElement('label');
            label.innerHTML = `<input type="checkbox" name="categorias[]" value="${c.id}"> ${c.nombre}`;
            contenedorCategorias.appendChild(label);
        });
    } catch (e) {}
}

async function cargarVehiculos() {
    try {
        const res = await fetch('../../../backend/vehiculos/listar_vehiculos.php');
        const data = await res.json();
        contenedorVehiculos.innerHTML = '';
        vehiculosGuardados = data.vehiculos || [];

        if (vehiculosGuardados.length === 0) {
            contenedorVehiculos.innerHTML = '<p>No hay vehículos registrados.</p>';
            return;
        }

        vehiculosGuardados.forEach(v => {
            const card = document.createElement('article');
            card.classList.add('vehiculo-admin-card');
            
            const foto = (v.imagenes && v.imagenes.length > 0) 
                ? `../../../img/${v.imagenes[0]}` 
                : 'https://via.placeholder.com/200x140?text=Sin+Foto';

            card.innerHTML = `
                <img src="${foto}" alt="${v.marca}">
                <strong>${v.marca} ${v.modelo} (${v.anio || 'N/A'})</strong>
                <span>Precio: $${v.precio}</span>
                <small>Estado: ${v.estado == 1 ? 'Disponible' : 'No disponible'}</small>
                <div class="vehiculo-card-acciones">
                    <button class="btn-edit" onclick="prepararEdicion('${v.id}')">Editar</button>
                    <button class="btn-danger" onclick="eliminar('${v.id}')">Eliminar</button>
                </div>
            `;
            contenedorVehiculos.appendChild(card);
        });
    } catch (e) {
        contenedorVehiculos.innerHTML = '<p>Error al cargar vehículos.</p>';
    }
}

function configurarFormulario() {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('id').value;
        const url = id 
            ? '../../../backend/vehiculos/modificar_vehiculo.php' 
            : '../../../backend/vehiculos/vehiculo.php';

        const res = await fetch(url, {
            method: 'POST',
            body: new FormData(form)
        });
        const data = await res.json();
        mostrarMensaje(data.status, data.message);

        if (data.status === 'success') {
            limpiar();
            cargarVehiculos();
        }
    });

    btnCancelar.addEventListener('click', limpiar);
}

function prepararEdicion(idEncriptado) {
    const v = vehiculosGuardados.find(item => item.id === idEncriptado);
    if (!v) return;

    document.getElementById('id').value = v.id;
    document.getElementById('marca').value = v.marca || '';
    document.getElementById('modelo').value = v.modelo || '';
    document.getElementById('precio').value = v.precio || '';
    document.getElementById('precioMinimo').value = v.precioMinimo || '';
    document.getElementById('anio').value = v.anio || '';
    document.getElementById('km').value = v.km || '';
    document.getElementById('potencia').value = v.potencia || '';
    document.getElementById('consumo').value = v.consumo || '';
    document.getElementById('patente').value = v.patente || '';
    document.getElementById('seguroSOA').value = v.seguroSOA || '';
    document.getElementById('seguroTerceros').value = v.seguroTerceros || '';
    document.getElementById('seguroTotal').value = v.seguroTotal || '';
    document.getElementById('estado').value = v.estado ?? 1;
    document.getElementById('descripcion').value = v.descripcion || '';
    document.getElementById('enlaceDocOficial').value = v.enlaceDocOficial || '';

    const checks = document.querySelectorAll('input[name="categorias[]"]');
    checks.forEach(chk => {
        chk.checked = v.categorias && v.categorias.some(c => c.id == chk.value);
    });

    formTitulo.textContent = 'Editar Vehículo';
    btnCancelar.style.display = 'inline-block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function limpiar() {
    form.reset();
    document.getElementById('id').value = '';
    formTitulo.textContent = 'Agregar Vehículo';
    btnCancelar.style.display = 'none';
}

async function eliminar(id) {
    if (!confirm('¿Deseas eliminar este vehículo?')) return;
    const fd = new FormData();
    fd.append('id', id);
    const res = await fetch('../../../backend/vehiculos/eliminar_vehiculo.php', {
        method: 'POST',
        body: fd
    });
    const data = await res.json();
    mostrarMensaje(data.status, data.message);
    if (data.status === 'success') cargarVehiculos();
}

function mostrarMensaje(tipo, texto) {
    mensaje.style.display = 'block';
    mensaje.style.background = tipo === 'success' ? '#d4edda' : '#f8d7da';
    mensaje.style.color = tipo === 'success' ? '#155724' : '#721c24';
    mensaje.textContent = texto;
    setTimeout(() => mensaje.style.display = 'none', 2500);
}