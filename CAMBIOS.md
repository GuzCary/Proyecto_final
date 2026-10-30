# Cambios de esta versión

Copiá el contenido del zip encima de la carpeta `primera entrega/` (reemplazando los archivos).
El zip trae solo archivos nuevos o modificados respecto de la entrega original. No toca tu `.env`.

**La base de datos NO cambia.** `script.sql` queda exactamente como estaba; no hay que correr nada nuevo.

Lo único a revisar: tu `.env` tiene que tener las claves que lee `env_load.php` (ver `.env.example`:
`DB_USER_PUBLIC`, `DB_PASS_PUBLIC`, `DB_USER_GESTION`, `DB_PASS_GESTION`).

---

## Última tanda

- **Vehículos vendidos:** ya no aparecen en el inventario admin, el catálogo ni el panel de vendedor; solo en el Historial de ventas. El servidor rechaza editarlos o eliminarlos. Antes, eliminar un vehículo borraba también sus ventas.
- **Usuarios:** el formulario pide todos los campos de la tabla `Usuarios`: fecha de contrato (viene con la de hoy), días de trabajo por semana (1–7), horario de entrada y de salida, y días de licencia por año (0–365). Son opcionales; al editar, dejarlos vacíos los borra. Se muestran en la tarjeta de cada usuario. La validación de usuarios ahora está en `backend/usuarios/datos_usuario.php`, compartida por el alta y la edición (antes estaba repetida).
- **Historial de gastos de limpieza:** dentro de Insumos, visible solo para el admin (`backend/productos/listar_gastos.php`). Muestra quién usó qué y cuánto, con filtros por empleado y producto y el total por producto. La tabla `Gasta` no guarda fecha, así que se ordena del más nuevo al más viejo.
- Se revisaron todas las columnas de la base: cada una se carga en algún formulario y se muestra en algún panel.

---

## Por qué fallaban Sucursales, Transacciones y Sanciones

Esos tres guardados usaban `mb_strlen()`, que pertenece a la extensión **mbstring** de PHP. Si el servidor
no la tiene activada (pasa seguido en XAMPP y en Linux), PHP cortaba con un error fatal y respondía HTML en vez de JSON.
El navegador no podía leer la respuesta y el botón "no hacía nada", sin mostrar ningún error.

Cómo se arregló, en tres capas:

1. **Sin dependencias de extensiones opcionales.** Se reemplazó `mb_strlen` por `largoTexto()` / `largoValido()` (en `sanitizar.php`), que funcionan con o sin mbstring. También se quitó `ctype_digit`. Todo se probó en un PHP mínimo sin esas extensiones.
2. **El backend siempre responde JSON.** `conexion.php` ya no muestra avisos de PHP en la respuesta (quedan en el log) y, si ocurre un error grave, igual devuelve `{"status":"error","message":...}` con la causa.
3. **El frontend nunca falla en silencio.** Todos los paneles usan `pedirJSON()` (en `auth.js`): si el servidor responde algo inesperado, se muestra un mensaje en pantalla y la respuesta completa queda en la consola.

Además había otro bug que hacía parecer que los botones no andaban: si hacías dos acciones seguidas, el temporizador del mensaje anterior **ocultaba el mensaje nuevo** a los pocos segundos. Ahora cada mensaje cancela el temporizador del anterior.

## Validación de todos los datos que entran

Todo lo que llega por `$_POST`, `$_GET` o `$_FILES` se sanitiza o se valida según su tipo:

| Tipo de dato | Qué se controla | Dónde |
|---|---|---|
| Texto | `trim`, `strip_tags`, `htmlspecialchars`; largo máximo según la columna; se rechazan arrays | `sanitizar()`, `largoValido()` |
| IDs | Siempre encriptados. `desencriptarId()` exige un entero positivo; un ID manipulado se rechaza | `encriptar.php`, en todos los endpoints |
| Números | Rango permitido: precios, km, potencia, montos y salarios **no pueden ser negativos**; estado 1–10; año 1900–año siguiente; stock ±100.000 por vez | `validarEnteroRango()`, `validarFloatRango()` |
| Fechas | Fecha real (rechaza 2026-02-31); sanciones y transacciones sin fechas futuras | `validarFecha()` |
| Enlaces | Solo `http://` o `https://` (bloquea `javascript:`) | `validarUrl()` |
| Usuarios | Nombre 3–50 caracteres `A-Z 0-9 . _ -`; contraseña 6–72 | `validarNombreUsuario()` |
| Valores fijos | Rol, tipo de sanción, Ingreso/Egreso, Entrada/Salida: solo los de la lista | cada endpoint |
| Imágenes | Tipo real (jpg/png/webp/gif), máximo 5 MB, máximo 20 por envío | `validarImagen()` |
| Imágenes a borrar | Solo se pueden borrar imágenes del mismo vehículo; antes se podía borrar cualquier archivo de `img/` | `modificar_vehiculo.php` |
| Categorías de un vehículo | Llegan encriptadas y se verifica que existan; el ID real de las categorías ya no se publica | `datos_vehiculo.php` |

**Filtros del catálogo:** el precio máximo no acepta negativos. El campo no deja escribir el signo `-`; si igual llega uno, se muestra un aviso y el backend responde error.

Los formularios también tienen `min`, `max` y `maxlength`, pero eso es solo comodidad: **la seguridad está en el backend.**

## Insumos de limpieza: editar y eliminar

- **Editar:** cambia el nombre del producto, sin permitir nombres repetidos (`backend/productos/modificar_producto.php`). El stock se sigue cambiando con + Sumar / - Restar para que quede registrado el gasto.
- **Eliminar** (`backend/productos/eliminar_producto.php`):
  - Si el producto nunca se usó, se borra directamente.
  - Si tiene usos registrados en `Gasta`, pide una segunda confirmación avisando que ese historial también se borra. Esto es necesario porque `Gasta` tiene clave foránea hacia `Productos`.
- Sumar y restar stock ahora usan una transacción con bloqueo de fila, para que dos personas no se pisen el stock.

## Ventas: eliminar una venta durante 1 minuto

- Después de registrar una venta, en el historial aparece **"Eliminar venta (59s)"** con cuenta regresiva. Al llegar a 0, el botón desaparece.
- El servidor también lo controla (`backend/ventas/eliminar_venta.php`): pasado el minuto, o si lo intenta otro usuario, se rechaza aunque alguien fuerce la petición.
- Como la tabla `Ventas` guarda la fecha pero no la hora, el momento exacto de la venta se guarda en la **sesión** de quien la registró. Por eso solo esa persona puede eliminarla, y si cierra sesión ya no aparece el botón.
- Al eliminarla, el vehículo vuelve a "Disponibles".

## Otros arreglos

- **Categorías:** los botones Editar/Eliminar se rompían si el nombre tenía una comilla (por ejemplo `l'auto`). Ahora no se permiten categorías repetidas, y el borrado usa transacción.
- **Vehículos:** la validación está unificada en `backend/vehiculos/datos_vehiculo.php`, que usan tanto el alta como la edición. Se valida que el precio mínimo no supere al precio. Las imágenes se ordenan bien (antes 10 quedaba antes que 2).
- **Marcas:** solo usuarios activos, solo Entrada/Salida. No guarda fecha porque la tabla no tiene esa columna.
- **Catálogo:** el botón de WhatsApp está incluido según la especificación (número 59892484684, mensaje automático con marca y modelo), porque este archivo se modificó para el filtro de precio. Si tu versión tenía algo distinto, el cambio del filtro está en `aplicarFiltros()` y `configurarFiltros()` de `front-end/index/script.js`.

## Archivos nuevos respecto de la entrega original

- `backend/sucursales/` — listar, registrar, eliminar
- `backend/transacciones/` — listar, registrar
- `backend/sanciones/` — listar, registrar
- `backend/aumentos/` — listar, registrar
- `backend/licencias/` — listar, registrar
- `backend/usuarios/listar_bajas.php`, `reactivar_usuario.php`, `datos_usuario.php`
- `backend/productos/modificar_producto.php`, `eliminar_producto.php`, `listar_gastos.php`
- `backend/ventas/eliminar_venta.php`
- `backend/vehiculos/datos_vehiculo.php`
- `front-end/admin/` — `sucursales/`, `transacciones/`, `sanciones/`, `aumentos/`, `licencias/`, `bajas/`, `admin-menu.css`
- `.env.example`
