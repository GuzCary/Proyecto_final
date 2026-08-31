// Variables globales
let vehiculosGlobal = [];
let todasLasCategorias = [];
let vehiculoSeleccionado = null;

// Helper para escapar texto antes de inyectarlo en HTML
function escaparHTML(texto) {
    const div = document.createElement("div");
    div.textContent = texto;
    return div.innerHTML;
}

// Verificamos que haya sesion activa con rol de admin
verificarSesion("admin", (datos) => {
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
                vehiculosGlobal = resultado.vehiculos;
                mostrarVehiculos(resultado.vehiculos);
            } else {
                alert("Error al cargar vehiculos: " + resultado.message);
            }
        })
        .catch(err => console.error("Error al cargar vehiculos:", err));
}

// Recibe un array de vehiculos y arma las filas de la tabla con esos datos
function mostrarVehiculos(vehiculos) {
    const cuerpo = document.getElementById("cuerpoVehiculos");

    cuerpo.innerHTML = vehiculos.map((vehiculo) => {
        const nombresCategorias = escaparHTML(vehiculo.categorias.map(c => c.nombre).join(", "));
        const idEncriptado = escaparHTML(vehiculo.id);

        return `
            <tr>
                <td>${idEncriptado}</td>
                <td>${escaparHTML(vehiculo.marca)}</td>
                <td>${escaparHTML(vehiculo.modelo)}</td>
                <td>${vehiculo.anio ?? ""}</td>
                <td>${escaparHTML(String(vehiculo.precio))}</td>
                <td>${escaparHTML(vehiculo.sucursal)}</td>
                <td>${nombresCategorias}</td>
                <td>
                    <button onclick="abrirModal('${idEncriptado}')">Modificar</button>
                    <button onclick="eliminarVehiculo('${idEncriptado}')">Eliminar</button>
                </td>
            </tr>
        `;
    }).join("");
}

// Pide al backend el listado de categorias y las guarda globalmente
function cargarCategorias() {
    fetch("../backend/categorias.php")
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {
                todasLasCategorias = resultado.categorias || [];

                // Cargamos las opciones del select de agregar vehiculo
                const select = document.getElementById("vCategorias");
                select.innerHTML = todasLasCategorias.map((categoria) => `
                    <option value="${categoria.id}">${escaparHTML(categoria.nombre)}</option>
                `).join("");
            } else {
                console.error("Error al cargar categorias:", resultado.message);
            }
        })
        .catch(err => console.error("Error al cargar categorias:", err));
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
                formVehiculo.reset();
                cargarVehiculos();
            } else {
                alert(resultado.message);
            }
        })
        .catch(err => console.error("Error al agregar vehiculo:", err));
});

// Abre el modal y carga los datos del vehiculo a modificar
function abrirModal(idEncriptado) {
    vehiculoSeleccionado = vehiculosGlobal.find(v => v.id === idEncriptado);

    if (!vehiculoSeleccionado) {
        alert("No se encontro el vehiculo seleccionado.");
        return;
    }

    document.getElementById("edit_id").value = vehiculoSeleccionado.id;
    document.getElementById("edit_idSucursal").value = vehiculoSeleccionado.idSucursal || 1;
    document.getElementById("edit_marca").value = vehiculoSeleccionado.marca || '';
    document.getElementById("edit_modelo").value = vehiculoSeleccionado.modelo || '';
    document.getElementById("edit_patente").value = vehiculoSeleccionado.patente || '';
    document.getElementById("edit_anio").value = vehiculoSeleccionado.anio || 0;
    document.getElementById("edit_km").value = vehiculoSeleccionado.km || 0;
    document.getElementById("edit_precio").value = vehiculoSeleccionado.precio || 0;
    document.getElementById("edit_precioMinimo").value = vehiculoSeleccionado.precioMinimo || 0;
    document.getElementById("edit_potencia").value = vehiculoSeleccionado.potencia || 0;
    document.getElementById("edit_consumo").value = vehiculoSeleccionado.consumo || 0;
    document.getElementById("edit_estado").value = vehiculoSeleccionado.estado || 0;
    document.getElementById("edit_enlaceDocOficial").value = vehiculoSeleccionado.enlaceDocOficial || '';
    document.getElementById("edit_seguroSOA").value = vehiculoSeleccionado.seguroSOA || 0;
    document.getElementById("edit_seguroTerceros").value = vehiculoSeleccionado.seguroTerceros || 0;
    document.getElementById("edit_seguroTotal").value = vehiculoSeleccionado.seguroTotal || 0;
    document.getElementById("edit_descripcion").value = vehiculoSeleccionado.descripcion || '';

    // Renderizamos los checkboxes de categorias
    const contenedorCat = document.getElementById("contenedorCategorias");
    contenedorCat.innerHTML = "";

    const categoriasActualesIds = (vehiculoSeleccionado.categorias || []).map(cat => Number(cat.id));

    if (todasLasCategorias.length === 0) {
        contenedorCat.innerHTML = "<p>No hay categorias disponibles.</p>";
    } else {
        contenedorCat.innerHTML = todasLasCategorias.map(cat => {
            const estaMarcada = categoriasActualesIds.includes(Number(cat.id)) ? 'checked' : '';
            return `
                <label style="margin-right: 15px; display: inline-block; cursor: pointer;">
                    <input type="checkbox" name="categorias[]" value="${cat.id}" ${estaMarcada}>
                    ${escaparHTML(cat.nombre)}
                </label>
            `;
        }).join("");
    }

    document.getElementById("modalEditar").style.display = "block";
}

// Cierra el modal y limpia el formulario
function cerrarModal() {
    document.getElementById("modalEditar").style.display = "none";
    document.getElementById("formModificar").reset();
    vehiculoSeleccionado = null;
}

// Envio del formulario de modificacion
const formModificar = document.getElementById("formModificar");

formModificar.addEventListener("submit", (e) => {
    e.preventDefault();

    if (!vehiculoSeleccionado) {
        alert("No hay un vehiculo seleccionado.");
        return;
    }

    const datos = new FormData(formModificar);

    fetch("../backend/modificar_vehiculo.php", {
        method: "POST",
        body: datos
    })
        .then(res => res.json())
        .then(resultado => {
            alert(resultado.message);
            if (resultado.status === "success") {
                cerrarModal();
                cargarVehiculos();
            }
        })
        .catch(err => {
            console.error("Error al modificar vehiculo:", err);
            alert("Error al comunicarse con el servidor.");
        });
});

// Elimina un vehiculo por su id encriptado, pidiendo confirmacion antes
function eliminarVehiculo(idEncriptado) {
    if (!confirm("¿Seguro que querés eliminar este vehiculo?")) {
        return;
    }

    const datos = new FormData();
    datos.append("id", idEncriptado);

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
        })
        .catch(err => console.error("Error al eliminar vehiculo:", err));
}
