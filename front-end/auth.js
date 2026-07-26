// Verifica si hay una sesion activa y si el usuario tiene el rol esperado
// Si no cumple, redirige al login. Si cumple, ejecuta la funcion "siInicioSesion" pasandole los datos del usuario
function verificarSesion(rolEsperado, siInicioSesion) {
    fetch("../backend/auth.php")
        .then(res => res.json())
        .then(datos => {
            if (!datos.logueado || datos.usuario_rol !== rolEsperado) {
                window.location.href = "login.html";
            } else {
                siInicioSesion(datos);
            }
        });
}

// Si existe en la pagina, se agrega el evento de logout al boton
function activarLogout() {
    const btnLogout = document.getElementById("btnLogout");

    // Si la pagina no tiene boton de logout, no se hace nada
    if (!btnLogout) {
        return;
    }

    btnLogout.addEventListener("click", () => {
        fetch("../backend/logout.php")
            .then(res => res.json())
            .then(resultado => {
                if (resultado.status === "success") {
                    window.location.href = "login.html";
                }
            });
    });
}