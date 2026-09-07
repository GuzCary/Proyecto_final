const formMarca = document.getElementById("formMarca");
const respuesta = document.getElementById("respuesta");
formMarca.addEventListener("submit", (e) => {
    e.preventDefault();
    const datos = new FormData(formMarca);
    fetch("../backend/marcas/registrar_marca.php", { method: "POST", body: datos })
        .then(res => res.json())
        .then(resultado => {
            respuesta.textContent = resultado.message;
            if (resultado.status === "success") formMarca.reset();
        })
        .catch(() => { respuesta.textContent = "Error al conectar con el servidor."; });
});
