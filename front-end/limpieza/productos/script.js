document.addEventListener('DOMContentLoaded', () => {
    verificarSesion('limp', () => {
        activarLogout();
        cargarProductos();
        configurarFormulario();
    });
});

const form = document.getElementById('form-producto');
const contenedor = document.getElementById('contenedor-productos');
const mensaje = document.getElementById('mensaje');

async function cargarProductos() {
    try {
        const res = await fetch('../../../backend/productos/listar_productos.php');
        const data = await res.json();
        contenedor.innerHTML = '';

        if (!data.productos || data.productos.length === 0) {
            contenedor.innerHTML = '<p>No hay productos registrados.</p>';
            return;
        }

        data.productos.forEach(p => {
            const div = document.createElement('div');
            div.classList.add('item-producto');
            div.innerHTML = `
                <div>
                    <strong>${p.nombre}</strong>
                    <br><small>Stock actual: <b>${p.stock}</b></small>
                </div>
                <div class="stock-acciones">
                    <input type="number" id="cant-${p.id}" value="1" min="1">
                    <button class="btn-stock" onclick="modificarStock('${p.id}', 1)">+ Sumar</button>
                    <button class="btn-stock" onclick="modificarStock('${p.id}', -1)">- Restar</button>
                </div>
            `;
            contenedor.appendChild(div);
        });
    } catch (e) {
        contenedor.innerHTML = '<p>Error al cargar productos.</p>';
    }
}

function configurarFormulario() {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('../../../backend/productos/productos.php', {
            method: 'POST',
            body: new FormData(form)
        });
        const data = await res.json();
        mostrarMensaje(data.status, data.message);
        if (data.status === 'success') {
            form.reset();
            cargarProductos();
        }
    });
}

async function modificarStock(idEncriptado, factor) {
    const inputCant = document.getElementById(`cant-${idEncriptado}`);
    const valor = parseInt(inputCant.value, 10);
    if (!valor || valor <= 0) return;

    const fd = new FormData();
    fd.append('id_encriptado', idEncriptado);
    fd.append('cantidad', valor * factor);

    const res = await fetch('../../../backend/productos/productos.php', {
        method: 'POST',
        body: fd
    });
    const data = await res.json();
    mostrarMensaje(data.status, data.message);

    if (data.status === 'success') {
        cargarProductos();
    }
}

function mostrarMensaje(tipo, texto) {
    mensaje.style.display = 'block';
    mensaje.style.background = tipo === 'success' ? '#d4edda' : '#f8d7da';
    mensaje.style.color = tipo === 'success' ? '#155724' : '#721c24';
    mensaje.textContent = texto;
    setTimeout(() => mensaje.style.display = 'none', 2500);
}