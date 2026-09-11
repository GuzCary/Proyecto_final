document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-login');
    const mensaje = document.getElementById('mensaje');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        mensaje.style.display = 'none';
        mensaje.textContent = '';

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
                mensaje.textContent = `Sesión iniciada con rol: ${data.rol}. Redirigiendo...`;

                // Redirección básica según el rol devuelto
                setTimeout(() => {
                    if (data.rol === 'admin') {
                        // Próxima carpeta admin
                        alert('Logueado como ADMIN. (Aquí redirigirá al panel admin)');
                    } else if (data.rol === 'user') {
                        // Próxima carpeta ventas
                        alert('Logueado como VENDEDOR. (Aquí redirigirá a ventas)');
                    } else if (data.rol === 'limp') {
                        // Próxima carpeta limpieza
                        alert('Logueado como LIMPIEZA. (Aquí redirigirá a productos)');
                    }
                }, 1000);

            } else {
                mensaje.style.display = 'block';
                mensaje.style.backgroundColor = '#f8d7da';
                mensaje.style.color = '#721c24';document.addEventListener('DOMContentLoaded', () => {
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
                                mensaje.textContent = `Sesión iniciada. Redirigiendo...`;
                
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
                                mensaje.textContent = data.message || 'Error al iniciar sesión.';
                            }
                        } catch (error) {
                            mensaje.style.display = 'block';
                            mensaje.style.backgroundColor = '#f8d7da';
                            mensaje.style.color = '#721c24';
                            mensaje.textContent = 'Error de conexión con el servidor.';
                        }
                    });
                });
                mensaje.textContent = data.message || 'Error al iniciar sesión.';
            }

        } catch (error) {
            mensaje.style.display = 'block';
            mensaje.style.backgroundColor = '#f8d7da';
            mensaje.style.color = '#721c24';
            mensaje.textContent = 'Error de conexión con el servidor.';
        }
    });
});