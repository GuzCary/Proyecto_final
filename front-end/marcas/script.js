document.addEventListener('DOMContentLoaded', () => {
    iniciarReloj();
    configurarFormulario();
});

function iniciarReloj() {
    const relojHora = document.getElementById('reloj-hora');
    const relojFecha = document.getElementById('reloj-fecha');

    function actualizar() {
        const ahora = new Date();

        relojHora.textContent = ahora.toLocaleTimeString('es-UY', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });

        relojFecha.textContent = ahora.toLocaleDateString('es-UY', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    actualizar();
    setInterval(actualizar, 1000);
}

function configurarFormulario() {
    const form = document.getElementById('form-marca');
    const mensaje = document.getElementById('mensaje');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        mensaje.style.display = 'none';

        const formData = new FormData(form);

        try {
            const res = await fetch('../../backend/marcas/registrar_marca.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            mostrarMensaje(data.status, data.message);

            if (data.status === 'success') {
                form.reset();
            }
        } catch (error) {
            mostrarMensaje('error', 'Error al conectar con el servidor.');
        }
    });

    function mostrarMensaje(tipo, texto) {
        mensaje.style.display = 'block';
        mensaje.style.backgroundColor = tipo === 'success' ? '#d4edda' : '#f8d7da';
        mensaje.style.color = tipo === 'success' ? '#155724' : '#721c24';
        mensaje.textContent = texto;

        setTimeout(() => {
            mensaje.style.display = 'none';
        }, 3500);
    }
}