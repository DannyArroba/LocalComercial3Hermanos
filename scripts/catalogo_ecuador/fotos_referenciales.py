"""Fotografías de alimentos sin marca, con atribución de Wikimedia Commons."""
import concurrent.futures
import json
import sys
import time
import urllib.parse
import urllib.request
from investigar import ROOT, fetch

TERMS = {
'arroz-libra':'rice grains bowl', 'azucar-libra':'white sugar bowl',
'lenteja':'brown lentils', 'frejol':'red kidney beans dried',
'pollo':'raw chicken meat', 'papa':'potatoes', 'cebolla':'red onions',
'tomate':'tomatoes', 'verde':'green plantains', 'huevos':'chicken eggs tray',
'caramelos':'wrapped candy', 'gomitas':'gummy bears', 'jabon-tocador':'bar soap',
}

def api(params):
    params.update(action='query',format='json')
    url='https://commons.wikimedia.org/w/api.php?'+urllib.parse.urlencode(params)
    request=urllib.request.Request(url,headers={'User-Agent':'TienditaNexarCatalog/1.0 (local product catalog)'})
    for attempt in range(4):
        try:
            return json.load(urllib.request.urlopen(request,timeout=35))
        except urllib.error.HTTPError as exc:
            if exc.code!=429 or attempt==3:
                raise
            time.sleep(4*(attempt+1))

def search(pair):
    key,term=pair
    data=api(dict(generator='search',gsrsearch=term,gsrnamespace=6,gsrlimit=4,
        prop='imageinfo',iiprop='url|extmetadata',iiurlwidth=640))
    pages=list(data.get('query',{}).get('pages',{}).values())
    pages.sort(key=lambda x:x.get('index',0))
    return key,pages

if __name__=='__main__':
    path=ROOT/'referencias'/'commons-candidatas.json'
    if sys.argv[1]=='search':
        result=json.loads(path.read_text(encoding='utf-8')) if path.exists() else {}
        for pair in TERMS.items():
            if pair[0] in result:
                continue
            key,pages=search(pair)
            result[key]=pages
            path.write_text(json.dumps(result,ensure_ascii=False,indent=2),encoding='utf-8')
            time.sleep(2)
        print(json.dumps({k:[p['title'] for p in v] for k,v in result.items()},ensure_ascii=True,indent=2))
    else:
        result=json.loads(path.read_text(encoding='utf-8'))
        rows=json.loads((ROOT/'catalogo.json').read_text(encoding='utf-8'))
        choices=json.loads((ROOT/'seleccion-fotos.json').read_text(encoding='utf-8'))
        for row in rows:
            if row['key'] not in choices:
                continue
            chosen=result[row['key']][choices[row['key']]]
            info=chosen['imageinfo'][0]
            source=info.get('thumburl',info['url'])
            data=urllib.request.urlopen(urllib.request.Request(source,headers={'User-Agent':'Mozilla/5.0'}),timeout=35).read()
            if not data.startswith(b'\xff\xd8'):
                raise ValueError('Foto no JPEG: '+chosen['title'])
            filename='ec-20261118-'+row['key']+'-referencial.jpg'
            (ROOT.parents[1]/'frontend'/'uploads'/filename).write_bytes(data)
            row.update(image=filename,photo_source=source,image_note='Foto referencial del alimento; no representa una marca o un lote físico',
                photo_attribution=dict(title=chosen['title'],url=info['descriptionurl'],metadata=info.get('extmetadata',{})))
            print(row['key'],chosen['title'],flush=True)
        (ROOT/'catalogo.json').write_text(json.dumps(rows,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
