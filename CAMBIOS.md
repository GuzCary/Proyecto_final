# Cambios de esta versión

Copiá el contenido del zip encima de la carpeta `primera entrega/` (reemplazando los archivos).
El zip trae **solo archivos nuevos o modificados**: no toca `front-end/index/` (el botón de WhatsApp queda como lo tengas) ni tu `.env`.

## Pasos para que funcione

1. **Base de datos** (elegí una):
   - Volver a correr `script.sql` completo (borra y recrea todo), y después abrir `backend/util/actualizar_contraseñas.php`.
   - O, si querés conservar los datos, correr solo `dev/migracion_db_existente.sql`.
2. **`.env`**: tiene que tener las claves que lee `env_load.php`. Mirá `.env.example` (`DB_USER_PUBLIC`, `DB_PASS_PUBLIC`, `DB_USER_GESTION`, `DB_PASS_GESTION`). El `.env` de la entrega todavía tenía `DB_USER` / `DB_PASS`, que el código ya no usa.

## Módulos nuevos (menú `⚙ Config ▾` del panel admin)

| Módulo | Frontend | Backend |
|---|---|---|
| Sucursales (CRUD) | `front-end/admin/sucursales/` | `backend/sucursales/listar_sucursales.php`, `registrar_sucursal.php`, `eliminar_sucursal.php` |
| Transacciones de caja | `front-end/admin/transacciones/` | `backend/transacciones/listar_transacciones.php`, `registrar_transaccion.php` |
| Licencias de conducir | `front-end/admin/licencias/` | `backend/licencias/listar_licencias.php`, `registrar_licencia.php` |
| Sanciones | `front-end/admin/sanciones/` | `backend/sanciones/listar_sanciones.php`, `registrar_sancion.php` |
| Aumentos de sueldo | `front-end/admin/aumentos/` | `backend/aumentos/listar_aumentos.php`, `registrar_aumento.php` |
| Historial de bajas | `front-end/admin/bajas/` | `backend/usuarios/listar_bajas.php`, `reactivar_usuario.php` |

El menú también tiene accesos a **Ventas** y **Insumos de limpieza**: el backend ya permitía al admin, pero el frontend lo mandaba al login. Ahora el admin entra y ve un botón "← Panel admin" para volver.

Estilos del menú y de las tablas: `front-end/admin/admin-menu.css` (compartido, se incluye en todas las páginas de `/admin`). El JS del menú está en `front-end/auth.js` (`activarMenuConfig()`).

### Reglas de negocio implementadas

- **Sucursales**: no se puede eliminar una que tenga vehículos, usuarios o transacciones; el mensaje dice cuántos tiene de cada uno. No se permiten dos con el mismo nombre.
- **Bajas**: el nombre de un usuario dado de baja se puede reutilizar. "Reactivar" borra el registro de `Baja`, pero solo si ningún usuario activo tomó ese nombre.
- **Sanciones**: tipos fijos (Observación, Advertencia, Suspensión, Descuento), sin fechas futuras, un admin no se sanciona a sí mismo. Son historial: no se editan ni borran.
- **Aumentos**: por porcentaje o monto fijo; además de guardarse en `AumentosDeSueldo`, actualizan `Usuarios.salarioPorHora` dentro de una transacción. Requiere que el empleado tenga salario cargado.
- **Transacciones**: Ingreso/Egreso con monto positivo, filtro por sucursal y fechas, totales y balance.
- **Licencias**: muestra si está vigente, por vencer (30 días) o vencida. Los usuarios nuevos se crean **sin licencia** (antes todos quedaban apuntando a la licencia id 1, y editarla le cambiaba la licencia a todos).

## Cambios en archivos existentes

- `backend/config/env_load.php`: si las variables no están en `$_SERVER`, las lee del archivo `.env`.
- `backend/config/conexion.php`: zona horaria de Uruguay en PHP y MySQL; errores de conexión en JSON.
- `backend/seguridad/sanitizar.php`: nueva función `validarFecha()`.
- `backend/seguridad/login.php`: si hay un usuario dado de baja y uno activo con el mismo nombre, entra el activo.
- `backend/usuarios/agregar_usuario.php` y `modificar_usuario.php`: sucursal (select dinámico) y salario por hora; ignoran a los dados de baja al chequear nombres repetidos; un admin no se puede quitar su propio rol; al cambiar de rol ya no falla si el usuario tiene historial (sanciones, gastos).
- `backend/usuarios/listar_usuarios.php`: devuelve sucursal, salario y cuál es el usuario logueado (para ocultar "Dar de baja" sobre uno mismo).
- `backend/vehiculos/*`: la sucursal viaja encriptada; el alta usa transacción; al editar se pueden desmarcar todas las categorías; no hay warnings si falta un campo.
- `backend/ventas/registrar_venta.php`: ya no deja vender dos veces el mismo vehículo (el comentario decía que lo chequeaba pero no lo hacía).
- `backend/marcas/*`: las marcas guardan **fecha** además de hora, solo aceptan Entrada/Salida y los usuarios dados de baja no pueden marcar.
- `backend/util/actualizar_contraseñas.php`: conecta con `usr_gestion` (con `usr_publico` el UPDATE fallaba por permisos).
- `script.sql`: columna `fecha` en `RegistroMarca` y el comando para ponerle contraseña a root (comentado, hacerlo a mano).
- `front-end/auth.js`: `verificarSesion()` acepta un rol o una lista de roles.
- `dev/pendientes.md`: actualizado.
