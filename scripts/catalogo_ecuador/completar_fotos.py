import json
import re
import urllib.request
from investigar import ROOT, fetch
from preparar import ref_for, SPECS

rows=json.loads((ROOT/'catalogo.json').read_text(encoding='utf-8'))
specs={s[0]:s for s in SPECS}
uploads=ROOT.parents[1]/'frontend'/'uploads'
for row in rows:
    code=row['barcode']
    if len(code)==11:
        row['barcode']='0'+code
        row['barcode_note']='UPC-A con cero inicial omitido por el catálogo de origen; se conserva la longitud de 12 dígitos.'
    if row['image']:
        continue
    key=row['key']
    if key in ['papa','frejol','gomitas','jabon-tocador']:
        ref=ref_for(specs[key])
        url=ref['image'][0]
        name='ec-20261118-'+key+'-referencia.png'
        data=urllib.request.urlopen(url,timeout=30).read()
        assert data.startswith(b'\x89PNG\r\n\x1a\n')
        (uploads/name).write_bytes(data)
        row.update(image=name,photo_source=url,image_note='Foto referencial del producto; empaque o cantidad pueden diferir de la venta local')
        if key=='jabon-tocador':
            row['description']+='Fotografía del tripack de origen; se vende y descuenta una sola barra, no el tripack. '
    elif key=='cola2500':
        # Conservar la imagen del producto existente; no sustituir por otra capacidad.
        row.update(image='620e767f925e901578b7f13e.png',image_note='Fotografía conservada del catálogo local anterior')
    elif key in ['pilsener','light']:
        url='https://cervezasguayaquil.com/pilsener-litro-1000-ml/' if key=='pilsener' else 'https://tienda.ecuadorcupon.com/alcoholicas/124-715-jaba-pilsener-ligth-de-litro-0000000001243.html'
        raw=fetch(url)
        match=re.search(r'<meta[^>]*(?:property|name)=[\"\x27]og:image[\"\x27][^>]*content=[\"\x27]([^\"\x27]+)',raw,re.I)
        if not match:
            match=re.search(r'<meta[^>]*content=[\"\x27]([^\"\x27]+)[\"\x27][^>]*(?:property|name)=[\"\x27]og:image',raw,re.I)
        if not match:
            raise ValueError('Sin foto comercial: '+url)
        photo=__import__('html').unescape(match[1])
        data=urllib.request.urlopen(urllib.request.Request(photo,headers={'User-Agent':'Mozilla/5.0'}),timeout=30).read()
        ext='.png' if data.startswith(b'\x89PNG') else '.jpg' if data.startswith(b'\xff\xd8') else '.webp' if data[:4]==b'RIFF' else None
        if not ext: raise ValueError('Formato no válido: '+photo)
        name='ec-20261118-'+key+'-litro'+ext
        (uploads/name).write_bytes(data)
        row.update(image=name,photo_source=photo,image_note='Foto del producto retornable; venta por botella de 1 L')
    else:
        raise ValueError('Falta foto: '+key)
    print(key,row['image'])
(ROOT/'catalogo.json').write_text(json.dumps(rows,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
