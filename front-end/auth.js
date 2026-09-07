function verificarSesion(rolEsperado, siInicioSesion) {
    fetch("../backend/seguridad/auth.php")
        .then(res => res.json())
        .then(datos => {
            if (!datos.logueado || datos.usuario_rol !== rolEsperado) {
                window.location.href = "login.html";
            } else {
                siInicioSesion(datos);
            }
        })
        .catch(err => {
            console.error("Error al verificar sesion:", err);
            window.location.href = "login.html";
        });
}

function activarLogout() {
    const btnLogout = document.getElementById("btnLogout");
    if (!btnLogout) return;

    btnLogout.addEventListener("click", () => {
        fetch("../backend/seguridad/logout.php")
            .then(res => res.json())
            .then(resultado => {
                if (resultado.status === "success") {
                    window.location.href = "login.html";
                }
            })
            .catch(err => console.error("Error al cerrar sesion:", err));
    });
}
