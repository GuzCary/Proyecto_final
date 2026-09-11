document.addEventListener('DOMContentLoaded', () => {
    verificarSesion();
    configurarLogout();
});

function verificarSesion() {
    fetch('../../backend/seguridad/auth.php')
        .then(res => res.json())
        .then(datos => {
            if (!datos.logueado || datos.usuario_rol !== 'limp') {
                window.location.href = '../login/login.html';
            } else {
                document.getElementById('nombre-usuario').textContent = datos.usuario_nombre;
            }
        })
        .catch(() => {
            window.location.href = '../login/login.html';
        });
}

function configurarLogout() {
    const btnLogout = document.getElementById('btnLogout');
    btnLogout.addEventListener('click', () => {
        fetch('../../backend/seguridad/logout.php')
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    window.location.href = '../login/login.html';
                }
            });
    });
}