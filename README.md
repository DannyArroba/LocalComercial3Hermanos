# Tiendita Nexar · Local Comercial Tres Hermanos

Aplicación de tienda con React y una API PHP/MySQL: productos, ventas y comprobantes internos, inventario, abastecimientos, clientes, proveedores y permisos del personal. Incluye dashboard financiero, búsqueda de productos y tema claro/azul marino.

## Restaurar la aplicación con todos los datos

`database/tienda-completa.sql` es una exportación completa de la base local: estructura, clientes, usuarios con sus hashes de contraseña y permisos, productos, proveedores, ventas, comprobantes, abastecimientos, devoluciones, inventario y tablas de trazabilidad. Se publica por solicitud expresa del propietario del proyecto.

Para usar esta copia, crea una base vacía y selecciona esa base en phpMyAdmin; importa **solamente `database/tienda-completa.sql`**. También puedes ejecutar desde la raíz del repositorio:

```powershell
mysql --user=root --password --database=tienda --execute="SOURCE database/tienda-completa.sql;"
```

Configura `backend/config.php` según el apartado siguiente y ejecuta PHP y Vite. Se conservan las cuentas y contraseñas existentes: **no ejecutes `create_admin.php` ni importes además `schema.sql` o `catalog.sql` sobre esta copia**. Para empezar sin datos de clientes ni operaciones, usa la instalación nueva descrita abajo. Importar la copia completa sobre una base existente reemplaza sus tablas; respalda primero cualquier trabajo que quieras conservar.

## Requisitos

- PHP 8.2 o superior con `mysqli`, `mbstring` y `fileinfo`.
- MySQL o MariaDB.
- Node.js 20.19+ o 22.12+ y npm, según los requisitos de Vite 8 utilizado en este proyecto.
- Git.

Los ejemplos siguientes usan PowerShell en Windows. Con XAMPP puedes utilizar `C:\xampp\php\php.exe` y `C:\xampp\mysql\bin\mysql.exe` si esos comandos no están en tu `PATH`.

## Instalación local nueva

### 1. Obtener el código

```powershell
git clone https://github.com/DannyArroba/LocalComercial3Hermanos.git
cd LocalComercial3Hermanos
Copy-Item backend/config.example.php backend/config.php
```

Edita `backend/config.php` con el host, nombre de base, usuario y contraseña de tu servidor MySQL. El archivo local se excluye de Git. También puedes usar las variables `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASSWORD` cuando no exista ese archivo. Los valores de desarrollo de `backend/db.php` son `localhost`, base `tienda`, usuario `root` y contraseña vacía; no son una configuración para producción.

### 2. Preparar una base vacía

Crea una base de datos vacía, por ejemplo `tienda`, con codificación `utf8mb4`. En phpMyAdmin, selecciona esa base e importa en este orden:

1. `database/schema.sql`: estructura actual, sin cuentas, clientes ni operaciones.
2. `database/catalog.sql`: catálogo de productos, códigos y proveedores genéricos; no contiene datos personales de proveedores.

Si utilizas la consola de MySQL desde la raíz del repositorio:

```powershell
mysql --user=root --password --database=tienda --execute="SOURCE database/schema.sql; SOURCE database/catalog.sql;"
```

La contraseña se solicita de forma interactiva. Adapta el usuario y el nombre de base a tu configuración. **Estos archivos son para una instalación nueva; no los importes sobre una base con operaciones que quieras conservar.**

El catálogo contiene precios, costos y stocks de referencia utilizados para el desarrollo. Revisa esos valores para tu negocio. Las imágenes comerciales son referencias de las presentaciones y marcas indicadas.

### 3. Crear el primer administrador

No se publican cuentas ni contraseñas predeterminadas. El siguiente bloque solicita tus datos sin guardar la contraseña como texto en el historial de comandos:

```powershell
$env:APP_ADMIN_NAME = Read-Host 'Nombre del administrador'
$env:APP_ADMIN_EMAIL = Read-Host 'Correo del administrador'
$adminSecret = Read-Host 'Contraseña (mínimo 12 caracteres)' -AsSecureString
try {
    $env:APP_ADMIN_PASSWORD = [System.Net.NetworkCredential]::new('', $adminSecret).Password
    php scripts/create_admin.php
} finally {
    Remove-Item Env:APP_ADMIN_PASSWORD, Env:APP_ADMIN_NAME, Env:APP_ADMIN_EMAIL -ErrorAction SilentlyContinue
    $adminSecret.Dispose()
    Remove-Variable adminSecret
}
```

El script solo funciona por CLI y solo crea una cuenta cuando todavía no existe un administrador. Guarda la contraseña mediante hash; no cambia roles ni restablece cuentas existentes. Después puedes gestionar trabajadores en **Personal**, asignarles nombre y activar sus secciones. Por defecto, los trabajadores acceden a Productos y ventas e Inventario.

### 4. Ejecutar la aplicación

En una terminal, desde la raíz del repositorio:

```powershell
php -S 127.0.0.1:8000 -t .
```

En otra terminal:

```powershell
cd react-frontend
npm.cmd ci
npm.cmd run dev
```

Abre [http://127.0.0.1:5173](http://127.0.0.1:5173). Vite envía las solicitudes `/api` al servidor PHP del puerto 8000; por eso el servidor PHP debe arrancar desde la raíz del proyecto. MySQL/MariaDB debe estar activo.

## Una base existente

Respalda la base completa antes de aplicar migraciones. Si ya tienes un administrador, conserva su cuenta y no ejecutes la inicialización de una base nueva.

Las migraciones SQL se encuentran en `backend/migrations/`. Para las columnas de accesos y nombres de trabajadores hay un ejecutor idempotente que exige un respaldo previo:

```powershell
php scripts/migrate_user_access.php --apply RUTA_DEL_RESPALDO_SQL
php scripts/backfill_staff_names.php --apply RUTA_DEL_RESPALDO_SQL
```

Revisa las otras migraciones según la versión de tu base, incluyendo códigos alternativos de productos y correo compartido entre clientes. No importes `database/schema.sql` ni `database/catalog.sql` como una actualización de datos existentes.

## Compilación y comprobaciones

```powershell
cd react-frontend
npm.cmd run build
node --test src/utils/access.test.js src/utils/productSearch.test.js src/utils/financialChart.test.js
```

Desde la raíz, `php scripts/test_user_access.php`, `php scripts/test_dashboard_options.php` y `php scripts/test_contact_data.php` comprueban permisos, períodos y datos de prueba. Las comprobaciones de integración del dashboard requieren una base configurada y un administrador existente; los casos con tablas temporales no modifican las operaciones reales.

## Datos incluidos y despliegue

Este repositorio incluye el código, las imágenes, las migraciones, el catálogo de referencia y la exportación completa `database/tienda-completa.sql`, incluidos sus clientes, ventas y cuentas. Las contraseñas de los usuarios están almacenadas como hashes. Las credenciales del servidor MySQL y los archivos de configuración locales se configuran en cada instalación.

Los scripts de generación y corrección de datos son herramientas administrativas de CLI para entornos de prueba. No se ejecutan automáticamente ni convierten datos ficticios en operaciones reales. Los comprobantes de la aplicación son para control interno y no tienen validez tributaria ante el SRI.

Subir cambios a GitHub no los despliega por sí solo en un dominio. Para un hosting PHP/MySQL, revisa `deployment/LEEME-HOSTINGER.txt`, configura las credenciales del hosting y prepara un paquete nuevo con la aplicación compilada y su base de datos.
