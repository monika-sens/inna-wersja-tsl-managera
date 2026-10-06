// Test demo statycznego z dysku (file://): wyszukiwarka, filtry, formularze, brak zapytań do sieci.
const { chromium } = require('playwright');
const path = require('path');
(async () => {
  const dir = process.argv[2]; const u = (p) => 'file://' + path.join(dir, p);
  const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 1366, height: 900 } });
  const res = [], errs = [], net = [];
  const ok = (n, c, x = '') => res.push((c ? 'OK   ' : 'FAIL ') + n + (x ? ' — ' + x : ''));
  p.on('pageerror', e => errs.push(e.message));
  p.on('request', r => { if (!r.url().startsWith('file:') && !r.url().startsWith('data:')) net.push(r.url()); });
  p.on('requestfailed', r => errs.push('failed: ' + r.url() + ' (' + (r.failure() && r.failure().errorText) + ') na ' + p.url()));
  await p.goto(u('index.html'));
  ok('start: tytuł', (await p.title()).includes('TSL Manager Editorial'));
  await p.click('text=Otwórz serwis');
  ok('start → serwis', p.url().endsWith('serwis/index.html'));
  ok('czcionki wczytane', await p.evaluate(async () => { await document.fonts.ready; return document.fonts.check('600 20px Newsreader') && [...document.fonts].some(f => f.family.includes('Newsreader') && f.status === 'loaded'); }));
  ok('jeden H1', (await p.locator('h1').count()) === 1);
  await p.locator('.section-btn').nth(3).click();
  ok('panel działu', await p.locator('#mega-ryzyko-bezpieczenstwo').isVisible());
  await p.locator('#mega-ryzyko-bezpieczenstwo .mega-list a').first().click();
  ok('przejście do obszaru', p.url().includes('ryzyko-bezpieczenstwo/'), p.url().split('serwis/')[1]);
  // wyszukiwarka
  await p.goto(u('serwis/index.html'));
  await p.locator('.masthead-main .search-toggle').click();
  await p.fill('#search-panel input[type=search]', 'cmr');
  await p.keyboard.press('Enter'); await p.waitForLoadState('load');
  const cnt = await p.locator('.result-count').textContent().catch(() => '');
  ok('szukaj: wyniki dla „cmr”', /Znaleziono \d+/.test(cnt), cnt);
  await p.goto(u('serwis/szukaj/index.html') + '?s=p%C5%82ynno%C5%9B%C4%87');
  ok('szukaj: polskie znaki', /Znaleziono/.test(await p.locator('.result-count').textContent().catch(() => '')), await p.locator('.result-count').textContent().catch(() => 'brak'));
  await p.goto(u('serwis/szukaj/index.html') + '?s=&format=decyzja-pod-lupa');
  ok('szukaj: filtr formatu (jak w WordPressie: 6)', /Znaleziono 6 /.test(await p.locator('.result-count').textContent().catch(() => '')), await p.locator('.result-count').textContent().catch(() => ''));
  await p.goto(u('serwis/szukaj/index.html') + '?s=xyzxyz');
  ok('szukaj: brak wyników', await p.locator('.empty-state').isVisible());
  await p.locator('.empty-state a').first().click(); await p.waitForLoadState('load');
  ok('szukaj: link z braku wyników działa', !p.url().includes('poza-demo'), p.url().split('serwis/')[1]);
  // filtr formatów w obszarze
  await p.goto(u('serwis/ludzie-organizacja/kierowcy/index.html'));
  const before = await p.locator('.post-list .story:visible').count();
  await p.locator('[data-format-filter] a[data-format="poradnik-operacyjny"]').click();
  const after = await p.locator('.post-list .story:visible').count();
  ok('obszar: filtr formatu', after > 0 && after < before, before + ' → ' + after);
  await p.locator('[data-format-filter] a[data-format="szkoda-pod-lupa"]').click();
  ok('obszar: pusty format → komunikat', (await p.locator('.empty-state').textContent()).includes('Brak materiałów w wybranym formacie'));
  // formularze
  await p.goto(u('serwis/kontakt/index.html'));
  await p.locator('.tsl-form button[type=submit]').click();
  ok('kontakt: walidacja', (await p.locator('.tsl-form [aria-invalid=true]').count()) > 0);
  for (const i of await p.locator('.tsl-form [required]').all()) { const t = await i.getAttribute('type'); const tag = await i.evaluate(e => e.tagName); if (t === 'checkbox') await i.check(); else if (t === 'email') await i.fill('jan@firma.pl'); else if (tag !== 'SELECT') await i.fill('Test'); }
  await p.locator('.tsl-form button[type=submit]').click();
  ok('kontakt: komunikat demo', (await p.locator('.tsl-form .form-status').textContent()).includes('wersja demonstracyjna'));
  await p.locator('.newsletter-form input[type=email]').first().fill('a@b.pl').catch(() => {});
  await p.goto(u('serwis/index.html'));
  await p.locator('.newsletter-form input[type=email]').fill('a@b.pl'); await p.locator('.newsletter-form input[type=checkbox]').check();
  await p.locator('.newsletter-form button').click();
  ok('newsletter: komunikat demo', (await p.locator('.newsletter-form .form-status').textContent()).includes('wersja demonstracyjna'));
  // checklista
  await p.goto(u('serwis/narzedzia/checklisty/weryfikacja-przewoznika/index.html'));
  await p.locator('.checklist-item label').first().click();
  ok('checklista: licznik', (await p.locator('[data-done]').textContent()) === '1');
  // poza demo
  await p.goto(u('serwis/index.html'));
  await p.locator('.legal-links a').first().click(); await p.waitForLoadState('load');
  ok('link do strony prawnej → poza demo', p.url().includes('poza-demo'));
  // pasek demo
  await p.goto(u('serwis/index.html'));
  ok('pasek demo widoczny', await p.locator('#demo-bar').isVisible());
  await p.click('#demo-bar-hide');
  ok('pasek demo ukryty', !(await p.locator('#demo-bar').isVisible()));
  // mobile
  const m = await b.newPage({ viewport: { width: 375, height: 800 }, isMobile: true });
  m.on('pageerror', e => errs.push(e.message));
  for (const pg of ['serwis/index.html', 'serwis/zarzadzanie-finanse/strategia-decyzje/wejscie-na-nowy-rynek-7-decyzji/index.html', 'serwis/riskbrief/index.html', 'serwis/narzedzia/index.html', 'serwis/szukaj/index.html', 'index.html']) {
    await m.goto(u(pg));
    const sw = await m.evaluate(() => document.documentElement.scrollWidth);
    ok('mobile 375 bez przewijania w bok: ' + pg, sw <= 375, String(sw));
  }
  await m.goto(u('serwis/index.html'));
  await m.locator('.menu-toggle').click();
  ok('mobile: menu', await m.locator('#mobile-nav').isVisible());
  await b.close();
  console.log(res.join('\n'));
  console.log('Błędy JS / zasobów:', errs.length ? errs : 'brak');
  console.log('Zapytania do sieci:', net.length ? [...new Set(net)] : 'brak');
})();
