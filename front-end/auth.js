// Verifica si hay sesión y rol correcto. Si falla, redirige al login.
// rolEsperado puede ser un rol ('admin') o una lista de roles (['user', 'admin'])
function verificarSesion(rolEsperado, siInicioSesion) {
    const rolesPermitidos = Array.isArray(rolEsperado) ? rolEsperado : [rolEsperado];

    fetch('../../../backend/seguridad/auth.php', { cache: 'no-store' })
        .then(res => res.json())
        .then(datos => {
            if (!datos.logueado || !rolesPermitidos.includes(datos.usuario_rol)) {
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

// Activa el menú desplegable "⚙ Config" del header de administración si existe
function activarMenuConfig() {
    const menu = document.querySelector('.config-menu');
    if (!menu) {
        return;
    }

    const boton = menu.querySelector('.config-toggle');

    // marcamos como activo el enlace de la página actual (y el botón, si estamos dentro del menú)
    const paginaActual = window.location.pathname.replace(/\/index\.html$/, '/');
    menu.querySelectorAll('.config-dropdown a').forEach(enlace => {
        const destino = new URL(enlace.href).pathname.replace(/\/index\.html$/, '/');
        if (destino === paginaActual) {
            enlace.classList.add('active');
            boton.classList.add('active');
        }
    });

    function cerrar() {
        menu.classList.remove('abierto');
        boton.setAttribute('aria-expanded', 'false');
    }

    // abrimos y cerramos con clic
    boton.addEventListener('click', (e) => {
        e.stopPropagation();
        const abierto = menu.classList.toggle('abierto');
        boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });

    // si se hace clic afuera o se presiona Escape, se cierra
    document.addEventListener('click', (e) => {
        if (!menu.contains(e.target)) {
            cerrar();
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            cerrar();
        }
    });
}

// El menú no depende de la sesión, así que lo activamos apenas carga la página
document.addEventListener('DOMContentLoaded', activarMenuConfig);
