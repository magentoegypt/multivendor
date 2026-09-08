/**
 * Adds one product to the cart and walks as far as the payment step, checking
 * the QA cycle 1 findings on the way. It deliberately STOPS before Place Order —
 * this is a live store and a smoke test should not create a real order.
 */
import puppeteer from '/home/ubuntu/zoonze-uitests/node_modules/puppeteer-core/lib/esm/puppeteer/puppeteer-core.js';

const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';
const BASE = 'https://hub-market.magento2.click';
const ok = (b) => (b ? 'PASS' : 'FAIL');

const browser = await puppeteer.launch({
  executablePath: '/usr/bin/google-chrome',
  headless: 'new',
  args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu'],
});
const page = await browser.newPage();
await page.setUserAgent(UA);
await page.setViewport({ width: 1440, height: 1000 });

// --- 1. add a product ------------------------------------------------------
await page.goto(`${BASE}/clothes.html`, { waitUntil: 'networkidle2', timeout: 60000 });
const added = await page.evaluate(() => {
  const btn = document.querySelector('button.action.tocart');
  if (!btn) return false;
  btn.click();
  return true;
});
console.log(`add to cart from PLP           ${ok(added)}`);
await new Promise((r) => setTimeout(r, 6000));

// --- 2. cart ---------------------------------------------------------------
await page.goto(`${BASE}/checkout/cart/`, { waitUntil: 'networkidle2', timeout: 60000 });
const cart = await page.evaluate(() => {
  const upd = document.querySelector('.cart.main.actions .action.update');
  const vis = upd ? getComputedStyle(upd).display !== 'none' : null;
  const vendor = document.querySelector('.hm-cart-group__vendor');
  const aside = document.querySelector('.hm-cart-line__aside');
  const body  = document.querySelector('.hm-cart-line__body');
  return {
    lines: document.querySelectorAll('.hm-cart-line').length,
    updateInDom: !!upd,
    updateVisible: vis,
    vendorColour: vendor ? getComputedStyle(vendor).color : null,
    crosssell: document.body.innerText.includes('More Choices'),
    asideH: aside ? Math.round(aside.getBoundingClientRect().height) : null,
    bodyH: body ? Math.round(body.getBoundingClientRect().height) : null,
  };
});
console.log(`cart lines rendered            ${ok(cart.lines > 0)}  (${cart.lines})`);
console.log(`"Update Shopping Cart" hidden  ${ok(cart.updateInDom && cart.updateVisible === false)}  (inDom=${cart.updateInDom} visible=${cart.updateVisible})`);
console.log(`"More Choices" removed         ${ok(!cart.crosssell)}`);
console.log(`vendor name blue               ${ok(cart.vendorColour === 'rgb(29, 78, 216)')}  (${cart.vendorColour})`);
console.log(`aside stretches to body        ${ok(cart.asideH !== null && cart.bodyH !== null && Math.abs(cart.asideH - cart.bodyH) <= 2)}  (aside=${cart.asideH} body=${cart.bodyH})`);

// --- 3. checkout -----------------------------------------------------------
await page.goto(`${BASE}/checkout/`, { waitUntil: 'networkidle2', timeout: 90000 });
await new Promise((r) => setTimeout(r, 8000));
const co = await page.evaluate(() => {
  const icon = document.querySelector('.hm-step-title__icon');
  return {
    stepIconColour: icon ? getComputedStyle(icon).color : null,
    shippingTitle: document.querySelector('.hm-step-title')?.innerText?.trim() ?? null,
    fieldGap: (() => {
      const f = document.querySelector('.opc-wrapper .form-shipping-address .fieldset > .field');
      return f ? getComputedStyle(f).marginBlockEnd : null;
    })(),
    discountBlock: !!document.querySelector('.payment-option.discount-code, [data-role="opc-discount"]'),
    bodyHasDiscount: /Apply Discount Code/i.test(document.body.innerText),
  };
});
console.log(`checkout reached               ${ok(co.shippingTitle !== null)}  ("${co.shippingTitle}")`);
console.log(`shipping icon blue             ${ok(co.stepIconColour === 'rgb(29, 78, 216)')}  (${co.stepIconColour})`);
console.log(`address field rhythm 16px      ${ok(co.fieldGap === '16px')}  (${co.fieldGap})`);
console.log(`discount code gone (step 1)    ${ok(!co.discountBlock && !co.bodyHasDiscount)}`);

await browser.close();
