/**
 * Walks a guest through shipping into the PAYMENT step and checks the QA cycle 1
 * findings that only exist there. Stops before Place Order — this is a live
 * store.
 */
import puppeteer from '/home/ubuntu/zoonze-uitests/node_modules/puppeteer-core/lib/esm/puppeteer/puppeteer-core.js';
const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';
const BASE = 'https://hub-market.magento2.click';
const ok = (b) => (b ? 'PASS' : 'FAIL');
const wait = (ms) => new Promise((r) => setTimeout(r, ms));

const browser = await puppeteer.launch({
  executablePath: '/usr/bin/google-chrome', headless: 'new',
  args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu'],
});
const page = await browser.newPage();
await page.setUserAgent(UA);
await page.setViewport({ width: 1440, height: 1100 });

await page.goto(`${BASE}/clothes.html`, { waitUntil: 'networkidle2', timeout: 60000 });
await page.evaluate(() => document.querySelector('button.action.tocart')?.click());
await wait(6000);

await page.goto(`${BASE}/checkout/`, { waitUntil: 'networkidle2', timeout: 90000 });
await wait(9000);

// --- fill the guest shipping form -----------------------------------------
const filled = await page.evaluate(() => {
  const set = (sel, val) => {
    const el = document.querySelector(sel);
    if (!el) return false;
    const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
    setter.call(el, val);
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    el.dispatchEvent(new Event('blur', { bubbles: true }));
    return true;
  };
  const out = {};
  out.email = set('#customer-email', 'qa.smoke.' + Date.now() + '@example.com');
  out.first = set('input[name="firstname"]', 'QA');
  out.last  = set('input[name="lastname"]', 'Smoke');
  out.street= set('input[name="street[0]"]', '1 Test Street');
  out.city  = set('input[name="city"]', 'Cairo');
  out.post  = set('input[name="postcode"]', '11511');
  out.tel   = set('input[name="telephone"]', '01000000000');
  const country = document.querySelector('select[name="country_id"]');
  if (country) {
    country.value = 'EG';
    country.dispatchEvent(new Event('change', { bubbles: true }));
    out.country = true;
  }
  return out;
});
console.log('filled:', JSON.stringify(filled));
await wait(9000);

//  Region is a REQUIRED select for Egypt and is populated only after the country
//  change lands, so it has to be set in a second pass. Leaving it empty is what
//  silently blocked "Continue to Payment" on the first version of this walk —
//  no console error, no visible message, just a button that does nothing.
const region = await page.evaluate(() => {
  const sel = document.querySelector('select[name="region_id"]');
  if (!sel) return 'absent';
  const opt = [...sel.options].find((o) => o.value && o.value !== '');
  if (!opt) return 'no options';
  sel.value = opt.value;
  sel.dispatchEvent(new Event('change', { bubbles: true }));
  return opt.text.trim();
});
console.log('region:', region);

//  TYPE the phone number, do not inject it.
//
//  The SMS module replaces this control with intlTelInput (class
//  `sms-mobile-number`) and validates it with `intlTelInput("isValidNumber")`,
//  which reads the plugin's own parsed state — not the input's value attribute.
//  Setting .value and firing a change event leaves that state empty, so
//  validation fails, jQuery blocks the submit, and because the message container
//  is not rendered there is NO inline error and NO network request: the button
//  simply does nothing. That is a test artefact, not a storefront bug, and it
//  cost three runs to pin down. Typing makes the plugin's keyup handlers fire.
const telSel = 'input[name="telephone"]';
await page.evaluate((sel) => { const el = document.querySelector(sel); if (el) { el.value = ''; el.focus(); } }, telSel);
await page.type(telSel, '1001234567', { delay: 60 });
await page.evaluate((sel) => document.querySelector(sel)?.blur(), telSel);
await wait(7000);

// pick a shipping method, then continue
await page.evaluate(() => {
  const r = document.querySelector('#checkout-shipping-method-load input[type="radio"]');
  if (r) { r.click(); }
});
await wait(5000);
//  Click the real button through the browser (a synthesised .click() inside
//  page.evaluate does not always reach knockout's click binding), then wait for
//  the payment step to actually become visible rather than for a fixed delay.
for (let attempt = 1; attempt <= 3; attempt++) {
  const handle = await page.evaluateHandle(() =>
    [...document.querySelectorAll('button')].find((b) => /continue to payment|^next$/i.test(b.innerText.trim())));
  const el = handle.asElement();
  if (el) { try { await el.click(); } catch { await page.evaluate((b) => b.click(), handle); } }
  try {
    await page.waitForFunction(
      () => { const p = document.querySelector('#payment'); return p && getComputedStyle(p).display !== 'none'; },
      { timeout: 25000 });
    console.log(`payment step opened on attempt ${attempt}`);
    break;
  } catch { console.log(`  attempt ${attempt}: payment step not open yet`); }
}
await wait(4000);

const diag = await page.evaluate(() => ({
  buttons: [...document.querySelectorAll('button')].map(b => b.innerText.trim()).filter(Boolean).slice(0, 12),
  shippingMethods: document.querySelectorAll('#checkout-shipping-method-load input[type=radio]').length,
  methodChecked: !!document.querySelector('#checkout-shipping-method-load input[type=radio]:checked'),
  validationErrors: [...document.querySelectorAll('.mage-error')].map(e => e.innerText.trim()).slice(0, 6),
  paymentDisplay: document.querySelector('#payment') ? getComputedStyle(document.querySelector('#payment')).display : 'absent',
  shippingDisplay: document.querySelector('#shipping') ? getComputedStyle(document.querySelector('#shipping')).display : 'absent',
}));
console.log('DIAG', JSON.stringify(diag, null, 1));

const step2 = await page.evaluate(() => {
  const txt = document.body.innerText;
  const note = document.querySelector('.hm-continue-review__note');
  const noteIcon = document.querySelector('.hm-continue-review__note-icon');
  const payTitle = document.querySelector('.checkout-payment-method .payment-group > .step-title');
  const back = document.querySelector('.hm-continue-review__back');
  const fwd  = document.querySelector('.hm-continue-review__button');
  const cs = (el) => (el ? getComputedStyle(el) : null);
  const w = (el) => (el ? Math.round(el.getBoundingClientRect().width) : null);
  return {
    onPayment: !!document.querySelector('#payment') &&
               getComputedStyle(document.querySelector('#payment')).display !== 'none',
    discountText: /apply discount code|discount code/i.test(txt),
    discountEl: !!document.querySelector('.payment-option.discount-code'),
    noteBg: cs(note)?.backgroundColor ?? null,
    noteIconColour: cs(noteIcon)?.color ?? null,
    payTitleIcon: payTitle ? getComputedStyle(payTitle, '::before').backgroundImage.slice(0, 60) : null,
    stepTitles: [...document.querySelectorAll('.step-title')].map(t => ({
      cls: t.className, parent: t.parentElement?.className?.slice(0,60), txt: t.innerText.trim().slice(0,30)
    })),
    backW: w(back), fwdW: w(fwd),
    fwdBg: cs(fwd)?.backgroundColor ?? null,
    fwdColour: cs(fwd)?.color ?? null,
  };
});
step2.stepTitles.forEach((t,i) => console.log(`  TITLE[${i}] cls="${t.cls}" parent="${t.parent}" txt="${t.txt}"`));
console.log(`reached payment step           ${ok(step2.onPayment)}`);
console.log(`discount code removed          ${ok(!step2.discountText && !step2.discountEl)}  (text=${step2.discountText} el=${step2.discountEl})`);
console.log(`security notice light blue     ${ok(step2.noteBg === 'rgb(234, 240, 253)')}  (${step2.noteBg})`);
console.log(`security lock icon blue        ${ok(step2.noteIconColour === 'rgb(29, 78, 216)')}  (${step2.noteIconColour})`);
console.log(`payment card icon present      ${ok(!!step2.payTitleIcon && step2.payTitleIcon !== 'none')}  (${step2.payTitleIcon})`);
console.log(`Back / Review equal width      ${ok(step2.backW && step2.fwdW && Math.abs(step2.backW - step2.fwdW) <= 2)}  (${step2.backW} vs ${step2.fwdW})`);
console.log(`Review Order blue + white      ${ok(step2.fwdBg === 'rgb(29, 78, 216)' && step2.fwdColour === 'rgb(255, 255, 255)')}  (${step2.fwdBg} / ${step2.fwdColour})`);

await browser.close();
