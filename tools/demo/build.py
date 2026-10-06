#!/usr/bin/env python3
"""Lekkie demo TSL Manager Editorial — eksport statyczny z działającego WordPressa.

Użycie:
    python3 tools/demo/build.py <adres serwisu> [<katalog wynikowy>]
    np. python3 tools/demo/build.py http://localhost:8090 demo

Wymagania: Python 3.9+ i Pillow (konwersja ilustracji do WebP).
Serwis źródłowy: WordPress z motywem tsl-editorial, wtyczką tsl-core w trybie
demonstracyjnym i wczytanymi treściami.

Co powstaje (katalog wynikowy):
    index.html        strona startowa demo (ścieżka prezentacji)
    CZYTAJ-MNIE.txt   instrukcja dla odbiorcy
    serwis/           zapis serwisu: strony HTML, style, skrypty, ilustracje

Co robi eksport:
- pobiera strony serwisu (bez widoków, które wymagają serwera: paginacji,
  filtrów formatu, dowolnych wyszukiwań) i zapisuje je jako katalog/index.html
  ze ścieżkami względnymi — strony otwierają się dwuklikiem, bez serwera;
- ilustracje: tylko używane rozmiary, w formacie WebP (kilkanaście razy mniej
  niż oryginalne PNG); czcionki osadzone w CSS (przeglądarki blokują pliki
  czcionek otwierane z dysku);
- powtarzające się style wstawiane przez WordPress → jeden wspólny plik;
- wyszukiwarka i filtry działają w przeglądarce (indeks materiałów
  zapisany w serwis/szukaj/indeks.js), filtr formatów w działach — na liście;
- formularze pokazują komunikat wersji demonstracyjnej i niczego nie wysyłają;
- bez analityki, bez zapytań do internetu (poza linkami do serwisów
  zewnętrznych, np. oficjalnego kalkulatora e-TOLL);
- kontrola: każdy lokalny link prowadzi do istniejącego pliku, w plikach
  nie zostaje adres serwera źródłowego.
"""
import base64
import hashlib
import html
import io
import json
import os
import posixpath
import re
import shutil
import sys
import unicodedata
import urllib.error
import urllib.request
from urllib.parse import quote, unquote, urlsplit, parse_qsl, urlencode

try:
    from PIL import Image
except ImportError:  # pragma: no cover
    sys.exit('Brak biblioteki Pillow: pip install Pillow')

HERE = os.path.dirname(os.path.abspath(__file__))
BASE = sys.argv[1].rstrip('/') if len(sys.argv) > 1 else sys.exit(__doc__)
OUT = os.path.abspath(sys.argv[2] if len(sys.argv) > 2 else 'demo')
SITE = os.path.join(OUT, 'serwis')
HOST = urlsplit(BASE).netloc
THEME = 'wp-content/themes/tsl-editorial'
VERSION = '1.0.0'
PUBLIC = os.environ.get('TSL_PUBLIC_URL', 'https://tslmanager.pl').rstrip('/')

SKIP = re.compile(
    r'/(wp-json|wp-admin|wp-login\.php|xmlrpc\.php|feed|wp-includes)(/|$)'
    r'|/page/\d+/?'
    r'|[?&](format|page_id|p|replytocom|tsl_nl|token|nl|temat)='
    r'|\.(xml|php)$'
)
OFFDEMO = 'poza-demo/index.html'
SEARCH = 'szukaj/index.html'


# ---------- Pomocnicze ----------

def fetch(path):
    url = BASE + quote(path, safe='/?=&%+:,')
    req = urllib.request.Request(url, headers={'User-Agent': 'tsl-demo-export'})
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            return r.status, r.headers.get('Content-Type', ''), r.read()
    except urllib.error.HTTPError as e:
        return e.code, e.headers.get('Content-Type', ''), e.read()


def norm_key(path, query=''):
    """Klucz strony: zdekodowana ścieżka + uporządkowane zapytanie."""
    path = unquote(path or '/')
    if not path.endswith('/') and not re.search(r'\.[a-z0-9]{2,5}$', path, re.I):
        path += '/'
    pairs = sorted(parse_qsl(query, keep_blank_values=True))
    return path + ('?' + urlencode(pairs) if pairs else '')


def slug(text):
    text = unicodedata.normalize('NFKD', text).encode('ascii', 'ignore').decode()
    return re.sub(r'[^a-z0-9]+', '-', text.lower()).strip('-') or 'x'


def file_for(key):
    """Plik w eksporcie dla klucza strony."""
    path, _, query = key.partition('?')
    if path == '/' and query.startswith('s='):
        return SEARCH
    base = path.strip('/')
    if query:
        base = (base + '/' if base else '') + 'filtr-' + slug(query)
    return (base + '/' if base else '') + 'index.html'


def rel(src, tgt):
    return posixpath.relpath(tgt, posixpath.dirname(src) or '.')


def write(path, data, mode='w'):
    full = os.path.join(SITE, path)
    os.makedirs(os.path.dirname(full), exist_ok=True)
    if mode == 'wb':
        open(full, 'wb').write(data)
    else:
        open(full, 'w', encoding='utf-8').write(data)


def fold(text):
    """Tekst do wyszukiwania: małe litery, bez polskich znaków."""
    text = text.lower().replace('ł', 'l')
    return unicodedata.normalize('NFKD', text).encode('ascii', 'ignore').decode()


def strip_tags(s):
    return html.unescape(re.sub(r'\s+', ' ', re.sub(r'<[^>]+>', ' ', s))).strip()


# ---------- 1. Pobieranie stron ----------

def is_ours(href):
    parts = urlsplit(href)
    return parts.netloc == HOST or (not parts.netloc and href.startswith('/') and not href.startswith('//'))


def crawl():
    pages = {}
    queue = [('/', ''), ('/', 's='), ('/nie-ma-takiej-strony-demo/', '')]
    seen = set()
    while queue:
        path, query = queue.pop(0)
        key = norm_key(path, query)
        if key in seen:
            continue
        seen.add(key)
        status, ctype, body = fetch(path + ('?' + query if query else ''))
        if 'text/html' not in ctype:
            continue
        text = body.decode('utf-8', 'replace')
        if status == 404 and 'nie-ma-takiej-strony-demo' in path:
            pages['/404/'] = text
            continue
        if status != 200:
            continue
        pages[key] = text
        for href in re.findall(r'href="([^"#]+)', text):
            href = html.unescape(href)
            if not is_ours(href):
                continue
            parts = urlsplit(href)
            if re.search(r'\.[a-z0-9]{2,5}$', parts.path, re.I):
                continue  # pliki (PDF itp.) — zasoby, nie strony
            if SKIP.search(parts.path + ('?' + parts.query if parts.query else '')):
                continue
            if parts.path in ('', '/') and parts.query.startswith('s='):
                continue  # dowolne wyszukiwania działają w przeglądarce
            nk = norm_key(parts.path, parts.query)
            if nk not in seen:
                queue.append((unquote(parts.path) or '/', parts.query))
    return pages


# ---------- 2. Zasoby: ilustracje, pliki, style, skrypty ----------

assets = {}       # adres zasobu (ścieżka) → plik w eksporcie
inline_css = {}   # skrót → treść stylu


def asset_for(path):
    """Kopiuje zasób z serwera; ilustracje konwertuje do WebP."""
    path = unquote(path.split('?')[0])
    if path in assets:
        return assets[path]
    status, _, body = fetch(path)
    if status != 200:
        assets[path] = None
        return None
    name = path.lstrip('/')
    if re.search(r'\.(png|jpe?g)$', name, re.I) and '/uploads/' in name:
        im = Image.open(io.BytesIO(body))
        im = im.convert('RGB')
        if im.width > 1280:
            im = im.resize((1280, round(im.height * 1280 / im.width)), Image.LANCZOS)
        buf = io.BytesIO()
        im.save(buf, 'WEBP', quality=74, method=6)
        short = re.sub(r'^wp-content/uploads/', 'assets/img/', name)
        short = re.sub(r'\.(png|jpe?g)$', '.webp', short, flags=re.I)
        short = posixpath.dirname(short) + '/' + slug(posixpath.basename(short)[:-5])[:60].rstrip('-') + '-' + hashlib.sha1(name.encode()).hexdigest()[:6] + '.webp'
        write(short, buf.getvalue(), 'wb')
        assets[path] = short
    elif name.startswith('wp-content/uploads/'):
        short = 'assets/pliki/' + posixpath.basename(name)
        write(short, body, 'wb')
        assets[path] = short
    else:
        assets[path] = None
    return assets[path]


def theme_assets():
    """Style i skrypt motywu; czcionki osadzone w CSS (z dysku przeglądarki ich nie wczytują)."""
    status, _, css = fetch('/' + THEME + '/assets/css/main.css')
    css = css.decode('utf-8')

    def embed(m):
        _, _, raw = fetch('/' + THEME + '/assets/fonts/' + m.group(1))
        return 'url("data:font/woff2;base64,' + base64.b64encode(raw).decode() + '")'
    css = re.sub(r'url\("\.\./fonts/([\w.-]+\.woff2)"\)', embed, css)
    write('assets/css/main.css', css)
    _, _, js = fetch('/' + THEME + '/assets/js/main.js')
    write('assets/js/main.js', js, 'wb')
    for name in ('demo.js', 'demo.css'):
        shutil.copy(os.path.join(HERE, name), os.path.join(SITE, 'assets', 'js' if name.endswith('.js') else 'css', name))


# ---------- 3. Przetwarzanie stron ----------

DEMO_BAR = '''<div class="demo-bar" id="demo-bar" role="region" aria-label="Wersja demonstracyjna">
<p class="demo-bar-msg" id="demo-bar-msg"><strong>TSL Manager Editorial · demo {v}</strong> Materiały przykładowe; formularze nie wysyłają danych.</p>
<p class="demo-bar-links"><a href="{start}">Spis demo</a><button type="button" id="demo-bar-hide">Ukryj</button></p>
</div>
'''


def process(key, out, text, known):
    s = text
    # Elementy potrzebne tylko działającemu WordPressowi.
    s = re.sub(r'\s*<link rel=["\'](?:canonical|EditURI|https://api\.w\.org/|shortlink|alternate|preload|dns-prefetch|pingback)["\'][^>]*>', '', s)
    s = re.sub(r'\s*<meta (?:property|name)=["\'](?:og:|twitter:|generator|description)[^>]*>', '', s)
    s = re.sub(r'<meta name=["\']robots["\'][^>]*>', '', s)
    s = s.replace('<meta charset="UTF-8">', '<meta charset="UTF-8">\n<meta name="robots" content="noindex, nofollow">', 1)
    s = re.sub(r'\s*<script type="speculationrules">.*?</script>', '', s, flags=re.S)
    s = re.sub(r'\s*<script>window\.tslAnalytics=.*?</script>', '', s, flags=re.S)
    s = re.sub(r'\s*<script[^>]*id="tsl-analytics-js"[^>]*></script>', '', s)
    s = re.sub(r'\s*<style class="wp-fonts-local">.*?</style>', '', s, flags=re.S)
    s = re.sub(r'data-endpoint="[^"]*"', 'data-endpoint=""', s)

    # Powtarzające się style WordPressa → wspólny plik.
    def css_out(m):
        body = re.sub(r'/\*# sourceURL=.*?\*/', '', m.group(2)).strip()
        h = hashlib.sha1(body.encode()).hexdigest()[:10]
        inline_css[h] = body
        return '<link rel="stylesheet" href="%s">' % rel(out, 'assets/css/wp-%s.css' % h)
    s = re.sub(r'<style id=[\'"]([\w-]+)[\'"]>(.*?)</style>', css_out, s, flags=re.S)

    # Ilustracje: jeden plik WebP zamiast zestawu rozmiarów PNG.
    def img_out(m):
        tag = m.group(0)
        src = re.search(r'\ssrc="([^"]+)"', tag)
        if not src:
            return tag
        path = urlsplit(html.unescape(src.group(1))).path
        local = asset_for(path) if '/wp-content/uploads/' in path else None
        tag = re.sub(r'\s(srcset|sizes)="[^"]*"', '', tag)
        if local:
            tag = tag.replace(src.group(0), ' src="%s"' % rel(out, local))
        return tag
    s = re.sub(r'<img\b[^>]*>', img_out, s)

    # Linki i formularze → ścieżki względne.
    def link_out(m):
        attr, val = m.group(1), html.unescape(m.group(3))
        if not is_ours(val):
            return m.group(0)
        parts = urlsplit(val)
        frag = '#' + parts.fragment if parts.fragment else ''
        path = unquote(parts.path) or '/'
        if '/wp-content/' in path:
            if re.search(r'/themes/tsl-editorial/assets/css/main\.css$', path):
                return '%s="%s"' % (attr, rel(out, 'assets/css/main.css'))
            if re.search(r'/themes/tsl-editorial/assets/js/main\.js$', path):
                return '%s="%s"' % (attr, rel(out, 'assets/js/main.js'))
            local = asset_for(path)
            return '%s="%s"' % (attr, rel(out, local) + frag if local else rel(out, OFFDEMO))
        if path in ('', '/') and (parts.query.startswith('s=') or attr == 'action'):
            if attr == 'action':
                return '%s="%s"' % (attr, rel(out, SEARCH))
            q = '?' + html.escape(parts.query) if parts.query not in ('', 's=') else ''
            return '%s="%s%s"' % (attr, rel(out, SEARCH), q)
        nk = norm_key(path, parts.query)
        target = known.get(nk)
        if not target:
            return '%s="%s"' % (attr, rel(out, OFFDEMO))
        return '%s="%s%s"' % (attr, rel(out, target), frag)
    s = re.sub(r'\b(href|src|action)=(["\'])(.*?)\2', link_out, s)

    # Adres serwisu w tekście (np. „Źródło:” w wydruku checklisty) → adres docelowy serwisu.
    s = s.replace(BASE + '/', PUBLIC + '/').replace(BASE, PUBLIC)

    bar = DEMO_BAR.format(v=VERSION, start=rel(out, '../index.html'))
    tail = ('<link rel="stylesheet" href="%s">\n' % rel(out, 'assets/css/demo.css')) + bar
    if out == SEARCH:
        tail += '<script src="indeks.js"></script>\n'
    tail += '<script src="%s"></script>\n' % rel(out, 'assets/js/demo.js')
    s = s.replace('</body>', tail + '</body>', 1)
    s = s.replace('<html ', '<html data-demo="%s" ' % rel(out, 'index.html').replace('index.html', './'), 1)
    return s


# ---------- 4. Indeks wyszukiwarki ----------

def search_index(pages, known):
    """Wszystkie materiały (strony wyników „/?s=”) + tekst do przeszukiwania z ich stron."""
    items, page = [], 1
    while True:
        status, _, body = fetch('/page/%d/?s=' % page if page > 1 else '/?s=')
        if status != 200:
            break
        text = body.decode('utf-8', 'replace')
        found = re.findall(r'(<article class="story story-river.*?</article>)', text, re.S)
        if not found:
            break
        items += found
        page += 1
        if page > 50:
            break
    index = []
    for art in items:
        href = html.unescape(re.search(r'class="story-title[^"]*">\s*<a href="([^"]+)"', art).group(1))
        nk = norm_key(urlsplit(href).path)
        body_html = pages.get(nk, '')
        content = re.search(r'<div class="entry-content">(.*?)</div>\s*(?:<section|<div|</div>)', body_html, re.S)
        heads = ' '.join(re.findall(r'<h[23][^>]*>(.*?)</h[23]>', content.group(1) if content else '', re.S))
        text = strip_tags(art) + ' ' + strip_tags(heads) + ' ' + strip_tags(content.group(1) if content else '')[:4000]
        rendered = process(nk, SEARCH, '<body>' + art + '</body>', known)
        rendered = re.search(r'<body>(.*?)<link rel="stylesheet"', rendered, re.S).group(1)
        attrs = dict(re.findall(r'data-(format|pillar|transport)="([^"]*)"', art.split('>', 1)[0]))
        index.append({'h': rendered.strip(), 't': fold(text), 'p': attrs.get('pillar', ''), 'f': attrs.get('format', ''), 'r': attrs.get('transport', '')})
    return index


# ---------- Główny przebieg ----------

def main():
    if os.path.isdir(OUT):
        shutil.rmtree(OUT)
    os.makedirs(SITE)

    print('1/6 Pobieranie stron serwisu…')
    pages = crawl()
    known = {k: file_for(k) for k in pages if k != '/404/'}
    print('    stron:', len(known))

    print('2/6 Style, skrypty, czcionki…')
    theme_assets()

    print('3/6 Strony, ilustracje, linki…')
    for key, text in pages.items():
        out = OFFDEMO if key == '/404/' else known[key]
        s = process(key, out, text, known)
        if key == '/404/':
            s = s.replace('Błąd 404', 'Wersja demonstracyjna')
            s = s.replace('Nie znaleźliśmy tej strony', 'Ten widok nie jest częścią wersji demonstracyjnej')
            s = s.replace('Adres mógł się zmienić. Skorzystaj z wyszukiwarki albo wybierz jeden z filarów.',
                          'W serwisie uruchomionym na serwerze ten adres działa (np. kolejne strony list, dokumenty w przygotowaniu). W demo wybierz dział albo wróć na stronę główną.')
            s = re.sub(r'<title>[^<]*</title>', '<title>Widok poza wersją demo – TSL Manager</title>', s)
        write(out, s)
    for h, body in inline_css.items():
        write('assets/css/wp-%s.css' % h, body)

    print('4/6 Indeks wyszukiwarki…')
    index = search_index(pages, known)
    _, _, empty = fetch('/?s=brak-wynikow-demo-tsl')
    empty = re.search(r'(<div class="empty-state">.*?</div>)\s*</div>', empty.decode('utf-8'), re.S).group(1)
    empty = re.search(r'<body>(.*?)<link rel="stylesheet"', process('/?s=', SEARCH, '<body>' + empty + '</body>', known), re.S).group(1).strip()
    write('szukaj/indeks.js', 'window.TSL_SEARCH=' + json.dumps(index, ensure_ascii=False, separators=(',', ':')) + ';\nwindow.TSL_EMPTY=' + json.dumps(empty, ensure_ascii=False) + ';\n')
    print('    materiałów w indeksie:', len(index))

    print('5/6 Strona startowa…')
    start = open(os.path.join(HERE, 'start.html'), encoding='utf-8').read().replace('{{VERSION}}', VERSION)
    open(os.path.join(OUT, 'index.html'), 'w', encoding='utf-8').write(start)
    shutil.copy(os.path.join(HERE, 'CZYTAJ-MNIE.txt'), os.path.join(OUT, 'CZYTAJ-MNIE.txt'))

    print('6/6 Kontrola…')
    problems, leftovers, files = [], [], 0
    for root, _, fs in os.walk(OUT):
        for f in fs:
            full = os.path.join(root, f)
            files += 1
            if not f.endswith(('.html', '.css', '.js')):
                continue
            s = open(full, encoding='utf-8', errors='ignore').read()
            if HOST in s or quote(HOST, safe='') in s:
                leftovers.append(os.path.relpath(full, OUT))
            if f.endswith('.html'):
                for link in re.findall(r'(?:href|src)=["\']([^"\']*)', s):
                    link = html.unescape(link).split('#')[0].split('?')[0]
                    if not link or re.match(r'^[a-z][a-z0-9+.-]*:', link, re.I) or link.startswith('//'):
                        continue
                    if not os.path.exists(os.path.normpath(os.path.join(root, unquote(link)))):
                        problems.append((os.path.relpath(full, OUT), link))
    size = sum(os.path.getsize(os.path.join(r, f)) for r, _, fs in os.walk(OUT) for f in fs)
    print('    plików: %d, rozmiar: %.1f MB' % (files, size / 1048576))
    if leftovers:
        sys.exit('W plikach został adres serwera: %s' % leftovers[:10])
    if problems:
        sys.exit('Brakujące pliki: %s' % problems[:15])
    print('Gotowe:', OUT)


if __name__ == '__main__':
    main()
