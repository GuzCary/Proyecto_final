document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-login');
    const mensaje = document.getElementById('mensaje');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        mensaje.style.display = 'none';

        const formData = new FormData(form);

        try {
            const res = await fetch('../../backend/seguridad/login.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status === 'success') {
                mensaje.style.display = 'block';
                mensaje.style.backgroundColor = '#d4edda';
                mensaje.style.color = '#155724';
                mensaje.textContent = 'Sesión iniciada correctamente. Redirigiendo...';

                setTimeout(() => {
                    if (data.rol === 'admin') {
                        window.location.href = '../admin/vehiculos/index.html';
                    } else if (data.rol === 'user') {
                        window.location.href = '../vendedor/ventas/index.html';
                    } else if (data.rol === 'limp') {
                        window.location.href = '../limpieza/productos/index.html';
                    }
                }, 800);
            } else {
                mensaje.style.display = 'block';
                mensaje.style.backgroundColor = '#f8d7da';
                mensaje.style.color = '#721c24';
                mensaje.textContent = data.message || 'Credenciales incorrectas.';
            }
        } catch (error) {
            mensaje.style.display = 'block';
            mensaje.style.backgroundColor = '#f8d7da';
            mensaje.style.color = '#721c24';
            mensaje.textContent = 'Error de conexión con el servidor.';
        }
    });
});
