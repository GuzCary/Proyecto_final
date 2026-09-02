let vehiculosGlobal = [];
let todasLasCategorias = [];
let autoSeleccionado = null;

// Verificamos sesión e iniciamos las cargas
verificarSesion("user", (datos) => {
    document.getElementById("nombreUsuario").textContent = datos.usuario_nombre;
    cargarVehiculos();
    cargarCategorias();
});

activarLogout();

// 1. Obtener todas las categorías disponibles de la BD
function cargarCategorias() {
    fetch('../backend/categorias.php')
        .then(res => res.json())
        .then(data => {
            todasLasCategorias = data.categorias || data;
        })
        .catch(err => console.error("Error al cargar categorías:", err));
}

// 2. Cargar y listar los vehículos
function cargarVehiculos() {
    fetch('../backend/listar_vehiculos.php')
        .then(res => res.json())
        .then(data => {
            const contenedor = document.getElementById("listaVehiculos");
            contenedor.innerHTML = "";

            if (data.status !== "success" || !data.vehiculos || data.vehiculos.length === 0) {
                contenedor.innerHTML = "<p>No hay vehículos cargados.</p>";
                return;
            }

            vehiculosGlobal = data.vehiculos;

            data.vehiculos.forEach(v => {
                contenedor.innerHTML += `
                    <div onclick="seleccionarAuto('${v.id}')" 
                         style="border: 1px solid #aaa; padding: 10px; margin: 8px 0; cursor: pointer;">
                        <strong>🚗 ${v.marca} ${v.modelo} (${v.anio})</strong>
                        <p style="margin: 4px 0;">Patente: ${v.patente} | Precio: $${v.precio} | Sucursal: ${v.sucursal}</p>
                        <small style="color: blue;">👉 Haz clic para seleccionar este vehículo</small>
                    </div>
                `;
            });
        })
        .catch(err => console.error("Error al cargar vehículos:", err));
}

// 3. Cuando el usuario hace clic en un auto de la lista
function seleccionarAuto(idEncriptado) {
    autoSeleccionado = vehiculosGlobal.find(v => v.id === idEncriptado);
    if (!autoSeleccionado) return;

    // Mostramos el nombre y el botón para modificar
    document.getElementById("nombreAutoSeleccionado").textContent = `${autoSeleccionado.marca} ${autoSeleccionado.modelo} (${autoSeleccionado.patente})`;
    document.getElementById("panelSeleccion").style.display = "block";
    document.getElementById("seccionEditar").style.display = "none";
}

// 4. Precargar todos los datos viejos en el formulario y marcar los checkboxes
function mostrarFormulario() {
    if (!autoSeleccionado) return;

    // Precargamos todos los campos de texto y números
    document.getElementById("edit_id").value = autoSeleccionado.id;
    document.getElementById("edit_idSucursal").value = autoSeleccionado.idSucursal || 1;
    document.getElementById("edit_marca").value = autoSeleccionado.marca || '';
    document.getElementById("edit_modelo").value = autoSeleccionado.modelo || '';
    document.getElementById("edit_patente").value = autoSeleccionado.patente || '';
    document.getElementById("edit_anio").value = autoSeleccionado.anio || 0;
    document.getElementById("edit_km").value = autoSeleccionado.km || 0;
    document.getElementById("edit_precio").value = autoSeleccionado.precio || 0;
    document.getElementById("edit_precioMinimo").value = autoSeleccionado.precioMinimo || 0;
    document.getElementById("edit_potencia").value = autoSeleccionado.potencia || 0;
    document.getElementById("edit_consumo").value = autoSeleccionado.consumo || 0;
    document.getElementById("edit_estado").value = autoSeleccionado.estado || 0;
    document.getElementById("edit_enlaceDocOficial").value = autoSeleccionado.enlaceDocOficial || '';
    document.getElementById("edit_seguroSOA").value = autoSeleccionado.seguroSOA || 0;
    document.getElementById("edit_seguroTerceros").value = autoSeleccionado.seguroTerceros || 0;
    document.getElementById("edit_seguroTotal").value = autoSeleccionado.seguroTotal || 0;
    document.getElementById("edit_descripcion").value = autoSeleccionado.descripcion || '';

    // Renderizamos los checkboxes marcando los que ya tiene el vehículo
    const contenedorCat = document.getElementById("contenedorCategorias");
    contenedorCat.innerHTML = "";

    const categoriasActualesIds = (autoSeleccionado.categorias || []).map(cat => Number(cat.id));

    todasLasCategorias.forEach(cat => {
        const estaMarcada = categoriasActualesIds.includes(Number(cat.id)) ? 'checked' : '';

        contenedorCat.innerHTML += `
            <label style="margin-right: 15px; display: inline-block; cursor: pointer;">
                <input type="checkbox" name="categorias[]" value="${cat.id}" ${estaMarcada}>
                ${cat.nombre}
            </label>
        `;
    });

    document.getElementById("seccionEditar").style.display = "block";
}

// Ocultar formulario
function cancelarEdicion() {
    document.getElementById("seccionEditar").style.display = "none";
    document.getElementById("formModificar").reset();
}

// 5. Enviar la modificación por POST a PHP
document.getElementById("formModificar").addEventListener("submit", function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('../backend/modificar_vehiculo.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.status === "success") {
            cancelarEdicion();
            document.getElementById("panelSeleccion").style.display = "none";
            cargarVehiculos(); // Refrescamos la lista con los nuevos cambios
        }
    })
    .catch(err => console.error("Error al modificar:", err));
});