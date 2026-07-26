// Verificamos que haya sesion activa con rol de limpieza
// Si es valida, mostramos el nombre de usuario en pantalla
verificarSesion("limp", (datos) => {
    document.getElementById("nombreUsuario").textContent = datos.usuario_nombre;
});

// Activamos el boton de logout (logica definida en auth.js)
activarLogout();