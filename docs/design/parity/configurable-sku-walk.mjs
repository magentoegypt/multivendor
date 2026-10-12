/**
 * Configurable SKU walk (CL036-TC104) — the SKU under the product title must
 * follow the variant the customer picks. Exits non-zero on a failure.
 *
 *   node docs/design/parity/configurable-sku-walk.mjs [url-key ...]
 *
 * Default product: QA's "test-config-pro" (2546), colour SWATCHES plus a size
 * DROPDOWN, so both kinds of option input are exercised. On each store:
 *
 * 1. On load, before any choice: the product's own SKU.
 * 2. One option chosen of two: still the product's own SKU, never a guess.
 * 3. Every option chosen: the SKU of exactly that variant, taken from GraphQL
 *    (`variants { product { sku } attributes { code value_index } }`), which is
 *    an independent source and also what the mobile app reads.
 * 4. Another choice for the same option: the new variant's SKU.
 * 5. A choice cleared (the selected swatch clicked again): back to the
 *    product's own SKU.
 * 6. Opened from a link that pre-selects both options (`#93=57&223=301`): that
 *    variant's SKU without a click.
 *
 * Serial, one tab (memory: host-cannot-take-agent-fanout). About a minute.
 */
import puppeteer from '/home/ubuntu/zoonze-uitests/node_modules/puppeteer-core/lib/esm/puppeteer/puppeteer-core.js';

const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';
const BASE = process.env.HM_BASE || 'https://hub-market.magento2.click';
const URL_KEYS = process.argv.slice(2).length ? process.argv.slice(2) : ['test-config-pro'];
const STORES = ['ar', 'en'];

const failures = [];
const check = (where, what, ok, detail = '') => {
  console.log(`  ${ok ? 'ok  ' : 'FAIL'} ${what}${ok || !detail ? '' : `  (${detail})`}`);
  if (!ok) failures.push(`${where}: ${what}`);
};

async function variantsOf(store, urlKey) {
  const query = `{ products(filter:{url_key:{eq:"${urlKey}"}}) { items { sku ... on ConfigurableProduct {
    variants { product { sku } attributes { code value_index } } } } } }`;
  const res = await fetch(`${BASE}/graphql`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'User-Agent': UA, Store: store },
    body: JSON.stringify({ query }),
  });
  const item = (await res.json()).data.products.items[0];
  return { sku: item.sku, variants: item.variants || [] };
}

const expectedSku = (data, chosen) => {
  const hit = data.variants.find((v) => v.attributes.every((a) => String(chosen[a.code]) === String(a.value_index)));
  return hit ? hit.product.sku : null;
};

const browser = await puppeteer.launch({
  executablePath: '/opt/google/chrome/chrome',
  headless: 'new',
  args: ['--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage'],
});
const page = await browser.newPage();
await page.setUserAgent(UA);
await page.setViewport({ width: 1366, height: 900 });

const sku = () => page.$eval('.product-info-main .product.attribute.sku .value', (e) => e.textContent.trim());
const chosen = () => page.$$eval('.swatch-attribute[data-option-selected]', (els) =>
  Object.fromEntries(els.map((e) => [e.dataset.attributeCode, e.dataset.optionSelected])));
const settle = () => new Promise((r) => setTimeout(r, 400));
const waitForSwatches = () => page.waitForSelector('.swatch-attribute', { timeout: 60000 });

// Picks the n-th enabled option of an attribute, whichever kind of input it is.
async function pick(code, n) {
  const kind = await page.$eval(`.swatch-attribute[data-attribute-code="${code}"]`, (el) =>
    (el.querySelector('select.swatch-select') ? 'select' : 'swatch'));
  if (kind === 'select') {
    await page.$eval(`.swatch-attribute[data-attribute-code="${code}"] select.swatch-select`, (sel, i) => {
      // The swatch widget's "Choose an Option…" placeholder carries value "0".
      const options = [...sel.options].filter((o) => o.value && o.value !== '0' && !o.disabled);
      sel.value = options[i].value;
      window.jQuery(sel).trigger('change');
    }, n);
  } else {
    const swatches = await page.$$(`.swatch-attribute[data-attribute-code="${code}"] .swatch-option:not(.disabled)`);
    await swatches[n].click();
  }
  await settle();
}

for (const urlKey of URL_KEYS) {
  for (const store of STORES) {
    const where = `${store}/${urlKey}`;
    console.log(`\n${where}`);
    const data = await variantsOf(store, urlKey);
    await page.goto(`${BASE}/${store}/${urlKey}.html`, { waitUntil: 'networkidle2', timeout: 120000 });
    await waitForSwatches();
    await settle();
    const codes = await page.$$eval('.swatch-attribute', (els) => els.map((e) => e.dataset.attributeCode));

    check(where, `on load: the product's own SKU "${data.sku}"`, (await sku()) === data.sku, await sku());

    await pick(codes[0], 0);
    if (codes.length > 1) {
      check(where, `${codes[0]} chosen, ${codes.slice(1).join('+')} not: still "${data.sku}"`, (await sku()) === data.sku, await sku());
    }
    for (const code of codes.slice(1)) {
      await pick(code, 0);
    }
    let want = expectedSku(data, await chosen());
    check(where, `all chosen ${JSON.stringify(await chosen())}: variant SKU "${want}"`, want !== null && (await sku()) === want, await sku());

    const last = codes[codes.length - 1];
    await pick(last, 1);
    want = expectedSku(data, await chosen());
    check(where, `${last} changed ${JSON.stringify(await chosen())}: variant SKU "${want}"`, want !== null && (await sku()) === want, await sku());

    const firstSelected = await page.$(`.swatch-attribute[data-attribute-code="${codes[0]}"] .swatch-option.selected`);
    if (firstSelected) {
      await firstSelected.click();
      await settle();
      check(where, `${codes[0]} cleared: back to "${data.sku}"`, (await sku()) === data.sku, await sku());
    }

    const ids = await page.$$eval('.swatch-attribute', (els) => els.map((e) => [e.dataset.attributeCode, e.dataset.attributeId]));
    const target = data.variants[data.variants.length - 1];
    const hash = ids.map(([code, id]) => `${id}=${target.attributes.find((a) => a.code === code).value_index}`).join('&');
    await page.goto('about:blank');
    await page.goto(`${BASE}/${store}/${urlKey}.html#${hash}`, { waitUntil: 'networkidle2', timeout: 120000 });
    await waitForSwatches();
    await settle();
    check(where, `opened with #${hash}: variant SKU "${target.product.sku}"`, (await sku()) === target.product.sku, await sku());
  }
}

await browser.close();
console.log(failures.length ? `\n${failures.length} check(s) FAILED:\n${failures.join('\n')}` : '\nAll checks passed');
process.exit(failures.length ? 1 : 0);
