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
            }
        });
}

// acá recibe un array de las categorias y las muestra como filas de la tabla
function mostrarCategorias(categorias) {
    const cuerpo = document.getElementById("cuerpoCategorias");

    // generamos una fila para cada categoria, con botones para editar y eliminar
    cuerpo.innerHTML = categorias.map((categoria) => `
        <tr>
            <td>${categoria.nombre}</td>
            <td>
                <button onclick="editarCategoria('${categoria.id_encriptado}', '${categoria.nombre.replace(/'/g, "\\'")}')">Editar</button>
                <button onclick="eliminarCategoria('${categoria.id_encriptado}')">Eliminar</button>
            </td>
        </tr>
    `).join("");
}

const formCategoria = document.getElementById("formCategoria");
const inputIdEncriptado = document.getElementById("idEncriptado");
const inputNombre = document.getElementById("catNombre");
const tituloFormulario = document.getElementById("tituloFormulario");
const btnSubmit = document.getElementById("btnSubmit");
const btnCancelar = document.getElementById("btnCancelar");

// al enviar el formulario, mandamos los datos al backend para crear o actualizar la categoria
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
                cancelarEdicion();
                cargarCategorias();
            }
        });
});

// carga los datos de la categoria en el formulario para editarla
function editarCategoria(idEncriptado, nombre) {
    inputIdEncriptado.value = idEncriptado;
    inputNombre.value = nombre;
    tituloFormulario.textContent = "Modificar categoría";
    btnSubmit.value = "Guardar cambios";
    btnCancelar.style.display = "inline";
}

// limpia el formulario y vuelve al modo agregar
function cancelarEdicion() {
    formCategoria.reset();
    inputIdEncriptado.value = "";
    tituloFormulario.textContent = "Agregar categoría";
    btnSubmit.value = "Agregar categoría";
    btnCancelar.style.display = "none";
}

btnCancelar.addEventListener("click", cancelarEdicion);

// elimina una categoria por su id encriptado, por seguridad, pedimos confirmacion antes
function eliminarCategoria(idEncriptado) {
    if (!confirm("¿Seguro que querés eliminar esta categoría?")) {
        return;
    }

    const datos = new FormData();
    datos.append("id_encriptado", idEncriptado);

    fetch("../backend/eliminar_categoria.php", {
        method: "POST",
        body: datos
    })
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {
                cargarCategorias();
            }
        });
}