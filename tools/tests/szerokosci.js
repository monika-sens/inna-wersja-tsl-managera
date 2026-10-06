const { chromium } = require('playwright');
(async () => {
  const [base, ...paths] = process.argv.slice(2);
  const b = await chromium.launch(); const out = [];
  for (const w of [360, 390, 600, 768, 820, 1024, 1180, 1280, 1366, 1440, 1920]) {
    const p = await b.newPage({ viewport: { width: w, height: 900 } });
    for (const path of paths) {
      await p.goto(base + path, { waitUntil: 'domcontentloaded' });
      const sw = await p.evaluate(() => document.documentElement.scrollWidth);
      const h1 = await p.locator('h1').count();
      if (sw > w || h1 !== 1) out.push(`${w} ${path}: scrollWidth ${sw}, h1 ${h1}`);
    }
    await p.close();
  }
  console.log(out.length ? out.join('\n') : 'Wszystkie szerokości: bez przewijania w bok, dokładnie jeden H1');
  await b.close();
})();
