# Contactos ficticios y tema azul marino

Actualización local del 18 de noviembre de 2026.

- Se corrigieron los correos de 217 clientes generados: `nombre.apellido@gmail.com` o `nombre.apellido@hotmail.com`, sin etiquetas ni sufijos numéricos. Los nombres de cuatro palabras usan el primer nombre y el primer apellido. Una coincidencia de dirección se conserva como correo compartido, sin fusionar clientes.
- Se variaron los celulares de ese grupo: diez dígitos, inicio 09 y combinaciones distintas en el resto del número; prefijos 093–099, sin repeticiones nuevas ni bloques largos de ceros.
- Se actualizaron las copias de contacto de 3.101 comprobantes. Ninguna devolución correspondía al grupo modificado. Los otros ocho contactos existentes se conservaron.
- No cambiaron los nombres, cédulas, IDs, ventas, importes ni stock. Las identidades continúan siendo datos ficticios del lote identificado internamente como simulado. No se contactó a ninguna dirección ni número.
- El generador de historial también usa el formato de correo corto y celulares variados para futuras cargas.

Respaldo completo anterior y auditoría privada de cada cambio:

`C:\Users\danny\AppData\Local\TienditaNexar\backups\contactos-tema-20261118-232859`

Archivos: `antes.sql`, `cambios-contactos.json` y `verificacion.json`. La auditoría contiene datos personales anteriores y debe permanecer fuera de la aplicación pública.

El botón de sol/luna de la barra superior cambia entre claro y azul marino. Se guarda en `localStorage` con la clave `tiendita-theme`; se restaura al recargar y se sincroniza entre pestañas. `ThemeContext` aplica `data-theme` al documento y los estilos cubren páginas, tablas, formularios, modales, alertas, menú móvil y gráfica. Las fotografías mantienen sus colores. Cambiar el tema de la gráfica conserva su período, datos y zoom.

Comprobación: no quedan correos con etiquetas ni celulares `0900…` en el grupo corregido y sus comprobantes; contactos copiados conciliados; ventas 5.732, total 84.388,82 USD y stock 4.557 unidades conservados. `scripts/test_contact_data.php` valida nombres con tildes y 250 celulares únicos con distintos prefijos. Las pruebas de navegador cubren ambos temas, persistencia, formularios, alertas, barra a 320 px y el canvas de la gráfica.
