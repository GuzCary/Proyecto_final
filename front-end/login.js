const loginForm = document.getElementById("loginForm");

// Al enviar el formulario de login, mandamos los datos al backend
loginForm.addEventListener("submit", (e) => {
    e.preventDefault(); 

    let datos = new FormData(loginForm);

    fetch("../backend/login.php", {
        method: "POST",
        body: datos
    })
        .then(res => res.json())
        .then(resultado => {

            if (resultado.status === "success") {

                // Segun el rol del usuario, lo redirigimos a su panel correspondiente
                if (resultado.rol === "admin") {
                    window.location.href = "admin.html";
                } else if (resultado.rol === "user") {
                    window.location.href = "vendedor.html";
                } else if (resultado.rol === "limp") {
                    window.location.href = "limpieza.html";
                }

            } else {
                alert("Usuario o contraseña incorrectos");
            }

        });
});