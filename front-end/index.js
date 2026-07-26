// Al cargar la pagina, pedimos al backend el listado de vehiculos disponibles y los mostramos
fetch("../backend/listar_vehiculos.php")
    .then(res => res.json())
    .then(resultado => {
        if (resultado.status === "success") {
            mostrarAutos(resultado.vehiculos);
        }
    });

// Recibe un array de vehiculos y arma las tarjetas del catalogo
function mostrarAutos(vehiculos) {
    const lista = document.getElementById("listaAutos");

    // Si no hay vehiculos, mostramos un mensaje para no dejar la seccion vacia
    if (vehiculos.length === 0) {
        lista.innerHTML = "<p>No hay vehículos disponibles por el momento.</p>";
        return;
    }

    lista.innerHTML = vehiculos.map((vehiculo) => {
        // Cada vehiculo trae un array de categorias, armamos un string con los nombres
        const nombresCategorias = vehiculo.categorias.map(c => c.nombre).join(", ");

        return `
            <article class="tarjeta-auto">
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