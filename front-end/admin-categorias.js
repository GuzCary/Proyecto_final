// verificamos que haya una sesion activa con rol de admin
verificarSesion("admin", (datos) => {
    // si es valida, mostramos el nombre de usuario y cargamos las categorias
    document.getElementById("nombreUsuario").textContent = datos.usuario_nombre;
    cargarCategorias();
});

// activamos el boton de logout (logica definida en auth.js)
activarLogout();

// pedimos al backend desde "categorias.php" la lista de categorias y las mostramos en la tabla
function cargarCategorias() {
    fetch("../backend/categorias.php")
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {
                mostrarCategorias(resultado.categorias);
            } else {
                alert("Error al cargar categorias: " + resultado.message);
            }
        });
}

// acá recibe un array de las categorias y las muestra como filas de la tabla
function mostrarCategorias(categorias) {
    const cuerpo = document.getElementById("cuerpoCategorias");

    // generamos una fila para cada categoria, acompañado de un boton para eliminarla
    cuerpo.innerHTML = categorias.map((categoria) => `
        <tr>
            <td>${categoria.id}</td>
            <td>${categoria.nombre}</td>
            <td><button onclick="eliminarCategoria(${categoria.id})">Eliminar</button></td>
        </tr>
    `).join("");
}

const formCategoria = document.getElementById("formCategoria");

// al enviar el formulario, mandamos los datos al backend para crear la categoria
formCategoria.addEventListener("submit", (e) => {
    e.preventDefault();
    const datos = new FormData(formCategoria);

    fetch("../backend/categorias.php", {
        method: "POST",
        body: datos
    })
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {

                 // si la categoria se agregó bien, limpiamos el formulario y refrescamos la tabla
                formCategoria.reset();
                cargarCategorias();
            } else {
                alert(resultado.message);
            }
        });
});

// elimina una categoria por su id, por seguridad, pedimos confirmacion antes
function eliminarCategoria(id) {
    if (!confirm("¿Seguro que querés eliminar esta categoría?")) {
        return;
    }

    const datos = new FormData();
    datos.append("id", id);

    fetch("../backend/eliminar_categoria.php", {
        method: "POST",
        body: datos
    })
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {
                cargarCategorias();
            } else {
                alert(resultado.message);
            }
        });
}