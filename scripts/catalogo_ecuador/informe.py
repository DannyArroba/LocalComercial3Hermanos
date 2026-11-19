"""Informe de la carga aplicada y etiquetas EAN-13 imprimibles sin dependencias."""
import html
import json
from pathlib import Path
import sys
from investigar import ROOT

audit=json.loads(Path(sys.argv[1]).read_text(encoding='utf-8'))
rows=audit['products']
suppliers={1:'Distribuidora Amazonica Paredes',2:'Comercializadora Andina del Oriente',3:'Alimentos Orellana Cia. Ltda.'}
h=html.escape

def ean_svg(code):
    assert len(code)==13
    L=['0001101','0011001','0010011','0111101','0100011','0110001','0101111','0111011','0110111','0001011']
    G=['0100111','0110011','0011011','0100001','0011101','0111001','0000101','0010001','0001001','0010111']
    P=['LLLLLL','LLGLGG','LLGGLG','LLGGGL','LGLLGG','LGGLLG','LGGGLL','LGLGLG','LGLGGL','LGGLGL']
    bits='101'+''.join((L if p=='L' else G)[int(c)] for p,c in zip(P[int(code[0])],code[1:7]))+'01010'
    bits+=''.join(''.join('1' if b=='0' else '0' for b in L[int(c)]) for c in code[7:])+'101'
    assert len(bits)==95
    bars=''.join(f'<rect x="{11+i}" y="1" width="1" height="50"/>' for i,b in enumerate(bits) if b=='1')
    return f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 117 66" role="img" aria-label="{code}"><rect width="117" height="66" fill="white"/><g fill="black" shape-rendering="crispEdges">{bars}</g><text x="58.5" y="62" text-anchor="middle" font-family="monospace" font-size="8">{code}</text></svg>'

notes='''Carga aplicada a la base de datos local el 18 de noviembre de 2026. Se crearon 46 productos y se actualizaron los 15 anteriores, conservando sus IDs. No se modificaron compras, ventas, devoluciones ni comprobantes históricos.

Los 45 renglones de la lista no tenían duplicados exactos entre sí. Nueve coincidían con productos existentes: arroz por libra, pollo, chocolate, agua de 500 ml, néctar de 1 L, leche de 1 L, queso de 500 g, pan de molde y mayonesa. Se actualizaron esos registros en lugar de crear copias. Las otras seis presentaciones existentes se conservaron y actualizaron. Se agregaron diez complementos: margarina, sardina, maicena, servilletas, lavavajilla, suavizante, Volt y tres cervezas.

Precios en USD. PVP es el precio FINAL unitario con IVA incluido; la app almacena el precio base sin IVA. Al mantener su precisión de dos decimales, algunos PVP difieren como máximo $0,01 de la propuesta inicial. Las referencias comerciales no garantizan el costo mayorista ni el precio en cada ciudad. Se escogieron alternativas económicas; el Red Bull de 250 ml no cuesta $1,00, por eso también se añadió Volt de 400 ml como alternativa económica. La gaseosa familiar consultada es de 3,05 L y el agua de 1,5 L seleccionada es con gas.

Los costos de compra son ESTIMACIONES y deben reemplazarse con facturas reales. Los proveedores son asignaciones administrativas entre los tres proveedores existentes: no se verificó que distribuyan cada marca. Los stocks son cantidades iniciales propuestas, no un conteo físico confirmado. Los grupos de almacén de 6, 10, 12 o 24 no certifican cajas de fábrica; las jabas retornables de litro sí se referenciaron como 12 botellas. Se cargaron 36 Pilsener de litro y 36 Pilsener Light de litro, tres jabas de cada una. La cerveza negra real añadida es Ballesta de 300 ml; no se inventó una negra de litro. El precio de las retornables supone intercambio de envase; el sistema no administra depósitos de envases.

Hay 45 códigos comerciales contrastados con los catálogos de las presentaciones y 16 EAN-13 internos con dígito verificador válido. Los internos NO son códigos oficiales del fabricante: imprimir y pegar sus etiquetas. Los códigos comerciales deben contrastarse con el empaque físico recibido; un cambio de envase puede cambiar el código. El arroz Real de 5 kg normaliza con un cero inicial el UPC que la fuente publica con 11 dígitos. El lector debe conservar ceros iniciales. No se realizó una prueba con lector físico.

Para productos a granel, el stock es por FUNDA/PORCIÓN de peso fijo: 454 g por libra o 1 kg para pollo, papa, tomate y cebolla. No se configuró una balanza ni lectura de códigos de peso variable. Los huevos se venden por cubeta de 30; cada pañal se descuenta por paquete de 20; el jabón Jolly se vende por barra de 100 g, no por el tripack de la fotografía.

IVA 0% aplicado a alimentos básicos sin transformar, leche entera nacional sin añadidos, yogures, queso, aceites comestibles que no sean de oliva, azúcar, sal, avena, harina, pasta seca, maicena, margarina y conservas nacionales de atún/sardina. Mantequilla, café procesado, snacks, bebidas y artículos de limpieza/cuidado personal seleccionados: 15%. Toallas sanitarias: 0%. Pañales Huggies Active Sec convencionales: 15%; no se asumió que fueran pañales populares. Pan blanco simple: 0%, sujeto a que el producto recibido cumpla INEN 2945. Revisar la factura y composición del producto recibido ante cambios de marca o formulación.

Las fotos comerciales se guardaron localmente para evitar enlaces rotos. Algunas fotos de alimentos/fraccionados son referenciales; no acreditan marca, peso ni lote. Las fotos de Commons conservan atribución y licencia abajo. Las imágenes comerciales conservan sus titulares y fuente; disponer de la imagen no otorga una licencia de redistribución.
'''

md='# Catálogo aplicado — Tiendita Nexar\n\n'+notes+'\n'
md+='\n| ID | Producto | PVP con IVA | IVA | Costo estimado sin IVA | Stock | Código | Tipo |\n|---|---|---:|---:|---:|---:|---|---|\n'
for r in rows:
    md+=f"| {r['id']} | {r['name']} | ${r['pvp']} | {15 if r['applies_iva'] else 0}% | ${r['cost_price']} | {r['stock']} | {r['barcode']} | {'Interno' if r['barcode_type']=='interno' else 'Comercial'} |\n"
md+='\n## Referencias por producto\n\n'
for r in rows:
    md+=f"- **{r['name']}**: [referencia comercial]({r['source_url']}); precio consultado ${r['reference_price'] or 'no disponible para la unidad exacta'}. {r['pack']}. Proveedor asignado: {suppliers[r['supplier_id']]}. {r['image_note']}.\n"
    if r.get('photo_attribution'):
        a=r['photo_attribution']; m=a['metadata']
        artist=__import__('re').sub('<[^>]*>','',m.get('Artist',{}).get('value','Autor indicado en Commons'))
        license=m.get('LicenseShortName',{}).get('value','Consultar licencia en la fuente')
        md+=f"  Foto: [{a['title']}]({a['url']}), {artist}, {license}.\n"
    elif r.get('photo_source'):
        md+=f"  [Foto de referencia]({r['photo_source']}).\n"
md+='''
## Fuentes generales

- [SRI, circular NAC-DGECCGC26-00000005: alimentos, leche y pan](https://www.sri.gob.ec/o/sri-portlet-biblioteca-alfresco-internet/descargar?id=a3e4257d-ba2e-4ec7-89c7-635fa764b22a&nombre=NAC-DGECCGC26-00000005.pdf).
- [Circular SRI NAC-DGECCGC26-00000002, copia publicada](https://www.teleamazonas.com/uploads/files/2026/04/01/circular-SRI-alimentos.pdf).
- [SRI: tarifa 0% en toallas sanitarias y pañales populares](https://www.sri.gob.ec/web/intersri/ley-organica-para-el-desarrollo-economico-y-sostenibilidad-fiscal-tras-la-pandemia-covid-19).
- [SRI: reglamento y definición de pañal popular, art. 196(1)](https://www.sri.gob.ec/o/sri-portlet-biblioteca-alfresco-internet/descargar/fe699a5c-a49a-42e1-a71f-61d66e752ed8/Reglamento%20para%20la%20Aplicaci%C3%B3n%20de%20la%20Ley%20de%20R%C3%A9gimen%20Tributario%20Interno.pdf).
- [MAG/SIPA: referencias mayoristas de septiembre de 2026](https://sipa.agricultura.gob.ec/boletines/nacionales/mayoristas/2026/precios_mayoristas_septiembre_2026.pdf). Referencia para productos fraccionados; no equivale al costo real del proveedor.
- [Tía: gaseosas](https://www.tia.com.ec/supermercado/aguas-y-bebidas/bebidas-gaseosas-y-maltas).
- [Ecuador Cupón: presentaciones retornables y jabas](https://tienda.ecuadorcupon.com/16-alcoholicas).

## Respaldo y trazabilidad

'''
md+='Respaldo completo de la base de datos anterior, fuera de la carpeta pública:\n\n`'+audit['backup']+'`\n\n'
md+='En esa misma carpeta están `productos-antes.json` y `resultado-catalogo.json`, con valores anteriores, cambios aplicados y comprobación del historial. La carga fue una transacción: no creó facturas de compras ficticias.\n\n'
md+='[Abrir etiquetas de los 16 códigos internos](etiquetas-internas.html). Imprimir al 100%, sin ajustar al ancho de página; comprobar con el lector antes de etiquetar todo el lote.\n'
(ROOT/'INFORME.md').write_text(md,encoding='utf-8')

cards=[]
for r in rows:
    if r['barcode_type']=='interno':
        cards.append(f'<article><b>{h(r["name"])}</b><strong>${r["pvp"]}</strong>{ean_svg(r["barcode"])}<small>Código interno · precio final con IVA</small></article>')
labels='''<!doctype html><html lang="es"><meta charset="utf-8"><title>Etiquetas internas — Tiendita Nexar</title><style>
@page{size:A4;margin:10mm}*{box-sizing:border-box}body{font-family:Arial,sans-serif;margin:10mm;color:#111}header{max-width:180mm;margin-bottom:8mm}h1{font-size:20px}p{font-size:13px}.labels{display:grid;grid-template-columns:repeat(3,58mm);gap:3mm}article{width:58mm;height:48mm;border:1px dashed #aaa;padding:3mm;break-inside:avoid;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;background:white}b{font-size:10px;height:10mm}strong{font-size:16px}svg{width:46mm;height:26mm;flex-shrink:0}small{font-size:7px}@media print{body{margin:0}header{display:none}article{border-color:#ddd}}</style>
<header><h1>Etiquetas de códigos internos</h1><p>16 productos · imprimir al 100% en A4. No sustituir códigos comerciales de otro producto. Verificar la lectura física antes de pegar todo el lote. Cada etiqueta corresponde a la unidad de venta indicada.</p><button onclick="window.print()">Imprimir etiquetas</button></header><main class="labels">'''+''.join(cards)+'</main></html>'
(ROOT/'etiquetas-internas.html').write_text(labels,encoding='utf-8')
table=[]
for r in rows:
    note=r['image_note']
    table.append(f'<tr><td><img src="../../frontend/uploads/{h(r["image"])}" alt="{h(r["name"])}" title="{h(note)}" loading="lazy"></td><td><b>{h(r["name"])}</b><small>{h(r["category"])} · {h(r["action"])} · ID {r["id"]}</small></td><td class="money">${r["pvp"]}</td><td>{15 if r["applies_iva"] else 0}%</td><td>${r["cost_price"]}</td><td>{r["stock"]}<small>{h(r["pack"])}</small></td><td><code>{r["barcode"]}</code><small>{"Interno: imprimir etiqueta" if r["barcode_type"]=="interno" else "Comercial contrastado"}</small></td><td>{h(suppliers[r["supplier_id"]])}<small><a href="{h(r["source_url"])}">Referencia comercial</a></small></td></tr>')
page='''<!doctype html><html lang="es"><meta charset="utf-8"><title>Catálogo aplicado — Tiendita Nexar</title><style>
body{font-family:system-ui,sans-serif;color:#1e293b;background:#f8fafc;margin:0;padding:32px}main{max-width:1400px;margin:auto}header{background:#064e3b;color:white;padding:28px;border-radius:18px}h1{margin:0 0 8px}.stats{display:flex;gap:20px;margin:20px 0}.stat{background:white;padding:18px;border-radius:12px;flex:1;border:1px solid #e2e8f0}.stat b{font-size:28px;display:block;color:#047857}a{color:#047857}header a{color:white}table{width:100%;border-collapse:collapse;background:white;font-size:13px;margin-top:22px}th{text-align:left;background:#e2e8f0;padding:12px}td{padding:12px;border-bottom:1px solid #e2e8f0}td img{width:64px;height:70px;object-fit:contain}small{display:block;color:#64748b;margin-top:5px;font-size:11px}.money{font-size:16px;font-weight:750;white-space:nowrap}.notes{background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:18px;line-height:1.5}input{padding:12px;width:100%;box-sizing:border-box;margin-top:20px;border:1px solid #cbd5e1;border-radius:9px}code{font-size:11px}@media(max-width:800px){body{padding:12px}.stats{flex-wrap:wrap}.wrap{overflow:auto}td{min-width:90px}}</style><main>
<header><h1>Catálogo de Tiendita Nexar</h1><div>Aplicado el 18 de noviembre de 2026 · USD · precios finales con IVA</div><p><a href="INFORME.md">Informe completo y fuentes</a> · <a href="etiquetas-internas.html">Imprimir etiquetas internas</a></p></header>
<div class="stats"><div class="stat"><b>61</b>productos activos</div><div class="stat"><b>46</b>nuevos</div><div class="stat"><b>15</b>actualizados</div><div class="stat"><b>16</b>etiquetas internas</div></div>
<div class="notes"><b>Datos propuestos para el inventario inicial.</b> Costos y stocks estimados: confirmar con facturas y conteo físico. Proveedores asignados entre los existentes; cajas agrupadas para almacén, sin afirmar empaque de fábrica. Fotos referenciales cuando se indica. El PVP incluye IVA; no sumarlo otra vez. Las jabas se contabilizan por botella. El precio de cerveza retornable supone intercambio de envase.</div>
<input id="filter" type="search" placeholder="Buscar producto, categoría, proveedor o código" aria-label="Buscar en catálogo"><div class="wrap"><table><thead><tr><th>Foto</th><th>Producto</th><th>PVP</th><th>IVA</th><th>Costo est.</th><th>Stock / presentación</th><th>Código</th><th>Proveedor</th></tr></thead><tbody>'''+''.join(table)+'''</tbody></table></div></main><script>document.getElementById('filter').addEventListener('input',e=>{const q=e.target.value.toLocaleLowerCase('es');document.querySelectorAll('tbody tr').forEach(r=>r.hidden=!r.textContent.toLocaleLowerCase('es').includes(q));});</script></html>'''
(ROOT/'catalogo.html').write_text(page,encoding='utf-8')
print('Informe, catálogo con fotos y 16 etiquetas generados.')
