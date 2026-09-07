fetch("../backend/vehiculos/listar_vehiculos.php")
    .then(res => res.json())
    .then(resultado => {
        if (resultado.status === "success") mostrarAutos(resultado.vehiculos);
    });
function mostrarAutos(vehiculos) {
    const lista = document.getElementById("listaAutos");
    if (!vehiculos || vehiculos.length === 0) {
        lista.innerHTML = "<p>No hay vehículos disponibles por el momento.</p>";
        return;
    }
    lista.innerHTML = vehiculos.map((vehiculo) => {
        const nombresCategorias = (vehiculo.categorias || []).map(c => c.nombre).join(", ");
        let imagenesHTML = (vehiculo.imagenes && vehiculo.imagenes.length > 0)
            ? `<div class="galeria-auto">` + vehiculo.imagenes.map(img => `<img src="../img/${img}" alt="Foto" style="width:100%; max-width:200px; margin:4px;">`).join("") + `</div>`
            : `<p><em>Sin fotos</em></p>`;
        return `
            <article class="tarjeta-auto">
                ${imagenesHTML}
                <h3>${vehiculo.marca} ${vehiculo.modelo}</h3>
                <p class="anio">${vehiculo.anio ?? ""}</p>
                <p class="descripcion">${vehiculo.descripcion ?? ""}</p>
                <p class="categorias">${nombresCategorias}</p>
                <p class="precio">$${vehiculo.precio}</p>
                <p class="sucursal">${vehiculo.sucursal}</p>
            </article>
        `;
    }).join("");
}
