verificarSesion("admin", (datos) => {
    document.getElementById("nombreUsuario").textContent = datos.usuario_nombre;
    cargarCategorias();
});

activarLogout();

function cargarCategorias() {
    fetch("../backend/categorias/listar_categorias.php")
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {
                mostrarCategorias(resultado.categorias);
            }
        })
        .catch(err => console.error("Error al listar categorias:", err));
}

function mostrarCategorias(categorias) {
    const cuerpo = document.getElementById("cuerpoCategorias");
    cuerpo.innerHTML = (categorias || []).map((categoria) => `
        <tr>
            <td>${categoria.nombre}</td>
            <td>
                <button onclick="editarCategoria('${categoria.id_encriptado || categoria.id}', '${categoria.nombre.replace(/'/g, "\\'")}')">Editar</button>
                <button onclick="eliminarCategoria('${categoria.id_encriptado || categoria.id}')">Eliminar</button>
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

formCategoria.addEventListener("submit", (e) => {
    e.preventDefault();
    const datos = new FormData(formCategoria);

    fetch("../backend/categorias/registrar_categoria.php", {
        method: "POST",
        body: datos
    })
    .then(res => res.json())
    .then(resultado => {
        if (resultado.status === "success") {
            cancelarEdicion();
            cargarCategorias();
        } else {
            alert(resultado.message);
        }
    })
    .catch(err => console.error("Error al registrar categoria:", err));
});

function editarCategoria(idEncriptado, nombre) {
    inputIdEncriptado.value = idEncriptado;
    inputNombre.value = nombre;
    tituloFormulario.textContent = "Modificar categoria";
    btnSubmit.value = "Guardar cambios";
    btnCancelar.style.display = "inline";
}

function cancelarEdicion() {
    formCategoria.reset();
    inputIdEncriptado.value = "";
    tituloFormulario.textContent = "Agregar categoria";
    btnSubmit.value = "Agregar categoria";
    btnCancelar.style.display = "none";
}

btnCancelar.addEventListener("click", cancelarEdicion);

function eliminarCategoria(idEncriptado) {
    if (!confirm("¿Seguro que queres eliminar esta categoria?")) return;

    const datos = new FormData();
    datos.append("id_encriptado", idEncriptado);

    fetch("../backend/categorias/eliminar_categoria.php", {
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
    })
    .catch(err => console.error("Error al eliminar categoria:", err));
}
