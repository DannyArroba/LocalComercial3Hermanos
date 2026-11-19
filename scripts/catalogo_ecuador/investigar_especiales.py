"""Fuentes comerciales que no figuran en la búsqueda de Supermaxi."""
import html
from html.parser import HTMLParser
import json
import re
import sys
from investigar import ROOT, fetch

URLS={
'ancisa':'https://ancisa.com.ec/categoria-producto/supermercado/helados/',
'pinguinovaso':'https://www.heladospinguino.com.ec/p/vasito-vainilla.html/07861047722576',
'pony1000':'https://www.tia.com.ec/supermercado/aguas-y-bebidas/bebidas-gaseosas-y-maltas/bebida-pony-malta-1-l-242310000.html',
'cocaoficial':'https://www.entuhogar.ec/pedir',
'lechevaquita':'https://www.tia.com.ec/supermercado/lacteos/leches-blancas?p=3',
'vitaleche':'https://www.tia.com.ec/supermercado/lacteos?p=10',
'ranchito':'https://elranchito.com.ec/categoria-producto/helados/',
}
class Extract(HTMLParser):
    def __init__(self):
        super().__init__(); self.images=[]; self.links=[]; self.metas={}; self.script=None; self.scripts=[]; self.text=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='meta': self.metas[a.get('property',a.get('name',''))]=a.get('content','')
        if tag=='a' and a.get('href'): self.links.append(a['href'])
        if tag=='img' and a.get('src'): self.images.append((a.get('alt'),a.get('src')))
        if tag=='script': self.script=''
    def handle_data(self,data):
        if self.script is not None: self.script+=data
        else: self.text.append(data.strip())
    def handle_endtag(self,tag):
        if tag=='script' and self.script is not None:
            if 'Product' in self.script or 'product' in self.script[:80]: self.scripts.append(self.script)
            self.script=None

if __name__=='__main__':
    saved=ROOT/'referencias'/'especiales.json'
    records=json.loads(saved.read_text(encoding='utf-8')) if saved.exists() else {}
    for key in sys.argv[1:] or URLS:
        url=URLS.get(key,key)
        try:
            raw=fetch(url)
        except Exception as exc:
            print(key, 'ERROR', str(exc),flush=True)
            continue
        p=Extract(); p.feed(raw)
        records[key]=dict(url=url,metas=p.metas,images=p.images,links=list(dict.fromkeys(p.links)),scripts=p.scripts,text=p.text)
        print(key,json.dumps(dict(metas=p.metas,links=[x for x in p.links if any(w in x.lower() for w in ['polito','vasito','vaso','leche-la-vaquita','leche-vita','paleta'])],images=p.images[:6],scripts=[x[:1200] for x in p.scripts[:2]]),ensure_ascii=True)[:6000])
        saved.write_text(json.dumps(records,ensure_ascii=False,indent=2),encoding='utf-8')
