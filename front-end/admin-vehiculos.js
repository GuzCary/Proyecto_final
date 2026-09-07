let vehiculosGlobal = [];
let todasLasCategorias = [];
let vehiculoSeleccionado = null;

function escaparHTML(texto) {
    const div = document.createElement("div");
    div.textContent = texto;
    return div.innerHTML;
}

verificarSesion("admin", (datos) => {
    document.getElementById("nombreUsuario").textContent = datos.usuario_nombre;
    cargarVehiculos();
    cargarCategorias();
});

activarLogout();

function cargarVehiculos() {
    fetch("../backend/vehiculos/listar_vehiculos.php")
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {
                vehiculosGlobal = resultado.vehiculos || [];
                mostrarVehiculos(vehiculosGlobal);
            }
        })
        .catch(err => console.error("Error al listar vehiculos:", err));
}

function mostrarVehiculos(vehiculos) {
    const cuerpo = document.getElementById("cuerpoVehiculos");
    cuerpo.innerHTML = vehiculos.map((vehiculo) => {
        const nombresCategorias = escaparHTML((vehiculo.categorias || []).map(c => c.nombre).join(", "));
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

function cargarCategorias() {
    fetch("../backend/categorias/listar_categorias.php")
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {
                todasLasCategorias = resultado.categorias || [];
                const select = document.getElementById("vCategorias");
                select.innerHTML = todasLasCategorias.map((categoria) => `
                    <option value="${categoria.id_encriptado || categoria.id}">${escaparHTML(categoria.nombre)}</option>
                `).join("");
            }
        })
        .catch(err => console.error("Error al listar categorias:", err));
}

const formVehiculo = document.getElementById("formVehiculo");
const modalAgregar = document.getElementById("modalAgregar");

formVehiculo.addEventListener("submit", (e) => {
    e.preventDefault();
    const datos = new FormData(formVehiculo);

    fetch("../backend/vehiculos/vehiculo.php", {
        method: "POST",
        body: datos
    })
    .then(res => res.json())
    .then(resultado => {
        if (resultado.status === "success") {
            formVehiculo.reset();
            cerrarModalAgregar();
            cargarVehiculos();
        } else {
            alert(resultado.message);
        }
    })
    .catch(err => console.error("Error al agregar vehiculo:", err));
});

function abrirModalAgregar() { modalAgregar.style.display = "block"; }
function cerrarModalAgregar() { modalAgregar.style.display = "none"; formVehiculo.reset(); }

function abrirModal(idEncriptado) {
    vehiculoSeleccionado = vehiculosGlobal.find(v => v.id === idEncriptado);
    if (!vehiculoSeleccionado) return;

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

    const contenedorCat = document.getElementById("contenedorCategorias");
    contenedorCat.innerHTML = "";
    const categoriasActualesIds = (vehiculoSeleccionado.categorias || []).map(cat => String(cat.id));

    if (todasLasCategorias.length === 0) {
        contenedorCat.innerHTML = "<p>No hay categorias disponibles.</p>";
    } else {
        contenedorCat.innerHTML = todasLasCategorias.map(cat => {
            const catId = String(cat.id_encriptado || cat.id);
            const estaMarcada = categoriasActualesIds.includes(catId) ? 'checked' : '';
            return `
                <label style="margin-right: 15px; display: inline-block; cursor: pointer;">
                    <input type="checkbox" name="categorias[]" value="${catId}" ${estaMarcada}>
                    ${escaparHTML(cat.nombre)}
                </label>
            `;
        }).join("");
    }

    const contenedorImg = document.getElementById("contenedorImagenes");
    contenedorImg.innerHTML = "";
    const imagenesActuales = vehiculoSeleccionado.imagenes || [];

    if (imagenesActuales.length === 0) {
        contenedorImg.innerHTML = "<p>No hay imagenes para este vehiculo.</p>";
    } else {
        contenedorImg.innerHTML = imagenesActuales.map(img => `
            <label style="display: inline-block; margin: 5px; text-align: center; vertical-align: top;">
                <img src="../img/${escaparHTML(img)}" width="100" style="display: block; border-radius: 4px;"><br>
                <input type="checkbox" name="eliminarImagenes[]" value="${escaparHTML(img)}">
                Eliminar
            </label>
        `).join("");
    }

    document.getElementById("modalEditar").style.display = "block";
}

function cerrarModal() {
    document.getElementById("modalEditar").style.display = "none";
    document.getElementById("formModificar").reset();
    vehiculoSeleccionado = null;
}

const formModificar = document.getElementById("formModificar");

formModificar.addEventListener("submit", (e) => {
    e.preventDefault();
    if (!vehiculoSeleccionado) return;

    const datos = new FormData(formModificar);

    fetch("../backend/vehiculos/modificar_vehiculo.php", {
        method: "POST",
        body: datos
    })
    .then(res => res.json())
    .then(resultado => {
        if (resultado.status === "success") {
            cerrarModal();
            cargarVehiculos();
        } else {
            alert(resultado.message);
        }
    })
    .catch(err => console.error("Error al modificar vehiculo:", err));
});

function eliminarVehiculo(idEncriptado) {
    if (!confirm("¿Seguro que queres eliminar este vehiculo?")) return;

    const datos = new FormData();
    datos.append("id", idEncriptado);

    fetch("../backend/vehiculos/eliminar_vehiculo.php", {
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
