// Verifica si hay sesión y rol correcto. Si falla, redirige al login.
function verificarSesion(rolEsperado, siInicioSesion) {
    fetch('../../../backend/seguridad/auth.php', { cache: 'no-store' })
        .then(res => res.json())
        .then(datos => {
            if (!datos.logueado || datos.usuario_rol !== rolEsperado) {
                window.location.href = '../../login/login.html';
            } else if (typeof siInicioSesion === 'function') {
                siInicioSesion(datos);
            }
        })
        .catch(() => {
            window.location.href = '../../login/login.html';
        });
}

// Activa el botón de cerrar sesión si existe en el DOM
function activarLogout() {
    const btnLogout = document.getElementById('btnLogout');

    // Si el botón no existe en el HTML actual, no hace nada y no rompe el código
    if (!btnLogout) {
        return;
    }

    btnLogout.addEventListener('click', () => {
        fetch('../../../backend/seguridad/logout.php', { method: 'POST', cache: 'no-store' })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    window.location.href = '../../login/login.html';
                }
            })
            .catch(() => {
                window.location.href = '../../login/login.html';
            });
    });
}
