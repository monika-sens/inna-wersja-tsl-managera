// Testy interakcji motywu (WordPress lub demo statyczne). Użycie: node interact.js <adres strony głównej> <outdir> [artykuł] [checklista] [kontakt]
const { chromium } = require('playwright');
(async () => {
  const [home, out, article, checklist, contact] = process.argv.slice(2);
  const b = await chromium.launch();
  const res = []; const errs = [];
  const ok = (name, cond, extra = '') => res.push((cond ? 'OK   ' : 'FAIL ') + name + (extra ? ' — ' + extra : ''));
  // Desktop
  let p = await b.newPage({ viewport: { width: 1440, height: 900 } });
  p.on('pageerror', e => errs.push(e.message));
  await p.goto(home, { waitUntil: 'load' });
  const btn = p.locator('.section-btn').first();
  await btn.click();
  ok('mega: otwarcie kliknięciem', await p.locator('.mega').first().isVisible());
  ok('mega: aria-expanded', (await btn.getAttribute('aria-expanded')) === 'true');
  await p.screenshot({ path: out + '/mega.png' });
  await p.locator('.section-btn').nth(1).click();
  ok('mega: jeden panel naraz', (await p.locator('.mega:visible').count()) === 1);
  await p.keyboard.press('Escape');
  ok('mega: Esc zamyka', (await p.locator('.mega:visible').count()) === 0);
  ok('mega: fokus wraca na przycisk', await p.evaluate(() => document.activeElement.classList.contains('section-btn')));
  await p.locator('.masthead-main .search-toggle').click();
  ok('szukaj: panel i fokus w polu', await p.evaluate(() => !document.getElementById('search-panel').hidden && document.activeElement.type === 'search'));
  await p.screenshot({ path: out + '/search.png' });
  await p.keyboard.press('Escape');
  await p.mouse.wheel(0, 1600); await p.waitForTimeout(500);
  ok('winieta: zwinięta po przewinięciu', await p.evaluate(() => document.getElementById('site-header').classList.contains('is-stuck')));
  ok('winieta: pasek działów u góry', await p.evaluate(() => Math.abs(document.querySelector('.sections').getBoundingClientRect().top) < 2));
  await p.screenshot({ path: out + '/stuck.png' });
  await p.locator('.sections-search').click();
  ok('szukaj po zwinięciu winiety', await p.evaluate(() => !document.getElementById('search-panel').hidden));
  await p.keyboard.press('Escape');
  // Newsletter (pusty e-mail → komunikat)
  await p.locator('.newsletter-form button[type=submit]').first().click();
  ok('newsletter: walidacja e-mail', (await p.locator('.newsletter-form .form-status').first().textContent()).includes('e-mail'));
  await p.locator('.newsletter-form input[type=email]').first().fill('test@firma.pl');
  await p.locator('.newsletter-form input[type=checkbox]').first().check();
  await p.locator('.newsletter-form button[type=submit]').first().click();
  await p.waitForTimeout(1500);
  ok('newsletter: odpowiedź', ((await p.locator('.newsletter-form .form-status').first().textContent()) || '').length > 5, await p.locator('.newsletter-form .form-status').first().textContent());
  if (article) {
    await p.goto(article, { waitUntil: 'load' });
    await p.mouse.wheel(0, 3000); await p.waitForTimeout(400);
    ok('artykuł: pasek postępu', await p.evaluate(() => { const s = document.querySelector('.reading-progress span'); return s && s.style.transform.includes('scaleX(0.'); }));
  }
  if (checklist) {
    await p.goto(checklist, { waitUntil: 'load' });
    await p.evaluate(() => localStorage.clear()); await p.reload();
    const boxes = p.locator('.checklist-item label');
    await boxes.nth(0).click(); await boxes.nth(1).click();
    ok('checklista: licznik', (await p.locator('[data-done]').textContent()) === '2');
    await p.reload();
    ok('checklista: zapis w przeglądarce', (await p.locator('[data-done]').textContent()) === '2');
    const n = await boxes.count();
    for (let i = 0; i < n; i++) { const inp = p.locator('.checklist-item input').nth(i); if (!(await inp.isChecked()) && !(await inp.getAttribute('data-critical') !== null)) await boxes.nth(i).click(); }
    ok('checklista: ostrzeżenie o punktach krytycznych', await p.locator('[data-alert]').isVisible());
    await p.locator('[data-reset]').click();
    ok('checklista: wyczyść', (await p.locator('[data-done]').textContent()) === '0');
  }
  if (contact) {
    await p.goto(contact, { waitUntil: 'load' });
    await p.locator('.tsl-form button[type=submit]').click();
    ok('formularz: błędy przy polach', (await p.locator('.tsl-form [aria-invalid=true]').count()) > 0);
    await p.fill('.tsl-form [name=name]', 'Jan Test').catch(() => {});
    const inputs = await p.locator('.tsl-form [required]').all();
    for (const i of inputs) { const t = await i.getAttribute('type'); const tag = await i.evaluate(e => e.tagName); if (t === 'checkbox') await i.check(); else if (t === 'email') await i.fill('jan@firma.pl'); else if (tag !== 'SELECT') await i.fill('Test wiadomości'); }
    await p.locator('.tsl-form button[type=submit]').click(); await p.waitForTimeout(1500);
    ok('formularz: wysłanie / komunikat', ((await p.locator('.tsl-form .form-status').textContent()) || '').length > 5, await p.locator('.tsl-form .form-status').textContent());
    await p.screenshot({ path: out + '/form.png', fullPage: false });
  }
  await p.close();
  // Mobile
  p = await b.newPage({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  p.on('pageerror', e => errs.push(e.message));
  await p.goto(home, { waitUntil: 'load' });
  ok('mobile: brak poziomego przewijania', await p.evaluate(() => document.documentElement.scrollWidth <= 390));
  ok('mobile: pasek działów (linki)', await p.locator('.section-a').first().isVisible());
  await p.locator('.menu-toggle').click();
  ok('mobile: menu otwarte', await p.locator('#mobile-nav').isVisible());
  ok('mobile: reszta strony inert', await p.evaluate(() => document.querySelector('main').inert === true));
  await p.locator('.accordion-toggle').first().click();
  ok('mobile: akordeon', await p.locator('.accordion-panel').first().isVisible());
  await p.screenshot({ path: out + '/mobile-menu.png' });
  await p.keyboard.press('Escape');
  ok('mobile: Esc zamyka menu', !(await p.locator('#mobile-nav').isVisible()));
  await p.evaluate(() => window.scrollBy(0, 1500)); await p.waitForTimeout(300); await p.evaluate(() => window.scrollBy(0, 600)); await p.waitForTimeout(500);
  ok('mobile: nagłówek chowa się przy czytaniu', await p.evaluate(() => document.body.classList.contains('hide-header')));
  await p.evaluate(() => window.scrollBy(0, -300)); await p.waitForTimeout(500);
  ok('mobile: nagłówek wraca przy przewinięciu w górę', await p.evaluate(() => !document.body.classList.contains('hide-header')));
  await b.close();
  console.log(res.join('\n'));
  console.log('Błędy JS:', errs.length ? errs : 'brak');
})();
