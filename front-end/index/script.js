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

    // si escriben un signo menos en el precio, lo sacamos en el momento
    const inputPrecio = document.getElementById('filtro-precio');
    inputPrecio.addEventListener('input', () => {
        if (inputPrecio.value.includes('-') || Number(inputPrecio.value) < 0) {
            inputPrecio.value = inputPrecio.value.replace(/-/g, '');
        }
    });
    inputPrecio.addEventListener('keydown', (e) => {
        if (e.key === '-' || e.key === 'e' || e.key === 'E' || e.key === '+') {
            e.preventDefault();
        }
    });

    btnLimpiar.addEventListener('click', () => {
        mostrarAvisoFiltro('');
        document.getElementById('filtro-busqueda').value = '';
        document.getElementById('filtro-categoria').value = '';
        document.getElementById('filtro-precio').value = '';
        cargarVehiculos();
    });
}

// número de WhatsApp de la automotora (Uruguay, 092484684)
const WHATSAPP_NUMERO = '59892484684';

// muestra un aviso arriba del listado (por ejemplo si el filtro es invalido)
function mostrarAvisoFiltro(texto) {
    const aviso = document.getElementById('aviso-filtro');
    aviso.textContent = texto;
    aviso.style.display = texto ? 'block' : 'none';
}

function aplicarFiltros() {
    const busqueda = document.getElementById('filtro-busqueda').value.trim();
    const categoria = document.getElementById('filtro-categoria').value;
    const inputPrecio = document.getElementById('filtro-precio');
    const precio = inputPrecio.value.trim();

    // el precio maximo tiene que ser un numero positivo (no se aceptan negativos)
    // (si el navegador no puede leer el numero, por ejemplo "-" solo, el input queda invalido)
    if (inputPrecio.validity.badInput || (precio !== '' && (isNaN(Number(precio)) || Number(precio) < 0))) {
        mostrarAvisoFiltro('El precio máximo tiene que ser un número positivo.');
        inputPrecio.focus();
        return;
    }
    if (busqueda.length > 100) {
        mostrarAvisoFiltro('La búsqueda no puede superar los 100 caracteres.');
        return;
    }
    mostrarAvisoFiltro('');

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
                    <div><strong>Doc. Oficial:</strong> ${esEnlaceSeguro(v.enlaceDocOficial) ? `<a href="${v.enlaceDocOficial}" target="_blank" rel="noopener noreferrer">Ver</a>` : 'N/A'}</div>
                </div>

                <div class="vehiculo-footer">
                    <div>
                        <strong>Precio: $${v.precio}</strong>
                    </div>
                    <a class="btn-whatsapp" href="${enlaceWhatsApp(v)}" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        WhatsApp
                    </a>
                </div>
            `;

            contenedor.appendChild(card);
        });

    } catch (error) {
        contenedor.innerHTML = `<p>Error al conectar con el servidor: ${error.message}</p>`;
    }
}

// arma el enlace de WhatsApp con el mensaje automatico del vehiculo
function enlaceWhatsApp(v) {
    const texto = decodificarTexto(`Hola, quiero consultar información sobre el auto ${v.marca} ${v.modelo}`);
    return `https://wa.me/${WHATSAPP_NUMERO}?text=${encodeURIComponent(texto)}`;
}

// solo se muestran enlaces http o https (nunca "javascript:...")
function esEnlaceSeguro(url) {
    return typeof url === 'string' && /^https?:\/\//i.test(decodificarTexto(url));
}

// convierte entidades HTML (&amp; &quot; ...) de vuelta a texto normal
function decodificarTexto(texto) {
    const t = document.createElement('textarea');
    t.innerHTML = texto;
    return t.value;
}
