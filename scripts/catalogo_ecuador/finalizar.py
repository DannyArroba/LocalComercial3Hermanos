"""Ajustes de catálogo para la precisión monetaria que ya usa la aplicación."""
import json
from decimal import Decimal, ROUND_HALF_UP
from investigar import ROOT

rows=json.loads((ROOT/'catalogo.json').read_text(encoding='utf-8'))
details={
'arroz-libra':'Arroz blanco seco, empacado en funda de 454 g para venta por libra.',
'azucar-libra':'Azúcar blanca granulada, empacada en funda de 454 g para venta por libra.',
'huevos':'Huevos medianos de gallina. Una unidad de venta corresponde a una cubeta completa de 30 huevos.',
'lenteja':'Lenteja seca, empacada en funda de 454 g. Seleccionar y lavar antes de cocinar.',
'frejol':'Fréjol rojo seco, empacado en funda de 454 g. Remojar y cocinar antes de consumir.',
'pollo':'Carne de pollo cruda, sin adobar, porción empacada de exactamente 1 kg. Conservar refrigerada y cocinar completamente.',
'papa':'Papa chola fresca, seleccionada y empacada en funda de 1 kg. Conservar en lugar fresco, seco y protegido de la luz.',
'cebolla':'Cebolla colorada fresca, empacada en funda de 1 kg. Conservar en lugar fresco y ventilado.',
'tomate':'Tomate riñón fresco, empacado en funda de 1 kg. Lavar antes de consumir.',
'verde':'Plátano verde fresco. Precio y stock por unidad; cocinar antes de consumir.',
'caramelos':'Caramelos surtidos, fraccionados en funda de 50 g. Conservar cerrados en lugar fresco y seco.',
'gomitas':'Gomitas de sabores surtidos, fraccionadas en funda de 50 g. Conservar cerradas en lugar fresco y seco.',
'jabon-tocador':'Jabón de tocador Jolly manzanilla. Venta por una barra de 100 g; la fotografía muestra el tripack del que se fracciona. Uso externo.',
'pilsener':'Cerveza Pilsener en botella retornable de 1 litro. Precio por botella de líquido con intercambio de envase; depósito no incluido. Venta exclusiva a mayores de 18 años.',
'light':'Cerveza Pilsener Light en botella retornable de 1 litro. Precio por botella de líquido con intercambio de envase; depósito no incluido. Venta exclusiva a mayores de 18 años.',
}
for r in rows:
    # La aplicación almacena precios BASE sin IVA con dos decimales.
    # No cambiar semántica ni esquema ni recalcular comprobantes históricos.
    r['pvp_propuesto']=r.get('pvp_propuesto',r['pvp'])
    r['price']=str(Decimal(r['price']).quantize(Decimal('.01'),rounding=ROUND_HALF_UP))
    r['pvp']=str((Decimal(r['price'])*(Decimal('1.15') if r['applies_iva'] else 1)).quantize(Decimal('.01'),rounding=ROUND_HALF_UP))
    r['rounding_difference']=str(Decimal(r['pvp'])-Decimal(r['pvp_propuesto']))
    r['cost_status']='Estimado, sin IVA recuperable; reemplazar con factura real del proveedor'
    r['stock_status']='Cantidad inicial propuesta y autorizada; pendiente de conteo físico'
    r['supplier_status']='Asignación administrativa a proveedor existente; no acredita distribución comercial de la marca'
    r['pack_status']='Agrupación propuesta para almacén; no es una caja de fábrica verificada, salvo las jabas de 12'
    r['description']=details.get(r['key'],r['name']+'. Una unidad de venta corresponde al envase o paquete completo indicado.')
    if r['category']=='Lácteos y huevos' and r['key']!='huevos':
        r['description']+=' Conservar según las indicaciones del fabricante; mantener refrigerado después de abrir.'
    if r['category']=='Limpieza del hogar':
        r['description']+=' Mantener fuera del alcance de los niños y separado de los alimentos.'
    if r['key']=='negra':
        r['description']+=' Venta exclusiva a mayores de 18 años.'
    if r['key'] in ['arroz-libra','azucar-libra','lenteja','frejol','huevos','caramelos','gomitas','pollo','papa','cebolla','tomate','verde','jabon-tocador']:
        r['description']+=' Fotografía referencial.'
(ROOT/'catalogo.json').write_text(json.dumps(rows,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
print('Listos:',len(rows),'Máximo ajuste de PVP por IVA:',max(abs(Decimal(r['rounding_difference'])) for r in rows))
