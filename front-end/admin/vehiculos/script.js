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
const seccionFotosExistentes = document.getElementById('seccion-fotos-existentes');
const contenedorImagenesExistentes = document.getElementById('contenedor-imagenes-existentes');
const mensaje = document.getElementById('mensaje');

let vehiculosGuardados = [];

async function cargarCategoriasCheckbox() {
    try {
        const res = await fetch('../../../backend/categorias/listar_categorias.php');
        const data = await res.json();
        contenedorCategorias.innerHTML = '';

        (data.categorias || []).forEach(c => {
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

            // 1. Scroll lateral de imágenes
            let htmlImagenes = '<p style="padding: 10px; font-size: 13px; color: #777;">Sin imágenes</p>';
            if (v.imagenes && v.imagenes.length > 0) {
                htmlImagenes = v.imagenes.map(img =>
                    `<img src="../../../img/${img}" alt="${v.marca} ${v.modelo}">`
                ).join('');
            }

            // 2. Categorías
            let htmlCategorias = '<span class="categoria-tag">Sin categoría</span>';
            if (v.categorias && v.categorias.length > 0) {
                htmlCategorias = v.categorias.map(cat =>
                    `<span class="categoria-tag">${cat.nombre}</span>`
                ).join('');
            }

            // 3. Toda la información en la carta
            card.innerHTML = `
                <div class="vehiculo-imagenes">
                    ${htmlImagenes}
                </div>

                <div class="vehiculo-header">
                    <h2>${v.marca} ${v.modelo}</h2>
                    <span class="estado-tag">Estado: ${v.estado !== null ? v.estado : 'N/A'}/10</span>
                </div>

                <p style="font-size: 13px;"><strong>Descripción:</strong> ${v.descripcion || 'Sin descripción'}</p>

                <div class="vehiculo-categorias">
                    ${htmlCategorias}
                </div>

                <div class="vehiculo-detalles">
                    <div><strong>Año:</strong> ${v.anio || 'N/A'}</div>
                    <div><strong>Kilómetros:</strong> ${v.km !== null ? v.km + ' km' : 'N/A'}</div>
                    <div><strong>Potencia:</strong> ${v.potencia !== null ? v.potencia + ' HP' : 'N/A'}</div>
                    <div><strong>Consumo:</strong> ${v.consumo !== null ? v.consumo + ' L/100km' : 'N/A'}</div>
                    <div><strong>Patente anual:</strong> $${v.patente || 0}</div>
                    <div><strong>Sucursal:</strong> ${v.sucursal || 'N/A'}</div>
                    <div><strong>Seguro SOA:</strong> $${v.seguroSOA || 0}</div>
                    <div><strong>Seguro Terceros:</strong> $${v.seguroTerceros || 0}</div>
                    <div><strong>Seguro Total:</strong> $${v.seguroTotal || 0}</div>
                    <div><strong>Doc. Oficial:</strong> ${v.enlaceDocOficial ? `<a href="${v.enlaceDocOficial}" target="_blank">Ver Enlace</a>` : 'N/A'}</div>
                </div>

                <div class="vehiculo-precios">
                    <span><strong>Precio:</strong> $${v.precio}</span>
                    <span><strong>Mínimo:</strong> $${v.precioMinimo || 'N/A'}</span>
                </div>

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

    // Completamos los campos del formulario
    document.getElementById('id').value = v.id;
    document.getElementById('marca').value = v.marca || '';
    document.getElementById('modelo').value = v.modelo || '';
    document.getElementById('precio').value = v.precio || '';
    document.getElementById('precioMinimo').value = v.precioMinimo || '';
    document.getElementById('estado').value = v.estado !== null ? v.estado : 10;
    document.getElementById('anio').value = v.anio || '';
    document.getElementById('km').value = v.km || '';
    document.getElementById('potencia').value = v.potencia || '';
    document.getElementById('consumo').value = v.consumo || '';
    document.getElementById('patente').value = v.patente || '';
    document.getElementById('seguroSOA').value = v.seguroSOA || '';
    document.getElementById('seguroTerceros').value = v.seguroTerceros || '';
    document.getElementById('seguroTotal').value = v.seguroTotal || '';
    document.getElementById('descripcion').value = v.descripcion || '';
    document.getElementById('enlaceDocOficial').value = v.enlaceDocOficial || '';

    // Checkboxes de categorías
    const checks = document.querySelectorAll('input[name="categorias[]"]');
    checks.forEach(chk => {
        chk.checked = v.categorias && v.categorias.some(c => c.id == chk.value);
    });

    // Imágenes actuales con checkbox para eliminarlas
    contenedorImagenesExistentes.innerHTML = '';
    if (v.imagenes && v.imagenes.length > 0) {
        seccionFotosExistentes.style.display = 'block';
        v.imagenes.forEach(img => {
            const div = document.createElement('div');
            div.classList.add('foto-item-eliminar');
            div.innerHTML = `
                <img src="../../../img/${img}" alt="Foto">
                <label>
                    <input type="checkbox" name="eliminarImagenes[]" value="${img}"> Eliminar
                </label>
            `;
            contenedorImagenesExistentes.appendChild(div);
        });
    } else {
        seccionFotosExistentes.style.display = 'none';
    }

    formTitulo.textContent = 'Editar Vehículo';
    btnCancelar.style.display = 'inline-block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function limpiar() {
    form.reset();
    document.getElementById('id').value = '';
    seccionFotosExistentes.style.display = 'none';
    contenedorImagenesExistentes.innerHTML = '';
    formTitulo.textContent = 'Agregar Vehículo';
    btnCancelar.style.display = 'none';
}

async function eliminar(id) {
    if (!confirm('¿Deseas eliminar este vehículo por completo?')) return;
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
