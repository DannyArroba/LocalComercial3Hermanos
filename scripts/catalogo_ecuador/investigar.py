"""Consulta pública de referencias comerciales; no modifica la base de datos."""
import concurrent.futures
import html
import json
import re
import sys
import urllib.parse
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent
CACHE = ROOT / 'referencias'
CACHE.mkdir(exist_ok=True)

def fetch(url):
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
    return urllib.request.urlopen(req, timeout=35).read().decode('utf-8')

def search(term):
    path = CACHE / (re.sub(r'[^a-z0-9]+', '-', term.lower()) + '.json')
    if path.exists():
        return term, json.loads(path.read_text(encoding='utf-8'))
    url = 'https://www.supermaxi.com/buscar?q=' + urllib.parse.quote(term)
    raw = fetch(url)
    links = list(dict.fromkeys(html.unescape(x) for x in re.findall(r'href="(/producto/[^"?]+)', raw)))
    # Keep public search results as evidence, not application dependencies.
    path.with_suffix('.html').write_text(raw, encoding='utf-8')
    path.write_text(json.dumps(links, ensure_ascii=False, indent=2), encoding='utf-8')
    return term, links

def product(url):
    if url.startswith('/'):
        url = 'https://www.supermaxi.com' + url
    key = url.rstrip('/').split('/')[-1]
    path = CACHE / (key + '.json')
    if path.exists():
        return json.loads(path.read_text(encoding='utf-8'))
    raw = fetch(url)
    for script in re.findall(r'<script\b[^>]*>(.*?)</script>', raw, flags=re.S):
        if '"@type":"Product"' not in script:
            continue
        try:
            data = json.loads(script)
        except ValueError:
            continue
        for item in data if isinstance(data, list) else [data]:
            if item.get('@type') == 'Product':
                item['source_url'] = url
                path.write_text(json.dumps(item, ensure_ascii=False, indent=2), encoding='utf-8')
                return item
    raise ValueError('Sin datos de producto: ' + url)

if __name__ == '__main__':
    mode, *terms = sys.argv[1:]
    func = search if mode == 'search' else product
    with concurrent.futures.ThreadPoolExecutor(max_workers=5) as pool:
        for arg, future in zip(terms, [pool.submit(func, term) for term in terms]):
            try:
                print(json.dumps({'query': arg, 'result': future.result()}, ensure_ascii=True))
            except Exception as exc:
                print(json.dumps({'query': arg, 'error': str(exc)}))
