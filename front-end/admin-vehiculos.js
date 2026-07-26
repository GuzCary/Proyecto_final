// Verificamos que haya sesion activa con rol de admin
verificarSesion("admin", (datos) => {
    // Si es valida, mostramos el nombre de usuario y cargamos vehiculos y categorias
    document.getElementById("nombreUsuario").textContent = datos.usuario_nombre;
    cargarVehiculos();
    cargarCategorias();
});

// Activamos el boton de logout (logica definida en auth.js)
activarLogout();

// Se pide al backend el listado de vehiculos y los muestra en la tabla
function cargarVehiculos() {
    fetch("../backend/listar_vehiculos.php")
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {
                mostrarVehiculos(resultado.vehiculos);
            } else {
                alert("Error al cargar vehiculos: " + resultado.message);
            }
        });
}

// Recibe un array de vehiculos y arma las filas de la tabla con esos datos
function mostrarVehiculos(vehiculos) {
    const cuerpo = document.getElementById("cuerpoVehiculos");

    cuerpo.innerHTML = vehiculos.map((vehiculo) => {
        // Cada vehiculo trae un array de categorias, armamos un string con los nombres
        const nombresCategorias = vehiculo.categorias.map(c => c.nombre).join(", ");

        return `
            <tr>
                <td>${vehiculo.id}</td>
                <td>${vehiculo.marca}</td>
                <td>${vehiculo.modelo}</td>
                <td>${vehiculo.anio ?? ""}</td>
                <td>${vehiculo.precio}</td>
                <td>${vehiculo.sucursal}</td>
                <td>${nombresCategorias}</td>
                <td><button onclick="eliminarVehiculo(${vehiculo.id})">Eliminar</button></td>
            </tr>
        `;
    }).join("");
}

// Pide al backend el listado de categorias y las carga como opciones en el select
function cargarCategorias() {
    fetch("../backend/categorias.php")
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {
                const select = document.getElementById("vCategorias");

                select.innerHTML = resultado.categorias.map((categoria) => `
                    <option value="${categoria.id}">${categoria.nombre}</option>
                `).join("");
            }
        });
}

const formVehiculo = document.getElementById("formVehiculo");

// Al enviar el formulario, mandamos los datos al backend para crear el vehiculo
formVehiculo.addEventListener("submit", (e) => {
    e.preventDefault();
    const datos = new FormData(formVehiculo);

    fetch("../backend/vehiculos.php", {
        method: "POST",
        body: datos
    })
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {
                // Si se agrego bien, limpiamos el formulario y refrescamos la tabla
                formVehiculo.reset();
                cargarVehiculos();
            } else {
                alert(resultado.message);
            }
        });
});

// Elimina un vehiculo por id, pidiendo confirmacion antes
function eliminarVehiculo(id) {
    if (!confirm("¿Seguro que querés eliminar este vehículo?")) {
        return;
    }

    const datos = new FormData();
    datos.append("id", id);

    fetch("../backend/eliminar_vehiculo.php", {
        method: "POST",
        body: datos
    })
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {
                cargarVehiculos();
            } else {
                alert(resultado.message);
            }
        });
}