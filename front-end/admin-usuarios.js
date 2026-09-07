verificarSesion("admin", (datos) => {
    document.getElementById("nombreUsuario").textContent = datos.usuario_nombre;
    cargarUsuarios();
});

activarLogout();

function cargarUsuarios() {
    fetch("../backend/usuarios/agregar_usuario.php")
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {
                mostrarUsuarios(resultado.usuarios);
            }
        })
        .catch(err => console.error("Error al cargar usuarios:", err));
}

function mostrarUsuarios(usuarios) {
    const cuerpo = document.getElementById("cuerpoUsuarios");
    cuerpo.innerHTML = (usuarios || []).map((usuario) => `
        <tr>
            <td>${usuario.id}</td>
            <td>${usuario.usuario}</td>
            <td>${usuario.rol}</td>
            <td>${usuario.fechaDeContrato ?? ""}</td>
            <td><button onclick="eliminarUsuario(${usuario.id})">Eliminar</button></td>
        </tr>
    `).join("");
}

const formUsuario = document.getElementById("formUsuario");

formUsuario.addEventListener("submit", (e) => {
    e.preventDefault();
    const datos = new FormData(formUsuario);

    fetch("../backend/usuarios/agregar_usuario.php", {
        method: "POST",
        body: datos
    })
    .then(res => res.json())
    .then(resultado => {
        if (resultado.status === "success") {
            formUsuario.reset();
            cargarUsuarios();
        } else {
            alert(resultado.message);
        }
    })
    .catch(err => console.error("Error al agregar usuario:", err));
});

function eliminarUsuario(id) {
    if (!confirm("¿Seguro que queres eliminar este usuario?")) return;

    const datos = new FormData();
    datos.append("id", id);

    fetch("../backend/usuarios/eliminar_usuario.php", {
        method: "POST",
        body: datos
    })
    .then(res => res.json())
    .then(resultado => {
        if (resultado.status === "success") {
            cargarUsuarios();
        } else {
            alert(resultado.message);
        }
    })
    .catch(err => console.error("Error al eliminar usuario:", err));
}
