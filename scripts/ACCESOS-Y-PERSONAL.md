# Accesos del personal y atribución de operaciones

Implementado y aplicado en la base local el 18 de noviembre de 2026.

En Personal → Editar/Nuevo usuario se configuran nombre, rol y seis accesos independientes: Dashboard, Productos y ventas, Inventario, Transacciones, Proveedores y Clientes. Los trabajadores reciben Productos y ventas e Inventario por defecto; una lista explícita vacía desactiva todas las secciones. Los administradores conservan control completo. Un administrador no puede eliminarse o quitarse su rol; no se elimina un usuario con operaciones registradas.

Los permisos se consultan desde la base en cada solicitud protegida, sin confiar en el rol antiguo de la sesión. Menús, rutas, carrito y accesos directos aplican los mismos permisos. La sesión de la interfaz se actualiza al enfocar la ventana y cada 30 segundos. El registro nunca acepta una promoción a administrador enviada por el cliente. Las rutas PHP anteriores también comprueban los accesos.

El formulario de inventario conserva la consulta limitada de proveedores para elegirlos al abastecer, aunque no tenga habilitada la gestión de proveedores. La caja puede buscar un cliente por cédula sin habilitar su página de gestión. Un usuario con solo Productos y ventas puede consultar sus propios comprobantes, pero no el historial general ni los comprobantes de otros trabajadores.

Cada venta guarda `purchases.served_by_name`; cada abastecimiento, `stock_purchases.requested_by_name`; cada devolución, `sales_returns.handled_by_name`. El servidor obtiene el nombre del usuario autenticado. Los historiales, detalles y comprobantes PDF/HTML muestran estos nombres. Editar posteriormente el perfil no reemplaza el nombre guardado en operaciones anteriores.

Se añadieron `users.name` y `users.permissions_json`; el rol válido de trabajador se guarda como `cashier`. Se corrigió el valor vacío anterior del trabajador y se conservó el administrador existente. Los nombres iniciales se obtuvieron del nombre de usuario que ya figuraba en el correo, sin inventar identidades. Se completaron los nombres de 5.732 ventas, 2 devoluciones y 2.867 abastecimientos a partir de sus `user_id` existentes.

Los importes y cantidades de las ventas, sus identificadores y las 4.557 unidades del inventario no cambiaron.

## Respaldo y migración

Respaldo completo anterior: `C:\Users\danny\AppData\Local\TienditaNexar\backups\accesos-personal-20261118-231911\antes.sql`.

Para aplicar en otra copia de la base, respaldar primero y ejecutar:

```text
php scripts/migrate_user_access.php --apply RUTA_RESPALDO_SQL
php scripts/backfill_staff_names.php --apply RUTA_RESPALDO_SQL
```

Los dos scripts son exclusivos de CLI e idempotentes. La aplicación requiere la migración antes de utilizar estos campos.

## Verificación

- `php scripts/test_user_access.php`: 16 comprobaciones de permisos, valores por defecto, denegación y administración.
- `node --test src/utils/access.test.js` desde `react-frontend`: cuatro casos de navegación y accesos.
- Pruebas HTTP aisladas: 26 comprobaciones de permisos, CRUD, sesiones con rol antiguo, registro sin escalamiento, caja, devoluciones y abastecimientos. Se usaron tablas temporales por conexión: no modificaron filas, stock ni numeración reales. Herramientas privadas en `%TEMP%\tiendita-chart-qa\access-http.mjs` y `access-router.php`; el servidor de prueba exige un secreto efímero y se detiene al terminar.
- Pruebas de navegador: activadores, nombre y guardado; rutas y menús restringidos; revocación de accesos al enfocar; pantalla sin accesos y formulario móvil.
- Consultas de los historiales existentes: todas las operaciones tienen nombre del trabajador y un vendedor sin permiso de transacciones solo consulta sus propios comprobantes.
- Compilación del frontend, 13 pruebas existentes de utilidades y sintaxis de 28 archivos PHP correctas.
