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

        // Número de WhatsApp (092484684 -> 59892484684)
        const telefonoWsp = '59892484684';

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

            // Mensaje automático personalizado
            const textoMensaje = `Hola, quiero consultar información sobre el auto ${v.marca} ${v.modelo}`;
            const urlWhatsapp = `https://wa.me/${telefonoWsp}?text=${encodeURIComponent(textoMensaje)}`;

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
                    <a href="${urlWhatsapp}" target="_blank" rel="noopener noreferrer" class="btn-whatsapp" title="Consultar por WhatsApp">
                        <svg class="icono-wsp" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                        </svg>
                        <span>WhatsApp</span>
                    </a>
                    <div class="id">ID: ${v.id}</div>
                </div>
            `;

            contenedor.appendChild(card);
        });

    } catch (error) {
        contenedor.innerHTML = `<p>Error al conectar con el servidor: ${error.message}</p>`;
    }
}
