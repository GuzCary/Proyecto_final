verificarSesion("limp", (datos) => {
    document.getElementById("nombreUsuario").textContent = datos.usuario_nombre;
    cargarProductos();
});

activarLogout();

function cargarProductos() {
    fetch("../backend/usuarios/limpieza/listar_productos.php")
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") {
                mostrarProductos(resultado.productos);
            }
        })
        .catch(err => console.error("Error al listar productos:", err));
}

function mostrarProductos(productos) {
    const cuerpo = document.getElementById("cuerpoProductos");
    if (!productos || productos.length === 0) {
        cuerpo.innerHTML = `<tr><td colspan="3">No hay productos registrados.</td></tr>`;
        return;
    }

    cuerpo.innerHTML = productos.map(p => `
        <tr>
            <td><strong>${p.nombre}</strong></td>
            <td>${p.stock}</td>
            <td>
                <button onclick="modificarStock('${p.id || p.id_encriptado}', 1)">+1</button>
                <button onclick="modificarStock('${p.id || p.id_encriptado}', 5)">+5</button>
                <button onclick="modificarStock('${p.id || p.id_encriptado}', -1)">-1</button>
                <button onclick="modificarStock('${p.id || p.id_encriptado}', -5)">-5</button>
            </td>
        </tr>
    `).join("");
}

document.getElementById("formNuevoProducto").addEventListener("submit", function(e) {
    e.preventDefault();
    const datos = new FormData(this);

    fetch("../backend/usuarios/limpieza/productos.php", {
        method: "POST",
        body: datos
    })
    .then(res => res.json())
    .then(resultado => {
        alert(resultado.message);
        if (resultado.status === "success") {
            this.reset();
            cargarProductos();
        }
    })
    .catch(err => console.error("Error al registrar producto:", err));
});

function modificarStock(idEncriptado, cantidad) {
    const datos = new FormData();
    datos.append("id_encriptado", idEncriptado);
    datos.append("cantidad", cantidad);

    fetch("../backend/usuarios/limpieza/productos.php", {
        method: "POST",
        body: datos
    })
    .then(res => res.json())
    .then(resultado => {
        if (resultado.status === "success") {
            cargarProductos();
        } else {
            alert(resultado.message);
        }
    })
    .catch(err => console.error("Error al modificar stock:", err));
}
