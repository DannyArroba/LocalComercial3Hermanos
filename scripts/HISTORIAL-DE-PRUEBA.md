# Historial de prueba de la tienda

**Actualización posterior del 18 de noviembre:** el historial activo empieza ahora el 1 de mayo. Se archivaron 13 ventas anteriores, sus 20 líneas, 15 registros de inventario y 327 movimientos del registro de trazabilidad. La apertura se trasladó al 1 de mayo sin cambiar las 4.557 unidades de stock. El comprobante #42 pasó a #5750 conservando su contenido y sus referencias. El historial activo tiene 5.732 ventas. El respaldo completo previo a este ajuste está en `C:\Users\danny\AppData\Local\TienditaNexar\backups\inicio-mayo-20261118-224403\antes.sql`; los registros retirados y la correspondencia de IDs se conservan en `local_history_archives`, `local_history_archive_rows` y `local_history_id_changes`. El resto de este documento describe la carga inicial, anterior a ese archivo.

El dashboard limita los períodos a fechas transcurridas de Ecuador: hoy la serie mensual tiene 18 días y la anual siete meses, mayo–noviembre. El siguiente día/mes se habilita según la fecha del servidor, sin fijar el 18 en el código. Las etiquetas de colores del eje derecho indican el último valor de cada línea. Los avisos tienen parámetros separados: stock crítico ≤5 y por acabarse 6–10, personalizables e inclusivos; el límite 5 pertenece al primer grupo por defecto para evitar duplicarlo. Los ajustes admiten cero. Al finalizar hay 31 productos críticos, 64 por acabarse y ninguno realmente agotado.

**Indicadores de ganancia:** a petición del usuario, la tarjeta y la línea naranja muestran «Ganancia estimada con IVA»: cobros netos de devoluciones menos el costo histórico vendido, neto del costo devuelto. El campo API `profitIncludingVat` contiene la estimación visible y `grossProfit` conserva el cálculo sin IVA; el antiguo `netProfit` sigue siendo alias de este último para compatibilidad. La estimación visible no representa utilidad neta después de liquidar impuestos y gastos operativos. Se quitó el texto «ventas sin IVA» de la interfaz. El IVA de las devoluciones se revierte sin duplicar cabeceras de varias líneas. Las pruebas mensual/anual verifican ambos resultados mediante tablas temporales sin alterar ventas, inventario ni numeración reales (`scripts/test_dashboard_profit.php`).

Carga local aplicada el 18 de noviembre de 2026. Lote `historial-ficticio-2026-mayo-v1`, ID 2. Todos los registros generados son ficticios; no representan operaciones reales ni documentos tributarios. El PDF de comprobantes de la aplicación conserva su indicación de control interno sin validez ante el SRI.

## Resultado

- 5.707 ventas nuevas entre el 1 de mayo y el 18 de noviembre de 2026: 202 días completos, con 15–42 ventas diarias contando las anteriores.
- 48.742 líneas nuevas: 5–14 productos distintos en cada venta nueva, con cantidades enteras y compras temáticas de despensa, desayuno, snacks, limpieza y bebidas.
- 3.101 ventas nuevas con datos y 2.606 de consumidor final. Todas las nuevas desde USD 13 llevan cliente; algunas menores también. Esta es una regla de generación solicitada, no una afirmación sobre obligaciones tributarias.
- 210 clientes ficticios nuevos con nombres de uso ecuatoriano y direcciones referenciales de El Coca. Aproximadamente 80% de sus identificadores empieza por 22. Los identificadores tienen un dígito verificador deliberadamente inválido: no son cédulas utilizables. Los teléfonos usan el patrón de prueba `0900xxxxxx`; los correos de Gmail/Hotmail incluyen `prueba.nexar`. No se envió ningún mensaje.
- 8 clientes anteriores recuperados desde comprobantes existentes y vinculados a sus ventas. Se conservaron los datos originales de estos comprobantes.
- 2.862 abastecimientos nuevos con proveedores existentes, costo, solicitud, pago y recepción. Las cantidades son múltiplos de una presentación estimada: 12 para cerveza, 6/24 para bebidas según tamaño, 12/24 para varios envasados, 25 para Polito, etc. Las cantidades por caja son supuestos de la simulación, no especificaciones verificadas del fabricante.
- Reposición antes de que las salidas dejen cuatro unidades o menos; sacos grandes y tortas usan un umbral menor para no mantener cantidades excesivas. Los abastecimientos generados se registran por producto, igual que el flujo actual de la aplicación.
- 51.664 registros en `inventory_logs` y 51.978 movimientos completos en `demo_stock_ledger`. Este último incluye apertura, ventas, abastecimientos y las dos devoluciones anteriores.
- Stock final: 4.557 unidades en 306 productos, entre 1 y 28 unidades por producto. Los saldos bajos corresponden a presentaciones voluminosas; ningún saldo es negativo.

La cifra de ingresos de las ventas nuevas es **USD 83.964,16**, calculada con los precios, costos y reglas de IVA actuales del catálogo. No se investigaron ni afirmaron precios históricos para cada mes.

## Resumen mensual

Incluye las ventas anteriores del período, cuyos importes se conservaron.

| Mes de 2026 | Ventas | Total vendido USD |
|---|---:|---:|
| Mayo | 892 | 13.198,06 |
| Junio | 816 | 11.684,67 |
| Julio | 880 | 12.625,33 |
| Agosto | 802 | 12.123,06 |
| Septiembre | 874 | 13.409,42 |
| Octubre | 924 | 13.270,51 |
| Noviembre, hasta el 18 | 544 | 8.077,77 |

## Conservación y trazabilidad

Las 38 ventas anteriores mantienen sus productos, cantidades, costos, precios, IVA e importes. Se redistribuyeron sus horas dentro de 08:00–20:00 conservando el orden temporal dentro del día. Se hizo lo mismo con las fechas de solicitud, pago, recepción, reversa y devoluciones anteriores. Las ventas de enero y abril permanecen en sus fechas originales; no se trasladaron a mayo. La reposición que necesita la reproducción de esos movimientos antiguos también se registra en su fecha correspondiente.

Se reemplazaron únicamente los correos explícitos `@example.com` por direcciones ficticias de Gmail con la marca `prueba.nexar`, en clientes y en las copias de sus comprobantes. Los contactos anteriores con otros dominios se conservaron.

Los saldos de apertura son una reconstrucción a partir del stock previo a la carga y de los movimientos existentes. No son una compra adicional. El producto histórico eliminado, ID 4, conserva su detalle y saldo histórico separado; no se recreó para la venta ni recibió abastecimientos.

`demo_data_batches.is_simulated=1` identifica el lote. `demo_data_records` relaciona cada alta o modificación con su tabla e ID y conserva la fila original cuando se modificó. `demo_stock_ledger` relaciona cada movimiento con el comprobante y su línea. Las tablas antiguas `sales`, `sale_items` e `invoices` permanecen vacías: duplicar allí las ventas de `purchases` produciría una segunda fuente de contabilidad innecesaria.

La fecha de creación de un producto, proveedor o usuario sigue indicando cuándo se registró su ficha en el sistema; no se falsificó para aparentar que el sistema ya existía cuando ocurrieron las operaciones retroactivas de prueba.

Se permitió compartir correo entre clientes manteniendo la CI como identificador único. Había comprobantes anteriores con distintas cédulas y el mismo correo; ahora pueden conservarse completos sin inventar otro contacto. Migración: `backend/migrations/20261118_shared_customer_email.sql`.

Los detalles de transacciones ahora usan el nombre guardado en el comprobante y conservan líneas de productos eliminados. Los historiales de transacciones y abastecimientos muestran 50 filas por página; la búsqueda de transacciones sigue abarcando todos los registros.

## Respaldo y comprobaciones

Directorio privado, fuera de la aplicación y del repositorio:

`C:\Users\danny\AppData\Local\TienditaNexar\backups\historial-20261118-221539`

- `antes.sql`: respaldo completo anterior a la carga.
- `plan-historial.json`: plan detallado y copia de las filas originales. Contiene información personal anterior; mantenerlo privado.
- `aplicado.json`: resumen de aplicación.
- `verificacion.json`: 14 comprobaciones de integridad realizadas antes de confirmar la transacción y al terminar.
- `auditoria-final.json`: conciliación adicional de IVA por línea, referencias, saldos y preservación de importes originales.

La carga es exclusiva de CLI, exige respaldo, comprueba que la base no haya cambiado desde el plan y usa una transacción con bloqueo de las filas originales. Un error revierte la carga. La clave única del lote y la huella del origen evitan repetirla accidentalmente.

`scripts/historial_demo.php --verify DIRECTORIO_RESPALDO` comprueba el estado al terminar la carga. Tras registrar operaciones reales posteriores, los saldos ya no coincidirán con esta foto histórica, por lo que debe conciliarse también ese movimiento posterior.

`scripts/verificar_historial_app.php dashboard|annual|details` verifica las respuestas del backend mediante un contexto CLI, sin crear una sesión HTTP autenticada. Pasaron las consultas mensuales, anuales y el caso del producto eliminado. La aplicación sirve sus 306 productos por HTTP. También pasaron la compilación del frontend, las nueve pruebas de búsqueda/gráfica y la comprobación de sintaxis PHP.

Restaurar `antes.sql` reemplazaría el estado completo de la base de datos; antes de restaurarlo se debe respaldar cualquier trabajo posterior. No hay restauración automática ni borrado de ventas posteriores.
