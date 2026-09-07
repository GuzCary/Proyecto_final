verificarSesion("admin", (datos) => {
    document.getElementById("nombreUsuario").textContent = datos.usuario_nombre;
    cargarMarcas();
});
activarLogout();
function escaparHTML(texto) {
    const div = document.createElement("div");
    div.textContent = texto;
    return div.innerHTML;
}
function cargarMarcas() {
    fetch("../backend/marcas/listar_marcas.php")
        .then(res => res.json())
        .then(resultado => {
            if (resultado.status === "success") mostrarMarcas(resultado.marcas);
        })
        .catch(error => console.error("Error al cargar marcas:", error));
}
function mostrarMarcas(marcas) {
    const cuerpo = document.getElementById("cuerpoMarcas");
    if (!marcas || marcas.length === 0) {
        cuerpo.innerHTML = `<tr><td colspan="4">No hay marcas registradas.</td></tr>`;
        return;
    }
    cuerpo.innerHTML = marcas.map(m => `
        <tr>
            <td>${escaparHTML(String(m.id))}</td>
            <td>${escaparHTML(m.usuario)}</td>
            <td>${escaparHTML(m.hora)}</td>
            <td>${escaparHTML(m.direccion)}</td>
        </tr>
    `).join("");
}
