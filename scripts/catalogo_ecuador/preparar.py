"""Prepara un catálogo verificable e imágenes locales. No escribe en MySQL."""
import concurrent.futures
from decimal import Decimal, ROUND_HALF_UP, ROUND_CEILING
import hashlib
import json
from pathlib import Path
import urllib.request
from investigar import CACHE, ROOT, product

APP = ROOT.parents[1]
UPLOADS = APP / 'frontend' / 'uploads'

# clave, nombre comercial, ID conservado, búsqueda, índice de resultado,
# IVA, proveedor existente, stock vendible, agrupación de almacén, categoría, PVP manual opcional.
SPECS = [
('arroz-libra','Arroz blanco — 1 libra (454 g)',6,'arroz granel',3,0,1,50,'50 fundas de 1 libra; fraccionamiento local','Granos y cereales',0.65),
('azucar-libra','Azúcar blanca — 1 libra (454 g)',None,'azucar blanca granel',0,0,1,50,'50 fundas de 1 libra; fraccionamiento local','Granos y cereales',0.60),
('aceite','Aceite vegetal La Favorita — 1 L',None,'aceite vegetal 1000',2,0,1,24,'2 grupos de almacén de 12 botellas','Aceites y grasas',None),
('leche','Leche entera UHT Nutri — 1 L',11,'leche nutrileche',9,0,2,36,'3 grupos de almacén de 12 envases','Lácteos y huevos',None),
('huevos','Huevos medianos — cubeta de 30',None,'huevos 30',5,0,2,8,'8 cubetas; 240 huevos en total','Lácteos y huevos',4.50),
('fideos','Fideo spaghetti Paca — 400 g',None,'fideos 400',2,0,1,30,'3 grupos de almacén de 10 paquetes','Pastas y harinas',None),
('atun','Atún en lomos en aceite ILE — 170 g',None,'atun 170',2,0,1,48,'2 grupos de almacén de 24 latas','Conservas',None),
('harina','Harina de trigo sin polvo de hornear La Original — 500 g',None,'harina trigo original 500',1,0,1,24,'2 grupos de almacén de 12 fundas','Pastas y harinas',None),
('sal','Sal refinada Cris-Sal — 1 kg',None,'sal refinada',0,0,1,30,'3 grupos de almacén de 10 fundas','Condimentos y salsas',None),
('avena','Avena en hojuelas La Pradera — 500 g',None,'avena 500',0,0,1,24,'2 grupos de almacén de 12 fundas','Granos y cereales',None),
('lenteja','Lenteja seca — 1 libra (454 g)',None,'lenteja libra',3,0,1,30,'30 fundas de 1 libra; fraccionamiento local','Granos y cereales',0.90),
('frejol','Fréjol rojo seco — 1 libra (454 g)',None,'frejol 500',2,0,1,30,'30 fundas de 1 libra; fraccionamiento local','Granos y cereales',1.25),
('cafe-soluble','Café soluble Alto Cayetano — 100 g',None,'cafe soluble 100',8,1,1,24,'2 grupos de almacén de 12 frascos','Café e infusiones',None),
('pan','Pan blanco de molde Moderna — paquete',16,'pan blanco moderna',3,0,3,12,'12 paquetes; reposición frecuente','Panadería',None),
('mayonesa','Mayonesa Los Andes — 200 g',19,'mayonesa 200',1,1,1,24,'2 grupos de almacén de 12 doypacks','Condimentos y salsas',None),
('salsa','Salsa de tomate Los Andes — 200 g',None,'salsa tomate 200',1,1,1,24,'2 grupos de almacén de 12 doypacks','Condimentos y salsas',None),
('galletas','Galletas María vainilla — 145 g',None,'galletas vainilla',2,1,3,30,'3 grupos de almacén de 10 paquetes','Snacks y golosinas',None),
('papas-fritas','Papas Ruffles picantes — 38 g',None,'papas ruffles 28',2,1,3,36,'3 grupos de almacén de 12 fundas','Snacks y golosinas',None),
('chifles','Chifles Tortolines clásicos — 45 g',None,'chifles 45',0,1,3,36,'3 grupos de almacén de 12 fundas','Snacks y golosinas',None),
('chocolate','Chocolate blanco con arroz crocante — barra de 20 g',7,'chocolate 20',0,1,3,36,'3 grupos de almacén de 12 barras','Snacks y golosinas',None),
('caramelos','Caramelos surtidos — funda de 50 g',None,'caramelos',1,1,3,30,'30 fundas fraccionadas de 50 g','Snacks y golosinas',0.50),
('cola500','Coca-Cola original — 500 ml',None,'coca cola 500',3,1,1,48,'4 grupos de almacén de 12 botellas','Gaseosas y bebidas',None),
('cola3','Coca-Cola original — 3,05 L',None,'coca cola 3050',1,1,1,24,'4 grupos de almacén de 6 botellas','Gaseosas y bebidas',2.75),
('agua500','Agua sin gas — 500 ml',9,'agua 500',4,1,1,48,'4 grupos de almacén de 12 botellas','Aguas',0.50),
('agua1500','Agua con gas Schweppes — 1,5 L',None,'agua dasani 1500',5,1,1,24,'4 grupos de almacén de 6 botellas','Aguas',1.00),
('jugo','Néctar de naranja Del Valle — 1 L',10,'jugo del valle 1000',1,1,1,24,'2 grupos de almacén de 12 envases','Gaseosas y bebidas',None),
('energia','Energizante Red Bull original — 250 ml',None,'energizante 250 ml',2,1,1,24,'4 grupos de almacén de 6 latas','Gaseosas y bebidas',None),
('yogurt-litro','Yogur natural bebible El Pino — 1 L',None,'yogurt 1000 ml',2,0,2,12,'12 botellas refrigeradas','Lácteos y huevos',None),
('queso','Queso fresco González — 500 g',13,'queso fresco 500',4,0,2,12,'12 paquetes refrigerados','Lácteos y huevos',None),
('mantequilla','Mantequilla sin sal Nutri — 250 g',None,'mantequilla nutri 250',0,1,2,24,'2 grupos de almacén de 12 unidades refrigeradas','Lácteos y huevos',None),
('pollo','Pollo fresco sin adobar — porción de 1 kg',5,'pollo entero',0,0,2,12,'12 porciones exactas de 1 kg; mantener cadena de frío','Carnes',3.50),
('papa','Papa chola — funda de 1 kg',None,'papa chola granel',0,0,2,25,'25 fundas de 1 kg','Frutas y verduras',1.00),
('cebolla','Cebolla colorada — funda de 1 kg',None,'cebolla colorada granel',1,0,2,20,'20 fundas de 1 kg','Frutas y verduras',1.25),
('tomate','Tomate riñón — funda de 1 kg',None,'tomate rinon granel',1,0,2,20,'20 fundas de 1 kg','Frutas y verduras',1.20),
('verde','Plátano verde — unidad',None,'platano verde',0,0,2,40,'40 unidades seleccionadas; venta por unidad','Frutas y verduras',0.25),
('detergente','Detergente Surf multiusos — 1 kg',None,'detergente 1 kg',4,1,1,24,'2 grupos de almacén de 12 fundas','Limpieza del hogar',None),
('cloro','Cloro Livy — 1 L',None,'cloro 1 l',9,1,1,24,'2 grupos de almacén de 12 botellas','Limpieza del hogar',None),
('jabon-lavar','Jabón para ropa El Macho limón — 480 g',None,'jabon lavar',4,1,1,24,'2 grupos de almacén de 12 barras','Limpieza del hogar',None),
('jabon-tocador','Jabón de tocador Jolly manzanilla — barra de 100 g',None,'jabon tocador',4,1,1,36,'12 tripacks abiertos; venta por barra de 100 g','Cuidado personal',0.65),
('papel','Papel higiénico Elite doble hoja — 4 rollos de 38 m',None,'papel higienico 4',2,1,1,24,'6 grupos de almacén de 4 paquetes; 96 rollos en total','Cuidado personal',None),
('pasta','Pasta dental Colgate menta pura — 75 ml',None,'pasta dental 75',3,1,1,24,'2 grupos de almacén de 12 tubos','Cuidado personal',None),
('shampoo','Shampoo Gama jojoba y keratina — 400 ml',None,'shampoo gama 400',0,1,1,24,'2 grupos de almacén de 12 botellas','Cuidado personal',None),
('desodorante','Desodorante Dr. Dry roll-on antimanchas — 50 ml',None,'desodorante roll',8,1,1,24,'2 grupos de almacén de 12 unidades','Cuidado personal',None),
('toallas','Toallas sanitarias Ladysoft normal con alas — 10 unidades',None,'toallas sanitarias',0,0,1,24,'2 grupos de almacén de 12 paquetes','Cuidado personal',None),
('panales','Pañales Huggies Active Sec talla G — 20 unidades',None,'panal 20',3,1,1,12,'12 paquetes; 240 pañales en total','Bebés',None),
# Presentaciones que ya existían y que NO son duplicados de las anteriores.
('cola2500','Coca-Cola original — 2,5 L',8,None,None,1,1,24,'4 grupos de almacén de 6 botellas','Gaseosas y bebidas',2.50),
('yogurt-kilo','Yogur bebible de fresa Miraflores — 1 kg',12,'yogurt 1 l',2,0,2,12,'12 doypacks refrigerados','Lácteos y huevos',None),
('arroz5','Arroz blanco Real — 5 kg',14,'arroz 5000',6,0,1,12,'12 fundas de 5 kg','Granos y cereales',None),
('girasol','Aceite de girasol Girasoli — 1 L',15,'aceite girasol 1',3,0,1,24,'2 grupos de almacén de 12 botellas','Aceites y grasas',None),
('cafe-molido','Café tostado y molido El Artesanal — 250 g',17,'cafe artesanal 250',7,1,1,24,'2 grupos de almacén de 12 fundas','Café e infusiones',None),
('gomitas','Gomitas surtidas — funda de 50 g',18,'gomitas 30',0,1,3,30,'30 fundas fraccionadas de 50 g','Snacks y golosinas',0.50),
# Complementos habituales de una tienda de barrio.
('margarina','Margarina Imperial — 250 g',None,'margarina 250',1,0,2,24,'2 grupos de almacén de 12 tarrinas','Aceites y grasas',None),
('sardina','Sardina Real en aceite de girasol — 155 g',None,'sardina 155',0,0,1,24,'2 grupos de almacén de 12 latas','Conservas',None),
('maicena','Maicena Iris — 200 g',None,'maicena 200',0,0,1,24,'2 grupos de almacén de 12 cajas','Pastas y harinas',None),
('servilletas','Servilletas Elite — 100 unidades',None,'servilletas 100',0,1,1,24,'2 grupos de almacén de 12 paquetes','Limpieza del hogar',None),
('lavavajilla','Lavavajilla Lavatodo limón — 450 g',None,'lavavajilla 250',9,1,1,24,'2 grupos de almacén de 12 tarrinas','Limpieza del hogar',None),
('suavizante','Suavizante Livy floral — 1 L',None,'suavizante 1',3,1,1,24,'2 grupos de almacén de 12 botellas','Limpieza del hogar',None),
('volt','Energizante Volt original — 400 ml',None,'bebida volt',1,1,1,24,'2 grupos de almacén de 12 botellas','Gaseosas y bebidas',None),
('pilsener','Cerveza Pilsener retornable — 1 L',None,None,None,1,1,36,'3 jabas de 12 botellas; venta por botella con intercambio de envase','Cervezas',2.50),
('light','Cerveza Pilsener Light retornable — 1 L',None,None,None,1,1,36,'3 jabas de 12 botellas; venta por botella con intercambio de envase','Cervezas',2.40),
('negra','Cerveza negra Ballesta — 300 ml',None,'cerveza negra',2,1,1,24,'4 grupos de almacén de 6 botellas','Cervezas',None),
]

INTERNAL = {'arroz-libra','azucar-libra','lenteja','frejol','huevos','caramelos','gomitas','pollo','papa','cebolla','tomate','verde','cola2500','pilsener','light','jabon-tocador'}
MAG = 'https://sipa.agricultura.gob.ec/boletines/nacionales/mayoristas/2026/precios_mayoristas_septiembre_2026.pdf'
BEER = 'https://tienda.ecuadorcupon.com/16-alcoholicas'

def ref_for(spec):
    query, idx = spec[3:5]
    if query is None:
        return None
    slug = __import__('re').sub(r'[^a-z0-9]+','-',query.lower())
    links = json.loads((CACHE/(slug+'.json')).read_text(encoding='utf-8'))
    return product(links[idx])

def money(n):
    return Decimal(str(n)).quantize(Decimal('.01'), rounding=ROUND_HALF_UP)

def barcode(sequence):
    # Prefijo 20: uso interno/restringido, NO GTIN asignado por GS1 a una marca.
    digits = '200001' + str(sequence).zfill(6)
    check = (10 - sum(int(c)*(1 if i%2==0 else 3) for i,c in enumerate(digits))%10)%10
    return digits+str(check)

def prepare(pair):
    seq, spec = pair
    key,name,existing,query,index,iva,supplier,stock,pack,category,pvp = spec
    ref = ref_for(spec)
    reference_price = Decimal(str(ref['offers']['price'])) if ref and ref.get('offers',{}).get('price') else None
    if pvp is None:
        if not reference_price or reference_price <= 0:
            raise ValueError('Falta precio de referencia: '+key)
        pvp = (reference_price/Decimal('.05')).to_integral_value(rounding=ROUND_CEILING)*Decimal('.05')
    pvp = money(pvp)
    rate = Decimal('1.15') if iva else Decimal('1')
    base = (pvp/rate).quantize(Decimal('.000001'),rounding=ROUND_HALF_UP)
    # Costos de reposición ESTIMADOS, sin IVA recuperable; no son cotizaciones.
    margin = Decimal('.18') if category in ['Granos y cereales','Lácteos y huevos','Conservas','Aceites y grasas','Carnes','Cervezas'] else Decimal('.25')
    cost = money(base*(1-margin))
    internal = key in INTERNAL
    code = barcode(seq) if internal else str(ref['gtin'])
    photo = None
    photo_source = None
    # No mostrar un empaque de otra presentación como si fuera el producto local.
    if ref and not internal:
        photo_source = ref['image'][0]
        filename = 'ec-20261118-'+key+'-'+str(ref['gtin'])+'.png'
        path = UPLOADS/filename
        if not path.exists():
            data = urllib.request.urlopen(photo_source,timeout=35).read()
            if not data.startswith(b'\x89PNG\r\n\x1a\n'):
                raise ValueError('Imagen no PNG: '+key)
            path.write_bytes(data)
        photo=filename
    desc = f'{name}. Venta por unidad de la presentación indicada. {pack}. '
    desc += 'Stock inicial estimado; confirmar con conteo físico. Costo de compra estimado; sustituir por factura real. '
    if internal:
        desc += 'Código interno EAN-13; requiere etiqueta propia, no corresponde al código del fabricante. '
    if category in ['Lácteos y huevos','Carnes'] and key!='huevos':
        desc += 'Mantener refrigerado según el fabricante; revisar caducidad al recibir. '
    if category=='Cervezas':
        desc += 'Venta exclusiva a mayores de 18 años. '
        if key in ['pilsener','light']:
            desc += 'Precio del líquido con devolución de envase; el depósito no está incluido ni automatizado. '
    if key=='pan':
        desc += 'IVA 0% para pan simple conforme INEN 2945; verificar composición del empaque recibido. '
    if key=='panales':
        desc += 'IVA 15%: producto convencional, no clasificado como pañal popular. '
    return dict(key=key,name=name,existing_id=existing,barcode=code,barcode_type='interno' if internal else 'comercial_verificado',
        price=str(base),pvp=str(pvp),applies_iva=iva,cost_price=str(cost),stock=stock,category=category,supplier_id=supplier,
        description=desc,image=photo,pack=pack,reference_price=str(reference_price) if reference_price else None,
        source_url=ref['source_url'] if ref else (BEER if category=='Cervezas' else 'https://www.tia.com.ec/supermercado/aguas-y-bebidas/bebidas-gaseosas-y-maltas'),
        photo_source=photo_source,price_basis='PVP propuesto con referencia de mercado' if spec[-1] is not None else 'Precio minorista consultado redondeado hacia arriba a $0,05',
        image_note='Foto comercial de la presentación exacta' if photo else 'Imagen referencial local pendiente',
        reference_product=ref['name'] if ref else None)

if __name__=='__main__':
    rows=[]
    with concurrent.futures.ThreadPoolExecutor(max_workers=5) as pool:
        futures=[pool.submit(prepare,pair) for pair in enumerate(SPECS,1)]
        for spec,future in zip(SPECS,futures):
            row=future.result()
            rows.append(row)
            print(row['key'],row['pvp'],row['barcode'],flush=True)
    assert len({r['name'].casefold() for r in rows})==len(rows)
    assert len({r['barcode'] for r in rows})==len(rows)
    (ROOT/'catalogo.json').write_text(json.dumps(rows,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
    print('CATALOGO PREPARADO:',len(rows),'productos; imágenes:',sum(bool(r['image']) for r in rows))
