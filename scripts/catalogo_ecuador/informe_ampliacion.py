"""Catálogo de consulta actualizado e informe de la ampliación aplicada."""
from decimal import Decimal,ROUND_HALF_UP
import html
import json
from pathlib import Path
import sys
import urllib.request
from investigar import ROOT

audit=json.loads(Path(sys.argv[1]).read_text(encoding='utf-8'))
products=json.load(urllib.request.urlopen('http://127.0.0.1:5173/api/backend/api/products.php',timeout=20))
metadata_path=ROOT/'catalogo-actual.json'
if not metadata_path.exists(): metadata_path=ROOT/'catalogo.json'
old={r['barcode']:r for r in json.loads(metadata_path.read_text(encoding='utf-8'))}
added={r['barcode']:r for r in audit['new_products']}
def pvp(p): return str((Decimal(p['price'])*(Decimal('1.15') if int(p['applies_iva']) else 1)).quantize(Decimal('.01'),rounding=ROUND_HALF_UP))
def h(s): return html.escape(str(s or ''))
for p in products:
    meta=added.get(p['barcode'],old.get(p['barcode'],{}))
    for k in ['key','source_url','photo_source','reference_price','barcode_type','image_note','price_note','variants','pack','photo_attribution']:
        if k in meta: p[k]=meta[k]
    p['pvp']=pvp(p)
    p['action']='nuevo' if p['barcode'] in added else 'anterior'
products.sort(key=lambda r:(r['category'],r['name']))
(ROOT/'catalogo-actual.json').write_text(json.dumps(products,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
notes='''Precios finales en USD, con IVA incluido. Los precios publicados por comercios ecuatorianos son referencias minoristas, no costos mayoristas ni un precio obligatorio en todas las ciudades. Algunos PVP son propuestas de tienda ajustadas al pedido del usuario (Pony Malta 330 ml a $0,75 y 1 L a $1,50, por ejemplo). El Vasito Pingüino conserva una referencia de presentación del fabricante, pero su PVP de $0,50 es propuesto y requiere cotización actual. La precisión base de dos decimales de la app provoca diferencias de hasta un centavo respecto del PVP objetivo.

Los costos de compra y stocks nuevos son ESTIMACIONES; confirmar con facturas y conteo físico. Los proveedores se asignaron entre los tres registros existentes; esta asignación no certifica que distribuyan cada marca. Los stocks indican envases/paquetes de venta, no cajas. Polito: 75 paletas de 55 ml, según caja publicada de 75. En salchichas y mortadelas se informa el peso real publicado: no se inventó que todos los sobres traigan cinco unidades.

El plátano verde se eliminó tras comprobar que no tenía referencias en ventas, compras, devoluciones ni movimientos. Los otros 60 productos anteriores conservaron su ID y stock. Sus nombres se acortaron y se ajustaron categorías. Coca-Cola de 500 ml se ajustó a un PVP cercano a $0,80 (base $0,69, final $0,79).

Siete conjuntos de variantes con misma marca, tamaño y precio se agruparon en un único producto con STOCK COMPARTIDO. La API incluye todos sus códigos comerciales y el lector de caja reconoce cada uno. Así, una venta de cualquiera de esos códigos descuenta del mismo stock. La foto del grupo muestra una variante representativa. Las variantes de precio o presentación distintos siguen separadas. Protex por barra y por tripack también tienen existencias separadas; si se abre un tripack físico para vender barras debe ajustarse el inventario de ambos para no contarlo dos veces. Lo mismo aplica a Maggi por cubo y por paquete.

Los códigos internos necesitan etiqueta propia: no se presentaron como códigos comerciales de fabricante. Los códigos comerciales se contrastaron con la presentación publicada; revisar el código del empaque físico antes de usar el lector. Las retornables de Coca-Cola usan código interno porque no se verificó un GTIN de cada envase retornable en la fuente. Su PVP supone intercambio del envase; no se agregó un depósito automático. No se hizo una prueba con lector físico.

Se usó IVA 0% para leche nacional sin aditivos, yogur solo, queso, embutidos, pastas secas y enlatados nacionales de atún. Leches fortificadas/deslactosadas/saborizadas, sopas preparadas, salsas, cereales de desayuno, golosinas, helados y productos de limpieza/cuidado personal: 15%. Los yogures con cereal o galletas se registraron al 15%. La Vaquita entera de 1 L y 900 ml seleccionada es fortificada y lleva 15%. Confirmar formulación y factura al cambiar de empaque. No se modificó la tasa global de la app ni se recalcularon comprobantes anteriores.

Los atunes se registraron como Real, Van Camp’s e ILE; no se atribuyeron a Nestlé. La Vaquita, Vita, Toni, Pingüino, Protex y Fabuloso conservan su identidad comercial. No se afirmó que todas esas marcas pertenezcan al mismo fabricante. Las fotos comerciales se descargaron sin modificarlas; conservan sus titulares y fuentes. Las atribuciones originales de Commons siguen en el informe inicial.
'''
is_snacks=audit.get('batch','').startswith('snacks_')
report_name='SNACKS-Y-ARROCES.md' if is_snacks else 'AMPLIACION.md'
if is_snacks:
    notes='''Precios finales en USD, con IVA incluido. Se consultaron precios publicados por comercios ecuatorianos, principalmente Supermaxi; cada producto conserva su referencia. Se redondeó la propuesta a intervalos de $0,05 y se guardó la base sin IVA con dos decimales, por lo que el precio final puede variar $0,01. Los productos importados o grandes no se igualaron artificialmente al precio de una funda pequeña. Costos y stocks son estimados: confirmar con factura y conteo físico. Los proveedores son asignaciones entre registros existentes, no contratos de distribución verificados.

Ruffles de 38 g y Arroz Real de 5 kg ya existían: se omitieron del lote para no duplicar ni alterar su stock. Todos los productos anteriores conservaron su ID, nombre, precio y existencias. Sólo se movieron las dos entradas anteriores de arroz a la categoría Arroces. No se crearon facturas ficticias ni se modificaron ventas, compras o devoluciones.

Los sabores de la misma marca y tamaño con igual PVP propuesto se agruparon. Comparten stock y la caja reconoce sus códigos comerciales alternativos. Las fotos de surtidos muestran una variante representativa. Los tamaños diferentes conservan su propio código y stock. Arroz de 1 kg, 2 kg, 5 kg, 10 kg, 25 lb y quintal son unidades de venta distintas: abrir un saco para fraccionar requiere ajustar ambos stocks para no contarlo dos veces.

Un quintal de 100 lb equivale aproximadamente a 45,36 kg; no se confundió con 50 kg. Los sacos pesados tienen tres o cuatro unidades propuestas, en lugar de 30 sacos por marca. Arroz Imperial quintal: PVP propuesto $40,00 frente a una oferta publicada de $37,86 y normal de $46; confirmar presentación de 100 lb al recibir. Su foto es referencial de la marca y muestra un empaque menor. Los quintales Arnejos y Supermaxi conservan los precios publicados para sus presentaciones de 45,36 kg, que son más elevados.

Pollo entero Mr. Pollo: la referencia publicada es $3,47 por kg. El catálogo usa un precio FIJO PROPUESTO de $7,00 por ave seleccionada de aproximadamente 2 kg, con stock de ocho aves enfundadas. No es una tarifa de $7 por kg ni una cotización por ave publicada por el comercio. Confirmar peso y costo real al recibir. Se creó código interno porque el PLU de pollo vendido al peso no es un GTIN comercial de una bolsa de peso fijo. La app sigue contando aves enteras y no interpreta etiquetas de peso variable. Se conservó aparte la porción de pollo de 1 kg que ya existía.

Se añadieron tortas completas Festival (2,5 L, 25 porciones), Capricho (1,5 L, 15 porciones), Topsy Cariño y Topsy Imperial. El stock propuesto es tres tortas por presentación y deben mantenerse congeladas según el fabricante. Capricho tiene precio publicado, pero figuraba sin stock en la fuente consultada; confirmar disponibilidad con el proveedor.

IVA 0% para arroz, granos secos sin preparar, panela y pollo crudo sin adobar. Snacks elaborados, palomitas preparadas/para microondas saborizadas, mermeladas, conservas vegetales cocidas, dips y tortas heladas: 15%. Revisar composición y factura al cambiar de formulación. Las fotos comerciales se descargaron sin modificarlas; conservaron sus fuentes y titulares. Tener la imagen no implica licencia de redistribución.

Las etiquetas internas nuevas corresponden al pollo entero y al quintal Imperial. No se inventaron códigos oficiales de fabricante. Los códigos comerciales deben contrastarse con los envases recibidos; la comprobación automática de dígitos no sustituye una prueba con lector físico.
'''
md=f'# Ampliación aplicada — Tiendita Nexar\n\n**{audit["created"]} productos nuevos, {len(products)} productos totales.**\n\n'+notes+'\n## Variantes agrupadas\n\n'
for g in audit['groups']:
    md+='- **'+g['name']+'**: '+', '.join(v['name'] for v in g['variants'])+'.\n'
md+='\n## Catálogo actual\n\n| ID | Producto | PVP con IVA | IVA | Stock | Código principal | Fuente |\n|---|---|---:|---:|---:|---|---|\n'
for p in products:
    md+=f"| {p['id']} | {p['name']} | ${p['pvp']} | {15 if int(p['applies_iva']) else 0}% | {p['stock']} | {p['barcode']} | [Referencia]({p.get('source_url','')}) |\n"
md+='\n## Códigos alternativos de variantes\n\n'
for p in products:
    if len(p.get('scan_codes',[]))>1:
        md+='- '+p['name']+': '+', '.join(p['scan_codes'])+'.\n'
md+='\n## Referencias especiales y respaldo\n\n'
md+='- [Precios y retornables del catálogo En Tu Hogar by Coca-Cola](https://www.entuhogar.ec/pedir).\n'
md+='- [Pony Malta de 1 L en Tía](https://www.tia.com.ec/supermercado/aguas-y-bebidas/bebidas-gaseosas-y-maltas/bebida-pony-malta-1-l-242310000.html).\n'
md+='- [Polito de 55 ml, caja de 75, Pharmacys](https://www.pharmacys.com.ec/helado-pinguino-polito-vainilla-55ml-x-75/p).\n'
md+='- [Vasito vainilla, presentación publicada por Pingüino](https://www.heladospinguino.com.ec/p/vasito-vainilla.html/07861047722576).\n'
md+='- [SRI: circular de alimentos y leches](https://www.sri.gob.ec/o/sri-portlet-biblioteca-alfresco-internet/descargar?id=a3e4257d-ba2e-4ec7-89c7-635fa764b22a&nombre=NAC-DGECCGC26-00000005.pdf).\n'
md+='- [Marcas de Nestlé Ecuador](https://www.nestle.com.ec/es/marcas).\n'
md+='\nRespaldo SQL completo previo: `'+audit['backup']+'`. En la misma carpeta está `resultado-ampliacion.json`, con el catálogo anterior y posterior. Las tablas históricas se compararon antes y después: no cambiaron.\n'
(ROOT/report_name).write_text(md,encoding='utf-8')

table=[]
for p in products:
    aliases=p.get('scan_codes',[p['barcode']])
    badge='Interno: etiquetar' if p.get('barcode_type')=='interno' else f'{len(aliases)} código(s) comercial(es)'
    source=f'<a href="{h(p.get("source_url"))}">Fuente</a>' if p.get('source_url') else ''
    table.append(f'<tr><td><img loading="lazy" src="../../frontend/uploads/{h(p["image"])}" alt="{h(p["name"])}" title="{h(p.get("image_note"))}"></td><td><b>{h(p["name"])}</b><small>{h(p["category"])} · {h(p["action"])} · ID {p["id"]}</small></td><td class="money">${p["pvp"]}</td><td>{15 if int(p["applies_iva"]) else 0}%</td><td>${p["cost_price"]}</td><td>{p["stock"]}</td><td><code>{h(p["barcode"])}</code><small>{h(badge)}</small></td><td>{h(p["supplier_name"])}<small>{source}</small></td></tr>')
page='''<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Catálogo Tiendita Nexar</title><style>
*{box-sizing:border-box}body{font-family:system-ui,sans-serif;background:#f4f7f8;color:#1e293b;margin:0;padding:28px}main{max-width:1400px;margin:auto}header{padding:28px;border-radius:18px;background:#064e3b;color:white}h1{margin:0 0 10px}header a{color:#d1fae5}.stats{display:flex;gap:16px;margin:18px 0}.stat{padding:18px;background:white;border:1px solid #dce6e4;border-radius:12px;flex:1}.stat b{display:block;font-size:28px;color:#047857}.notes{padding:16px;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;line-height:1.5}.controls{display:flex;gap:10px;margin:18px 0}input,select{padding:12px;border:1px solid #cbd5e1;border-radius:8px;background:white}input{flex:1}.wrap{overflow:auto}table{width:100%;border-collapse:collapse;background:white;font-size:13px}th{text-align:left;background:#e2e8f0;padding:12px}td{padding:12px;border-bottom:1px solid #e2e8f0}img{width:66px;height:70px;object-fit:contain}small{display:block;color:#64748b;font-size:11px;margin-top:5px}.money{font-size:16px;font-weight:700;white-space:nowrap}code{font-size:11px}a{color:#047857}@media(max-width:700px){body{padding:10px}.stats{flex-wrap:wrap}.controls{flex-direction:column}td{min-width:85px}}</style><main>
<header><h1>Tiendita Nexar · Catálogo ampliado</h1><div>18 de noviembre de 2026 · precios finales con IVA · USD</div><p><a href="AMPLIACION.md">Informe y fuentes</a> · <a href="etiquetas-internas.html">Imprimir etiquetas internas</a></p></header>
<div class="stats"><div class="stat"><b>203</b>productos</div><div class="stat"><b>143</b>nuevos</div><div class="stat"><b>7</b>grupos de variantes</div><div class="stat"><b>0</b>plátanos verdes</div></div>
<div class="notes"><b>PVP con IVA incluido.</b> Costos y stocks estimados: confirmar con factura y conteo físico. Los surtidos comparten stock y aceptan sus códigos comerciales. Las retornables requieren intercambio de envase. Algunas fotos muestran un sabor representativo o el paquete del que se fracciona. Revisa el informe para precios propuestos y fuentes.</div>
<div class="controls"><input id="q" type="search" placeholder="Buscar marca, producto o código" aria-label="Buscar"><select id="category" aria-label="Categoría"><option value="">Todas las categorías</option>'''+''.join(f'<option>{h(c)}</option>' for c in sorted({p['category'] for p in products}))+'''</select><select id="scope" aria-label="Alcance"><option value="">Todos</option><option value="nuevo">Sólo nuevos</option><option value="anterior">Anteriores</option></select></div>
<p id="count">203 productos</p><div class="wrap"><table><thead><tr><th>Foto</th><th>Producto</th><th>PVP</th><th>IVA</th><th>Costo est.</th><th>Stock</th><th>Código</th><th>Proveedor</th></tr></thead><tbody>'''+''.join(table)+'''</tbody></table></div></main><script>
const data='''+json.dumps([dict(category=p['category'],action=p['action'],codes=p.get('scan_codes',[])) for p in products],ensure_ascii=True)+''';const rows=[...document.querySelectorAll('tbody tr')];function filter(){let n=0;rows.forEach((r,i)=>{const q=document.getElementById('q').value.toLocaleLowerCase('es'),c=document.getElementById('category').value,s=document.getElementById('scope').value;r.hidden=!((r.textContent+' '+data[i].codes.join(' ')).toLocaleLowerCase('es').includes(q)&&(!c||data[i].category===c)&&(!s||data[i].action===s));if(!r.hidden)n++;});document.getElementById('count').textContent=n+' productos';}['q','category','scope'].forEach(id=>document.getElementById(id).addEventListener('input',filter));</script></html>'''
page=page.replace('<b>203</b>',f'<b>{len(products)}</b>').replace('<b>143</b>',f'<b>{audit["created"]}</b>').replace('<b>7</b>grupos de variantes',f'<b>{len(audit["groups"])}</b>grupos nuevos').replace('<p id="count">203 productos</p>',f'<p id="count">{len(products)} productos</p>').replace('href="AMPLIACION.md"',f'href="{report_name}"')
if is_snacks:
    page=page.replace('<b>0</b>plátanos verdes',f'<b>{len(audit["skipped"])}</b>duplicados evitados')
(ROOT/'catalogo.html').write_text(page,encoding='utf-8')

def ean(code):
    assert len(code)==13
    l=['0001101','0011001','0010011','0111101','0100011','0110001','0101111','0111011','0110111','0001011']
    g=['0100111','0110011','0011011','0100001','0011101','0111001','0000101','0010001','0001001','0010111']
    parity=['LLLLLL','LLGLGG','LLGGLG','LLGGGL','LGLLGG','LGGLLG','LGGGLL','LGLGLG','LGLGGL','LGGLGL']
    bits='101'+''.join((l if p=='L' else g)[int(c)] for p,c in zip(parity[int(code[0])],code[1:7]))+'01010'+''.join(''.join('1' if b=='0' else '0' for b in l[int(c)]) for c in code[7:])+'101'
    assert len(bits)==95
    bars=''.join(f'<rect x="{11+i}" y="1" width="1" height="50"/>' for i,b in enumerate(bits) if b=='1')
    return f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 117 66"><rect width="117" height="66" fill="white"/><g fill="black" shape-rendering="crispEdges">{bars}</g><text x="58.5" y="62" text-anchor="middle" font-family="monospace" font-size="8">{code}</text></svg>'
internal=[p for p in products if p.get('barcode_type')=='interno']
labels=''.join(f'<article><b>{h(p["name"])}</b><strong>${p["pvp"]}</strong>{ean(p["barcode"])}<small>Código interno · precio final con IVA</small></article>' for p in internal)
labels='''<!doctype html><html lang="es"><meta charset="utf-8"><title>Etiquetas internas Nexar</title><style>@page{size:A4;margin:10mm}*{box-sizing:border-box}body{font-family:Arial,sans-serif;margin:10mm;color:#111}header{max-width:180mm;margin-bottom:8mm}h1{font-size:20px}p{font-size:13px}main{display:grid;grid-template-columns:repeat(3,58mm);gap:3mm}article{width:58mm;height:48mm;border:1px dashed #aaa;padding:3mm;break-inside:avoid;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;background:white}b{font-size:10px;height:10mm}strong{font-size:16px}svg{width:46mm;height:26mm;flex-shrink:0}small{font-size:7px}@media print{body{margin:0}header{display:none}}</style><header><h1>Etiquetas internas · '''+str(len(internal))+''' productos</h1><p>Imprimir A4 al 100%. Probar el lector antes de etiquetar el lote. No corresponden a códigos oficiales de fabricante. Retornables: precio con intercambio de envase. Los paquetes fraccionados y sus unidades se contabilizan por separado.</p><button onclick="window.print()">Imprimir</button></header><main>'''+labels+'</main></html>'
(ROOT/'etiquetas-internas.html').write_text(labels,encoding='utf-8')
print(json.dumps(dict(total=len(products),new=audit['created'],groups=len(audit['groups']),internal_labels=len(internal)),ensure_ascii=True))
