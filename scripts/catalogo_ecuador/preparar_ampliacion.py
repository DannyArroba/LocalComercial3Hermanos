"""Prepara ampliación y fotos; todavía no escribe en la base de datos."""
import concurrent.futures
from decimal import Decimal,ROUND_HALF_UP,ROUND_CEILING
import json
import re
import urllib.request
from investigar import ROOT
from preparar import ref_for, barcode
from ampliacion_specs import SPECS,GROUPS

UPLOADS=ROOT.parents[1]/'frontend'/'uploads'
def money(n): return Decimal(str(n)).quantize(Decimal('.01'),rounding=ROUND_HALF_UP)
def valid(code):
    return code.isdigit() and len(code) in (8,12,13,14) and (10-sum(int(c)*(3 if i%2==0 else 1) for i,c in enumerate(code[-2::-1]))%10)%10==int(code[-1])
def normalized(code):
    code=str(code or '')
    if valid(code): return code
    if code.isdigit() and 8<len(code)<12 and valid(code.zfill(12)): return code.zfill(12)
    return None
def photo(key,url):
    # Conservar exactamente los bytes de la foto comercial consultada.
    stem='ec-variedad-'+key
    previous=list(UPLOADS.glob(stem+'.*'))
    if previous: return previous[0].name
    data=urllib.request.urlopen(urllib.request.Request(url,headers={'User-Agent':'Mozilla/5.0'}),timeout=35).read()
    ext='.png' if data.startswith(b'\x89PNG\r\n\x1a\n') else '.jpg' if data.startswith(b'\xff\xd8') else '.webp' if data[:4]==b'RIFF' and data[8:12]==b'WEBP' else None
    if not ext: raise ValueError('Foto no válida: '+url)
    (UPLOADS/(stem+ext)).write_bytes(data)
    return stem+ext

def from_spec(spec):
    key,name,query,index,iva,supplier,stock,category=spec
    ref=ref_for((key,name,None,query,index))
    return dict(key=key,name=name,applies_iva=iva,supplier_id=supplier,stock=stock,category=category,
        barcode=normalized(ref.get('gtin')),photo_source=ref['image'][0],source_url=ref['source_url'],
        reference_product=ref['name'],reference_price=ref['offers']['price'],pvp_target=None,variants=[],
        brand=ref.get('brand',{}).get('name',''),image_note='Foto comercial de la presentación consultada')

def special(key,name,price,iva,supplier,stock,category,source,image,code=None,reference_price=None,note=''):
    return dict(key=key,name=name,pvp_target=str(price),applies_iva=iva,supplier_id=supplier,stock=stock,category=category,
        barcode=normalized(code),photo_source=image,source_url=source,reference_product=name,reference_price=str(reference_price or price),
        price_note=note,variants=[],brand='',image_note='Foto comercial de la presentación consultada')

def finish(row,seq):
    pvp=Decimal(row['pvp_target']) if row.get('pvp_target') else (Decimal(row['reference_price'])/Decimal('.05')).to_integral_value(rounding=ROUND_CEILING)*Decimal('.05')
    base=money(pvp/(Decimal('1.15') if row['applies_iva'] else 1))
    row.update(price=str(base),pvp_propuesto=str(money(pvp)),pvp=str(money(base*(Decimal('1.15') if row['applies_iva'] else 1))),
        cost_price=str(money(base*Decimal('.78'))),existing_id=None)
    if not row['barcode']:
        row.update(barcode=barcode(1000+seq),barcode_type='interno')
    else: row['barcode_type']='comercial_verificado'
    row['scan_codes']=list(dict.fromkeys([row['barcode']]+[v['barcode'] for v in row['variants'] if v.get('barcode')]))
    row['image']=photo(row['key'],row['photo_source'])
    row['pack']=f"{row['stock']} unidades de venta de la presentación indicada; cantidad inicial propuesta"
    row['cost_status']='Costo estimado sin IVA recuperable; confirmar con factura'
    row['stock_status']='Stock inicial propuesto; confirmar con conteo físico'
    row['supplier_status']='Asignación administrativa a proveedor existente; no acredita contrato de distribución'
    row['description']=row['name']+'. Venta por envase o paquete completo.'
    if row['variants']:
        row['description']+=' Variedades: '+', '.join(v['name'] for v in row['variants'])+'. Stock conjunto entre variedades al mismo precio. Foto de una de las variedades.'
    if row['category']=='Helados': row['description']+=' Conservar congelado a -18 °C o según indicación del fabricante; no recongelar.'
    elif row['category'] in ('Leches','Yogures','Yogures con cereal','Quesos y embutidos'):
        row['description']+=' Conservar según el empaque; refrigerar los productos que requieran cadena de frío y después de abrir.'
    elif row['category']=='Limpieza del hogar': row['description']+=' Uso doméstico; mantener alejado de niños y alimentos. No mezclar productos de limpieza.'
    if row['key'].startswith('cocaret'):
        row['description']+=' Precio del líquido con intercambio de envase; depósito no incluido ni cobrado automáticamente.'
    if row['key']=='protexunidad': row['description']='Protex Fresh o avena, una barra de 110 g. Se fracciona de tripack: la fotografía muestra el paquete de origen. Etiquetar cada barra con el código interno.'
    if row['key']=='polito': row['pack']='1 caja de 75 paletas de 55 ml; venta por paleta'
    if row['key']=='caldocubo': row['description']='Caldo Maggi de gallina. Venta por un cubo de 10 g; la foto muestra el paquete de origen. Etiquetar cada unidad con el código interno.'
    return row

if __name__=='__main__':
    saved=json.loads((ROOT/'referencias'/'especiales.json').read_text(encoding='utf-8'))
    with concurrent.futures.ThreadPoolExecutor(max_workers=5) as pool:
        rows=list(pool.map(from_spec,SPECS))
    bykey={r['key']:r for r in rows}
    grouped=[]; removed=set()
    for keys,name in GROUPS:
        buckets={}
        for key in keys:
            row=bykey[key]
            buckets.setdefault(row['reference_price'],[]).append(row)
        for price,members in buckets.items():
            if len(members)<2: continue
            primary=members[0]
            primary['name']=name
            if primary['key'].startswith('amor166') and len(members)==2: primary['name']='Amor fresa/chocolate 166 g'
            if primary['key'].startswith('protex') and len(members)==2: primary['name']='Protex Fresh/avena 3 × 110 g'
            primary['variants']=[{k:m[k] for k in ['name','barcode','source_url','reference_price']} for m in members]
            # Recordar los nombres originales sin inventar códigos de fabricante.
            primary['variants'][0]['name']=SPECS[[s[0] for s in SPECS].index(primary['key'])][1]
            removed.update(m['key'] for m in members[1:])
            grouped.append(dict(name=primary['name'],variants=primary['variants']))
    rows=[r for r in rows if r['key'] not in removed]
    # Precios de tienda propuestos a petición del usuario; la fuente consultada queda por separado.
    bykey={r['key']:r for r in rows}
    for key,pvp in {'pony200':'.30','pony330':'.75','coca300':'.50','coca1000':'1.10','atunvc160':'1.75'}.items():
        bykey[key]['pvp_target']=pvp
    pony=saved['pony1000']
    rows.append(special('pony1000','Pony Malta 1 L','1.50',1,1,24,'Maltas',pony['url'],pony['metas']['og:image'],reference_price=pony['metas']['product:price:amount'],note='PVP de tienda propuesto por el usuario; referencia minorista inferior registrada por separado.'))
    milk=saved['https://www.tia.com.ec/leche-la-vaquita-tetrabrik-1-l-entera-330202000.html']
    rows.append(special('vaquita1000','Vaquita entera fortificada 1 L','1.45',1,2,24,'Leches',milk['url'],milk['metas']['og:image'],reference_price=milk['metas']['product:price:amount']))
    coca=saved['cocaoficial']
    images={a:u for a,u in coca['images'] if a}
    for key,name,pvp,label in [
        ('cocaret300','Coca-Cola retornable 300 ml','.30','#Sitengoenvase Coca-Cola Sabor Original 300 ML. Retornable'),
        ('cocaret1250','Coca-Cola retornable 1,25 L','1.00','#Sitengoenvase Coca-Cola Sabor Original 1250 ML. Retornable Gye'),
        ('cocaret2000','Coca-Cola retornable 2 L','1.50','#Sitengoenvase Coca-Cola Sabor Original 2000 ML. Retornable'),
        ('coca2000','Coca-Cola desechable 2 L','2.00','Coca-Cola Sabor Original 2000 ML.'),
    ]:
        rows.append(special(key,name,pvp,1,1,36 if key=='cocaret300' else 24,'Gaseosas',coca['url'],images[label]))
    rows.append(special('tonientera','Toni entera 1 L','1.65',0,2,24,'Leches',coca['url'],images['Leche Entera 1000 ML.']))
    rows.append(special('toniyog110','Yogur Toni surtido 110 g','.50',0,2,24,'Yogures',coca['url'],images['Yogur Frutilla 110 GR']))
    rows.append(special('polito','Pingüino Polito vainilla 55 ml','.25',1,3,75,'Helados',
        'https://www.pharmacys.com.ec/helado-pinguino-polito-vainilla-55ml-x-75/p','https://pharmacys.vtexassets.com/arquivos/ids/173798-800-800?aspect=true&height=800&v=639015160996970000&width=800',reference_price='.25',note='Precio unitario de referencia: caja de 75 por $18,75; no es el costo de compra real.'))
    rows.append(special('vasito','Pingüino Vasito vainilla 100 ml','.50',1,3,40,'Helados',
        'https://www.heladospinguino.com.ec/p/vasito-vainilla.html/07861047722576','https://assets.unileversolutions.com/v1/1148978.png',code='07861047722576',reference_price='.50',note='PVP propuesto de tienda: pendiente de cotización vigente del distribuidor.'))
    protex=bykey['protexfresh']
    rows.append(special('protexunidad','Protex Fresh/avena 110 g','.75',1,1,36,'Cuidado personal',protex['source_url'],protex['photo_source'],reference_price=str(money(Decimal(protex['reference_price'])/3)),note='Venta fraccionada de tripack; fotografía del paquete de origen.'))
    caldo=bykey['caldogallina']
    rows.append(special('caldocubo','Maggi gallina cubo 10 g','.20',1,1,60,'Sazonadores y sopas',caldo['source_url'],caldo['photo_source'],reference_price=str(money(Decimal(caldo['reference_price'])/12)),note='5 paquetes fraccionados de 12 cubos; precio de referencia unitario calculado del paquete.'))
    jet=saved['https://tiendaamiga.online/despensa-y-minimarket/']
    jetphoto=next(u for a,u in jet['images'] if a=='Jet Leche Milk Chocolate en Barra 12 g')
    rows.append(special('jet12','Chocolate Jet 12 g','.25',1,3,48,'Chocolates','https://tiendaamiga.online/product/jet-leche-milk-chocolate-en-barra-12-g/',jetphoto,reference_price='.25'))
    # Fuentes y agrupaciones permanecen auditables aunque tengan un único SKU.
    with concurrent.futures.ThreadPoolExecutor(max_workers=5) as pool:
        final=list(pool.map(lambda pair:finish(*pair),[(r,i) for i,r in enumerate(rows,1)]))
    baseline=json.load(urllib.request.urlopen('http://127.0.0.1:5173/api/backend/api/products.php',timeout=20))
    existing_codes={r['barcode']:r for r in baseline}
    conflicts=[]
    for row in final:
        if row['barcode'] in existing_codes:
            row['existing_id']=int(existing_codes[row['barcode']]['id'])
            conflicts.append(dict(key=row['key'],id=row['existing_id']))
    codes={}
    for row in final:
        for code in row['scan_codes']:
            if code in codes and codes[code]!=row['key']: raise ValueError('Código duplicado '+code)
            assert valid(code),code
            codes[code]=row['key']
    assert all(len(r['name'])<=45 for r in final)
    payload=dict(products=final,groups=grouped,baseline=baseline,remove_id=45,existing_matches=conflicts)
    (ROOT/'ampliacion.json').write_text(json.dumps(payload,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
    print(json.dumps(dict(products=len(final),groups=len(grouped),internal=sum(r['barcode_type']=='interno' for r in final),existing_matches=conflicts),ensure_ascii=True))
    for r in final: print(r['key'],r['name'],r['pvp'],flush=True)
