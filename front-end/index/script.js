document.addEventListener('DOMContentLoaded', () => {
    cargarCategoriasFiltro();
    cargarVehiculos();
    configurarFiltros();
});

// Carga las categorías existentes en el select del buscador
async function cargarCategoriasFiltro() {
    try {
        const res = await fetch('../../backend/categorias/listar_categorias.php');
        const data = await res.json();
        const select = document.getElementById('filtro-categoria');

        if (data.categorias && data.categorias.length > 0) {
            data.categorias.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id_encriptado;
                opt.textContent = c.nombre;
                select.appendChild(opt);
            });
        }
    } catch (e) {
        console.error('Error al cargar categorías en filtro:', e);
    }
}

// Configura los eventos de los botones y la tecla Enter
function configurarFiltros() {
    const btnBuscar = document.getElementById('btn-buscar');
    const btnLimpiar = document.getElementById('btn-limpiar');
    const inputBusqueda = document.getElementById('filtro-busqueda');

    btnBuscar.addEventListener('click', aplicarFiltros);

    // Permite presionar Enter en el input de texto para buscar directamente
    inputBusqueda.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') {
            aplicarFiltros();
        }
    });

    btnLimpiar.addEventListener('click', () => {
        document.getElementById('filtro-busqueda').value = '';
        document.getElementById('filtro-categoria').value = '';
        document.getElementById('filtro-precio').value = '';
        cargarVehiculos();
    });
}

function aplicarFiltros() {
    const busqueda = document.getElementById('filtro-busqueda').value.trim();
    const categoria = document.getElementById('filtro-categoria').value;
    const precio = document.getElementById('filtro-precio').value;

    const params = new URLSearchParams();
    if (busqueda !== '') params.append('busqueda', busqueda);
    if (categoria !== '') params.append('categoria', categoria);
    if (precio !== '') params.append('precio_max', precio);

    const queryString = params.toString() ? '?' + params.toString() : '';
    cargarVehiculos(queryString);
}

async function cargarVehiculos(query = '') {
    const contenedor = document.getElementById('contenedor-vehiculos');

    try {
        const respuesta = await fetch('../../backend/vehiculos/listar_vehiculos.php' + query);
        const data = await respuesta.json();

        if (data.status !== 'success') {
            contenedor.innerHTML = `<p>Error: ${data.message}</p>`;
            return;
        }

        contenedor.innerHTML = '';

        // Filtramos para mostrar solo los no vendidos
        const disponibles = (data.vehiculos || []).filter(v => v.vendido == 0);

        if (disponibles.length === 0) {
            contenedor.innerHTML = '<p>No se encontraron vehículos con los criterios seleccionados.</p>';
            return;
        }

        disponibles.forEach(v => {
            const card = document.createElement('article');
            card.classList.add('vehiculo-card');

            let htmlImagenes = '<p>Sin imágenes</p>';
            if (v.imagenes && v.imagenes.length > 0) {
                htmlImagenes = v.imagenes.map(img =>
                    `<img src="../../img/${img}" alt="${v.marca} ${v.modelo}">`
                ).join('');
            }

            let htmlCategorias = '<span class="categoria-tag">Sin categoría</span>';
            if (v.categorias && v.categorias.length > 0) {
                htmlCategorias = v.categorias.map(cat =>
                    `<span class="categoria-tag">${cat.nombre}</span>`
                ).join('');
            }

            card.innerHTML = `
                <div class="vehiculo-imagenes">
                    ${htmlImagenes}
                </div>

                <div class="vehiculo-header">
                    <h2>${v.marca} ${v.modelo}</h2>
                    <strong>${v.estado}</strong>
                </div>

                <p><strong>Descripción:</strong> ${v.descripcion || 'Sin descripción'}</p>

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
                    <div><strong>Doc. Oficial:</strong> ${v.enlaceDocOficial ? `<a href="${v.enlaceDocOficial}" target="_blank">Ver</a>` : 'N/A'}</div>
                </div>

                <div class="vehiculo-footer">
                    <div>
                        <strong>Precio: $${v.precio}</strong>
                    </div>
                    <div class="id">ID: ${v.id}</div>
                </div>
            `;

            contenedor.appendChild(card);
        });

    } catch (error) {
        contenedor.innerHTML = `<p>Error al conectar con el servidor: ${error.message}</p>`;
    }
}
