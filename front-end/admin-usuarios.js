// verificamos que haya sesion activa con rol de admin
verificarSesion("admin", (datos) => {
    // si es valida, mostramos el nombre de usuario y cargamos el listado
    document.getElementById("nombreUsuario").textContent = datos.usuario_nombre;
    cargarUsuarios();
});

// activamos el boton de logout (logica definida en auth.js)
activarLogout();

// pedimos al backend el listado de usuarios y los mostramos en la tabla
function cargarUsuarios() {
    fetch("../backend/usuarios.php")
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {
                mostrarUsuarios(resultado.usuarios);
            } else {
                alert("Error al cargar usuarios: " + resultado.message); 
            }
        });
}

// se recibe un array de usuarios y se arman las filas de la tabla con esos datos
function mostrarUsuarios(usuarios) {
    const cuerpo = document.getElementById("cuerpoUsuarios");

    // si no hay fecha de contrato, mostramos vacio en vez de "undefined"
    cuerpo.innerHTML = usuarios.map((usuario) => `
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

// se envia el formulario, mandamos los datos al backend para crear el usuario
formUsuario.addEventListener("submit", (e) => {
    e.preventDefault();
    const datos = new FormData(formUsuario);

    fetch("../backend/usuarios.php", {
        method: "POST",
        body: datos
    })
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {
                // si el usuario se agrego bien, limpiamos el formulario y refrescamos la tabla
                formUsuario.reset();
                cargarUsuarios();
            } else {
                alert(resultado.message);
            }
        });
});

// opción de eliminar un usuario por id, pidiendo confirmacion antes por seguridad
function eliminarUsuario(id) {
    if (!confirm("¿Seguro que querés eliminar este usuario?")) {
        return;
    }

    const datos = new FormData();
    datos.append("id", id);

    fetch("../backend/eliminar_usuario.php", {
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
        });
}