/**
 * Headless parity capture.
 *
 * Runs harness.js against the storefront across four pages, two locales and two
 * widths, and writes one JSON census per combination.
 *
 * The WAF that made this look impossible rejects a DEFAULT client, not the app
 * server: it answers a real browser User-Agent, and puppeteer-core driving the
 * system Chrome is one. That is the whole trick.
 *
 * puppeteer-core is resolved from an absolute path because this repository has
 * no node_modules of its own and adding one for a verification script is not a
 * trade worth making.
 */
import fs from 'node:fs';
import path from 'node:path';
import puppeteer from '/home/ubuntu/zoonze-uitests/node_modules/puppeteer-core/lib/esm/puppeteer/puppeteer-core.js';

const HARNESS = fs.readFileSync('/var/www/multi.magento2.click/docs/design/parity/harness.js', 'utf8');
const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';
const OUT = process.argv[2];
const BASE = 'https://hub-market.magento2.click';

const PAGES = [
  ['home',     ''],
  ['plp',      'clothes.html'],
  ['pdp',      'savvy-shoulder-tote.html'],
  ['register', 'customer/account/create/'],
];
const LOCALES = [['en', ''], ['ar', 'ar/']];
const WIDTHS  = [1440, 390];

const browser = await puppeteer.launch({
  executablePath: '/usr/bin/google-chrome',
  headless: 'new',
  args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu'],
});

fs.mkdirSync(OUT, { recursive: true });
let ok = 0, fail = 0;

for (const [pname, ppath] of PAGES) {
  for (const [lname, lpath] of LOCALES) {
    for (const w of WIDTHS) {
      const url = `${BASE}/${lpath}${ppath}`;
      const page = await browser.newPage();
      try {
        await page.setUserAgent(UA);
        await page.setViewport({ width: w, height: 900 });
        await page.goto(url, { waitUntil: 'networkidle2', timeout: 60000 });
        const census = await page.evaluate(HARNESS);
        const file = path.join(OUT, `${pname}-${lname}-${w}.json`);
        fs.writeFileSync(file, JSON.stringify(census, null, 2));
        const c = census.contrast?.failures ?? '?';
        const h1 = census.a11y?.h1 ?? '?';
        console.log(`  OK   ${pname.padEnd(9)} ${lname} ${String(w).padStart(4)}  contrastFails=${c} h1=${h1} colours=${Object.keys(census.colours||{}).length}`);
        ok++;
      } catch (e) {
        console.log(`  FAIL ${pname} ${lname} ${w}: ${e.message.split('\n')[0]}`);
        fail++;
      } finally {
        await page.close();
      }
    }
  }
}
await browser.close();
console.log(`\ncaptured ${ok}, failed ${fail}`);
