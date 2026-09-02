// verificamos que haya una sesion activa con rol de admin
verificarSesion("admin", (datos) => {
    // si es valida, mostramos el nombre de usuario y cargamos las categorias
    document.getElementById("nombreUsuario").textContent = datos.usuario_nombre;
    cargarCategorias();
});

// activamos el boton de logout (logica definida en auth.js)
activarLogout();

const formMarca = document.getElementById("formMarca");
const respuesta = document.getElementById("respuesta");

// Helper para escapar texto antes de inyectarlo en HTML
function escaparHTML(texto) {
    const div = document.createElement("div");
    div.textContent = texto;
    return div.innerHTML;
}

// Cargar las marcas al abrir la página
cargarMarcas();

// Función que pide las marcas al backend vía GET y las dibuja en la tabla
function cargarMarcas() {
    fetch("../backend/marcas.php")
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {
                mostrarMarcas(resultado.marcas);
            }
        })
        .catch(error => {
            console.error("Error al cargar marcas:", error);
        });
}

// Renderiza las filas de la tabla
function mostrarMarcas(marcas) {
    const cuerpo = document.getElementById("cuerpoMarcas");

    if (marcas.length === 0) {
        cuerpo.innerHTML = `<tr><td colspan="4">No hay marcas registradas.</td></tr>`;
        return;
    }

    cuerpo.innerHTML = marcas.map(m => `
        <tr>
            <td>${escaparHTML(String(m.id))}</td>
            <td>${escaparHTML(m.usuario)}</td>
            <td>${escaparHTML(m.hora)}</td>
            <td>${escaparHTML(m.direccion)}</td>
        </tr>
    `).join("");
}

