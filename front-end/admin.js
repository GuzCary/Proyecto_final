// Verificamos que haya sesion activa con rol de admin
verificarSesion("admin", (datos) => {
    // Si es valida, mostramos el nombre de usuario en pantalla
    document.getElementById("nombreUsuario").textContent = datos.usuario_nombre;
});

// Activamos el boton de logout (logica definida en auth.js)
activarLogout();