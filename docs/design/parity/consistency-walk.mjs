/**
 * Number-consistency walk — run after any change to search, listing, layered
 * navigation, translations or number formatting. Exits non-zero on a failure.
 *
 *   node docs/design/parity/consistency-walk.mjs
 *
 * It checks CLASSES of defect that QA kept finding one instance at a time:
 *
 * 1. DIGITS — the Arabic storefront writes numbers in Latin digits, like its
 *    prices. Any Arabic-Indic digit in visible text fails, wherever it came from
 *    (a formatter, a dictionary string, CMS copy, a JS toLocaleString). "٥"
 *    reads as a Latin "0": QA02 BUG-04 reported 5 products under "0 results".
 *    The rule lives in MagentoEgypt\SetExtend\Model\LatinDigits.
 *
 * 2. COUNTS — on a search results page, the summary line, the Products tab and
 *    the toolbar must state the same number (QA02 BUG-13: 69 / 63 / 63), and a
 *    filtered listing's counter must match the cards it shows.
 *
 * 3. CATEGORIES — every category the header autocomplete offers for a query
 *    must also be on the results page's Categories tab (QA02 BUG-14: the tab
 *    read 0 while autocomplete listed categories). Both read Algolia's
 *    categories index; see MagentoEgypt\SearchLanding\Model\SearchFacets.
 *
 * Serial, one tab: this host has two cores and five FPM children
 * (memory: host-cannot-take-agent-fanout). About two minutes.
 */
import puppeteer from '/home/ubuntu/zoonze-uitests/node_modules/puppeteer-core/lib/esm/puppeteer/puppeteer-core.js';

const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';
const BASE = process.env.HM_BASE || 'https://hub-market.magento2.click';
const NON_LATIN_DIGIT = /[٠-٩۰-۹]/;

const ARABIC_PAGES = [
  'ar/',
  'ar/clothes.html',
  'ar/clothes.html?price=12.75-486.75&rating=45&vendor=6',
  'ar/catalogsearch/result/?q=test',
  'ar/savvy-shoulder-tote.html',
  'ar/checkout/cart/',
];
const SEARCHES = [
  ['en', 'bag'],
  ['ar', 'test'],
  ['ar', 'حقيبة'],
  ['ar', 'حلقي'],
];
const FILTERED_LISTINGS = [
  'ar/clothes.html?price=12.75-486.75&rating=45&vendor=6',
  'en/clothes.html?vendor=6',
];

const failures = [];
const fail = (where, what) => { failures.push(`${where}: ${what}`); console.log(`  FAIL ${what}`); };
const pass = (what) => console.log(`  ok   ${what}`);
const firstInt = (s) => { const m = /\d+/.exec(s || ''); return m ? parseInt(m[0], 10) : null; };

const browser = await puppeteer.launch({
  executablePath: '/usr/bin/google-chrome',
  headless: 'new',
  args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu'],
});
const page = await browser.newPage();
await page.setUserAgent(UA);
await page.setViewport({ width: 1440, height: 900 });

async function open(path) {
  await page.goto(`${BASE}/${path}`, { waitUntil: 'networkidle2', timeout: 90000 });
}

/** Visible text snippets around any Arabic-Indic digit, including the autocomplete panel. */
async function nonLatinDigits() {
  return page.evaluate((re) => {
    const rx = new RegExp(re);
    const out = [];
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    for (let n = walker.nextNode(); n; n = walker.nextNode()) {
      const el = n.parentElement;
      if (!el || !rx.test(n.nodeValue) || el.closest('script,style,noscript,template')) continue;
      if (!el.getClientRects().length) continue; // not rendered
      out.push(n.nodeValue.trim().slice(0, 60));
    }
    return out;
  }, NON_LATIN_DIGIT.source);
}

async function typeInHeaderSearch(query) {
  const input = await page.waitForSelector('.aa-Input, #search', { timeout: 20000 });
  await input.click();
  await input.type(query, { delay: 60 });
  await page.waitForSelector('.aa-Panel', { timeout: 20000 }).catch(() => null);
  await new Promise((r) => setTimeout(r, 2500)); // let every source answer
}

console.log('1. Digits on the Arabic storefront');
for (const path of ARABIC_PAGES) {
  console.log(`- ${path}`);
  try {
    await open(path);
    let found = await nonLatinDigits();
    found.length ? fail(path, `Arabic-Indic digits: ${JSON.stringify(found.slice(0, 5))}`) : pass('page text');
    if (path === 'ar/') {
      await typeInHeaderSearch('حقيبة');
      found = await nonLatinDigits();
      found.length ? fail(path, `autocomplete digits: ${JSON.stringify(found.slice(0, 5))}`) : pass('autocomplete panel');
    }
  } catch (e) { fail(path, e.message); }
}

console.log('2+3. Search counts and categories');
for (const [lang, q] of SEARCHES) {
  const path = `${lang}/catalogsearch/result/?q=${encodeURIComponent(q)}`;
  console.log(`- ${lang} "${q}"`);
  try {
    await open(path);
    await page.waitForFunction(
      () => /\d/.test(document.querySelector('.hm-live__summary')?.textContent || ''),
      { timeout: 20000 }
    ).catch(() => null);
    const c = await page.evaluate(() => ({
      summary: document.querySelector('.hm-live__summary')?.textContent,
      tab: document.querySelector('.hm-live__tab[data-panel="products"]')?.textContent,
      total: document.querySelector('.toolbar-amount[data-total]')?.getAttribute('data-total'),
      shown: document.querySelector('.toolbar-amount .toolbar-number')?.textContent,
      catTab: document.querySelector('.hm-live__tab[data-panel="categories"]')?.textContent,
      catUrls: [...document.querySelectorAll('.hm-srp__panel[data-panel="categories"] a.hm-live__row')]
        .map((a) => a.pathname),
    }));
    const n = [c.summary, c.tab, c.total, c.shown].map(firstInt);
    if (c.total === null || c.total === undefined) {
      pass('no product toolbar (empty result)');
    } else if (n.every((x) => x === n[0])) {
      pass(`summary = Products tab = toolbar = ${n[0]}`);
    } else {
      fail(path, `counts disagree: summary ${n[0]}, tab ${n[1]}, data-total ${n[2]}, toolbar ${n[3]}`);
    }

    if (firstInt(c.catTab) !== c.catUrls.length) {
      fail(path, `Categories tab says ${firstInt(c.catTab)} but lists ${c.catUrls.length}`);
    }
    await typeInHeaderSearch(q);
    const offered = await page.evaluate(() =>
      [...document.querySelectorAll('.aa-Panel [data-autocomplete-source-id="categories"] a')]
        .map((a) => a.pathname));
    const missing = offered.filter((u) => !c.catUrls.includes(u));
    missing.length
      ? fail(path, `autocomplete offers ${missing.join(', ')} but the Categories tab does not`)
      : pass(`categories: autocomplete ${offered.length} ⊆ tab ${c.catUrls.length}`);
  } catch (e) { fail(path, e.message); }
}

console.log('2. Filtered listing counter vs cards');
for (const path of FILTERED_LISTINGS) {
  console.log(`- ${path}`);
  try {
    await open(path);
    const r = await page.evaluate(() => ({
      total: document.querySelector('.toolbar-amount[data-total]')?.getAttribute('data-total'),
      shown: document.querySelector('.toolbar-amount .toolbar-number')?.textContent,
      cards: document.querySelectorAll('.products.wrapper .product-item').length,
      paged: !!document.querySelector('.pages .item.pages-item-next'),
    }));
    const total = firstInt(r.total);
    if (firstInt(r.shown) !== total) fail(path, `visible counter ${r.shown} ≠ data-total ${total}`);
    else if (!r.paged && total !== r.cards) fail(path, `counter ${total} but ${r.cards} cards`);
    else pass(`counter ${total}, cards ${r.cards}${r.paged ? ' (paged)' : ''}`);
  } catch (e) { fail(path, e.message); }
}

await browser.close();
console.log(failures.length ? `\n${failures.length} FAILURE(S)\n${failures.join('\n')}` : '\nALL CHECKS PASS');
process.exit(failures.length ? 1 : 0);
