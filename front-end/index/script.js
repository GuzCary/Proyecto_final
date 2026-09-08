document.addEventListener('DOMContentLoaded', () => {
    cargarVehiculos();
});

async function cargarVehiculos() {
    const contenedor = document.getElementById('contenedor-vehiculos');
    

    try {
        const respuesta = await fetch('../../backend/vehiculos/listar_vehiculos.php');
        const data = await respuesta.json();

        if (data.status !== 'success') {
            contenedor.innerHTML = `<p>Error: ${data.message}</p>`;
            return;
        

        
        contenedor.innerHTML = '';

        if (data.vehiculos.length === 0) {
            contenedor.innerHTML = '<p>No hay vehículos registrados en el sistema.</p>';
            return;
        }

        data.vehiculos.forEach(v => {
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
        });}

    } catch (error) {
        contenedor.innerHTML = `<p>Error al conectar con el servidor: ${error.message}</p>`;
    }
}