let vehiculosGlobal = [];
let todasLasCategorias = [];
let autoSeleccionado = null;

verificarSesion("user", (datos) => {
    document.getElementById("nombreUsuario").textContent = datos.usuario_nombre;
    cargarVehiculos();
    cargarCategorias();
});

activarLogout();

function cargarCategorias() {
    fetch('../backend/categorias/listar_categorias.php')
        .then(res => res.json())
        .then(data => {
            todasLasCategorias = data.categorias || [];
        })
        .catch(err => console.error("Error al cargar categorias:", err));
}

function cargarVehiculos() {
    fetch('../backend/vehiculos/listar_vehiculos.php')
        .then(res => res.json())
        .then(data => {
            const contenedor = document.getElementById("listaVehiculos");
            contenedor.innerHTML = "";

            if (data.status !== "success" || !data.vehiculos || data.vehiculos.length === 0) {
                contenedor.innerHTML = "<p>No hay vehiculos cargados.</p>";
                return;
            }

            vehiculosGlobal = data.vehiculos;

            data.vehiculos.forEach(v => {
                contenedor.innerHTML += `
                    <div onclick="seleccionarAuto('${v.id}')" 
                         style="border: 1px solid #aaa; padding: 10px; margin: 8px 0; cursor: pointer;">
                        <strong>🚗 ${v.marca} ${v.modelo} (${v.anio})</strong>
                        <p style="margin: 4px 0;">Patente: ${v.patente} | Precio: $${v.precio} | Sucursal: ${v.sucursal}</p>
                        <small style="color: blue;">👉 Haz clic para seleccionar este vehiculo</small>
                    </div>
                `;
            });
        })
        .catch(err => console.error("Error al cargar vehiculos:", err));
}

function seleccionarAuto(idEncriptado) {
    autoSeleccionado = vehiculosGlobal.find(v => v.id === idEncriptado);
    if (!autoSeleccionado) return;

    document.getElementById("nombreAutoSeleccionado").textContent = `${autoSeleccionado.marca} ${autoSeleccionado.modelo} (${autoSeleccionado.patente})`;
    document.getElementById("panelSeleccion").style.display = "block";
    document.getElementById("seccionEditar").style.display = "none";
}

function mostrarFormulario() {
    if (!autoSeleccionado) return;

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

    const contenedorCat = document.getElementById("contenedorCategorias");
    contenedorCat.innerHTML = "";
    const categoriasActualesIds = (autoSeleccionado.categorias || []).map(cat => String(cat.id));

    todasLasCategorias.forEach(cat => {
        const catId = String(cat.id_encriptado || cat.id);
        const estaMarcada = categoriasActualesIds.includes(catId) ? 'checked' : '';

        contenedorCat.innerHTML += `
            <label style="margin-right: 15px; display: inline-block; cursor: pointer;">
                <input type="checkbox" name="categorias[]" value="${catId}" ${estaMarcada}>
                ${cat.nombre}
            </label>
        `;
    });

    document.getElementById("seccionEditar").style.display = "block";
}

function cancelarEdicion() {
    document.getElementById("seccionEditar").style.display = "none";
    document.getElementById("formModificar").reset();
}

document.getElementById("formModificar").addEventListener("submit", function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('../backend/vehiculos/modificar_vehiculo.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.status === "success") {
            cancelarEdicion();
            document.getElementById("panelSeleccion").style.display = "none";
            cargarVehiculos();
        }
    })
    .catch(err => console.error("Error al modificar vehiculo:", err));
});
