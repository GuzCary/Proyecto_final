document.addEventListener('DOMContentLoaded', () => {
    verificarSesion('admin', () => {
        activarLogout();
        cargarMarcas();
    });
});

async function cargarMarcas() {
    const contenedor = document.getElementById('contenedor-marcas');
    try {
        const res = await fetch('../../../backend/marcas/listar_marcas.php');
        const data = await res.json();
        contenedor.innerHTML = '';

        if (!data.marcas || data.marcas.length === 0) {
            contenedor.innerHTML = '<p>No hay marcas registradas.</p>';
            return;
        }

        data.marcas.forEach(m => {
            const div = document.createElement('div');
            div.classList.add('marca-item');
            div.innerHTML = `
                <strong>Empleado: ${m.usuario}</strong>
                <span>Hora: ${m.hora}</span>
                <small>Ubicación: ${m.direccion}</small>
            `;
            contenedor.appendChild(div);
        });
    } catch (e) {
        contenedor.innerHTML = '<p>Error al cargar marcas.</p>';
    }
}