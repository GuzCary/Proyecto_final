const loginForm = document.getElementById("loginForm");

loginForm.addEventListener("submit", (e) => {
    e.preventDefault(); 
    const datos = new FormData(loginForm);

    fetch("../backend/seguridad/login.php", {
        method: "POST",
        body: datos
    })
    .then(res => res.json())
    .then(resultado => {
        if (resultado.status === "success") {
            if (resultado.rol === "admin") {
                window.location.href = "admin.html";
            } else if (resultado.rol === "user") {
                window.location.href = "vendedor.html";
            } else if (resultado.rol === "limp") {
                window.location.href = "limpieza.html";
            }
        } else {
            alert(resultado.message || "Credenciales incorrectas.");
        }
    })
    .catch(err => {
        console.error("Error en login:", err);
        alert("Error al conectar con el servidor.");
    });
});
