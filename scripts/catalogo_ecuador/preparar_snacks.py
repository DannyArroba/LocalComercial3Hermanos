"""Prepara lote de snacks, arroz, pollo y tortas, sin escribir en MySQL."""
import concurrent.futures
from decimal import Decimal,ROUND_CEILING
import json
import os
from pathlib import Path
import urllib.request
from investigar import ROOT, product
from preparar_ampliacion import from_spec, finish, special, money, UPLOADS
from snacks_specs import SPECS,GROUPS

baseline=json.load(urllib.request.urlopen('http://127.0.0.1:5173/api/backend/api/products.php',timeout=20))
known={c:p for p in baseline for c in p.get('scan_codes',[p['barcode']])}
rows=json.loads((ROOT/'snacks-referencias.json').read_text(encoding='utf-8'))
skipped=[]; fresh=[]
for row in rows:
    if row['barcode'] in known:
        skipped.append(dict(name=row['name'],existing_id=known[row['barcode']]['id'],existing_name=known[row['barcode']]['name'],barcode=row['barcode']))
    else: fresh.append(row)
rows=fresh; bykey={r['key']:r for r in rows}; grouped=[]; removed=set()

def proposed(row):
    target=(Decimal(row['reference_price'])/Decimal('.05')).to_integral_value(rounding=ROUND_CEILING)*Decimal('.05')
    base=money(target/(Decimal('1.15') if row['applies_iva'] else 1))
    return str(money(base*(Decimal('1.15') if row['applies_iva'] else 1)))

for keys,name in GROUPS:
    buckets={}
    for key in keys:
        if key in bykey: buckets.setdefault(proposed(bykey[key]),[]).append(bykey[key])
    for price,members in buckets.items():
        if len(members)<2:continue
        primary=members[0]
        variants=[{k:r[k] for k in ['name','barcode','source_url','reference_price']} for r in members]
        primary['name']=name
        primary['variants']=variants
        removed.update(r['key'] for r in members[1:])
        grouped.append(dict(name=name,pvp=price,variants=variants))
rows=[r for r in rows if r['key'] not in removed]
# Otra torta popular, no confundir con litros de helado en tarrina.
capricho=product('/producto/torta-helada-capricho-vainilla-y-chocolate-15-porciones-pinguino-1500-ml/')
rows.append(special('capricho','Torta Pingüino Capricho 1,5 L','10.05',1,3,3,'Tortas heladas',capricho['source_url'],capricho['image'][0],code=capricho['gtin'],reference_price=capricho['offers']['price'],note='La fuente consultada la marca sin stock; verificar disponibilidad con el proveedor.'))
# La fuente vende por kg. Para el modelo actual de stock entero se propone
# precio fijo por ave seleccionada de ~2 kg: NO registrar su PLU como GTIN.
chicken=product('/producto/pollo-entero-grande-mr-pollo-al-peso-1384')
rows.append(special('polloentero','Pollo entero Mr. Pollo bolsa ~2 kg','7.00',0,2,8,'Carnes',chicken['source_url'],chicken['image'][0],reference_price=chicken['offers']['price'],note='Fuente: $3,47 por kg. PVP por ave propuesta de aproximadamente 2 kg; no es una cotización por ave ni lectura de peso variable.'))
rows[-1]['reference_unit']='kg'; rows[-1]['sale_unit']='ave de aproximadamente 2 kg'
# Referencia adicional de quintal económico. Descargar con el almacén de
# certificados de Windows; conservar bytes y fuente. La foto es de la marca,
# no certifica el tamaño del saco mostrado.
imperial_photo='https://sietedias.elipsys.ec/web/image/product.template/27967/image?unique=814d560'
local=Path(os.environ['TEMP'])/'nexar-imperial-qq.jpg'
raw=local.read_bytes(); ext='.png' if raw.startswith(b'\x89PNG') else '.jpg' if raw.startswith(b'\xff\xd8') else None
assert ext,'Imagen Imperial inválida'
(UPLOADS/('ec-variedad-snacks-imperialqq'+ext)).write_bytes(raw)
rows.append(special('imperialqq','Arroz Imperial quintal 100 lb','40.00',0,1,3,'Arroces',
    'https://sietedias.elipsys.ec/shop/product/02571-arroz-imperial-quintal-27967',imperial_photo,
    reference_price='37.86',note='PVP de tienda propuesto $40; fuente publica oferta $37,86 y normal $46. Foto referencial de marca, presentación menor; confirmar saco de 100 lb al recibir.'))
rows[-1]['image_note']='Foto referencial de marca; el empaque de la fotografía no es el quintal'

for row in rows: row['key']='snacks-'+row['key']
with concurrent.futures.ThreadPoolExecutor(max_workers=5) as pool:
    final=list(pool.map(lambda pair:finish(*pair),[(r,8000+i) for i,r in enumerate(rows,1)]))
for r in final:
    if r['category']=='Tortas heladas':
        r['description']=r['name']+'. Venta por torta completa. Conservar congelada a -18 °C o según fabricante; no recongelar.'
        r['pack']=f"{r['stock']} tortas completas; inventario inicial estimado"
    if r['key']=='snacks-polloentero':
        r['description']='Pollo entero crudo enfundado de marca Mr. Pollo. Pieza de aproximadamente 2 kg, precio fijo por ave seleccionada. Mantener refrigerado y cocinar completamente.'
        r['pack']='8 aves enfundadas de aproximadamente 2 kg; stock por ave, no por kg'
        r['image_note']='Foto comercial de pollo entero; peso de cada ave variable'
    if r['key']=='snacks-imperialqq':
        r['description']='Arroz blanco Imperial, saco de un quintal de 100 lb (aprox. 45,36 kg). Venta por saco completo. Imagen referencial de la marca; confirmar presentación del saco recibido.'
    if r['category']=='Arroces':
        r['pack']=f"{r['stock']} sacos o fundas completos de la presentación indicada; stock independiente del granel"
        if '100 lb' in r['name'] and r['key']!='snacks-imperialqq':
            r['description']+=' Un quintal equivale a 100 lb (aproximadamente 45,36 kg).'
    if r['key']=='snacks-festival': r['description']+=' Rinde 25 porciones según presentación del fabricante.'
    if r['key']=='snacks-capricho': r['description']+=' Rinde 15 porciones según presentación del fabricante.'
    if 'canguil99' in r['key'] or 'actii87' in r['key']: r['description']+=' Producto para preparar; seguir instrucciones de microondas del empaque.'
    if r['key']=='snacks-popcorn65': r['description']+=' Palomitas acarameladas listas para consumir.'
    assert len(r['name'])<=45,r['name']
codes={}
for r in final:
    for code in r['scan_codes']:
        assert code not in known,'Código existente: '+code
        assert code not in codes,'Código repetido en el lote: '+code
        codes[code]=r['key']
plan=dict(batch='snacks_arroces_20261118',products=final,groups=grouped,skipped=skipped,baseline=baseline,
    category_updates=[dict(id=int(p['id']),category='Arroces') for p in baseline if int(p['id']) in (6,14)])
(ROOT/'snacks-plan.json').write_text(json.dumps(plan,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
print(json.dumps(dict(new=len(final),groups=len(grouped),skipped=skipped,internal=sum(r['barcode_type']=='interno' for r in final)),ensure_ascii=True))
