const formMarca = document.getElementById("formMarca");
const respuesta = document.getElementById("respuesta");

formMarca.addEventListener("submit", (e) => {
    e.preventDefault();

    const datos = new FormData(formMarca);

    fetch("../backend/marcas.php", {
        method: "POST",
        body: datos
    })
    .then(res => res.json())
    .then(resultado => {
        if (resultado.status === "success") {
            respuesta.textContent = resultado.message;
            formMarca.reset();
        } else {
            respuesta.textContent = resultado.message;
        }
    })
    .catch(error => {
        respuesta.textContent = "Error al conectar con el servidor.";
    });
});