/*
 * Hub Market — design parity harness (browser side)
 * ---------------------------------------------------------------------------
 * Paste this into the Chrome MCP `javascript_tool` on BOTH pages:
 *   reference : https://doze-coyote-58038022.figma.site/<page>
 *   target    : https://hub-market.magento2.click/<page>
 *
 * It returns a JSON census of what the page ACTUALLY renders — not what the
 * stylesheet claims. Comparing the two censuses is what catches drift; the two
 * DOMs are unrelated, so element-by-element diffing is meaningless and a
 * census is not.
 *
 * Note: the storefront 403s from the application server, so this must run from
 * a developer browser. That is the only supported verification channel.
 *
 * Output: { meta, colours, fonts, sizes, radii, contrast, roles }
 */
(() => {
  const MAX_NODES = 6000;

  // --- helpers -------------------------------------------------------------
  const parseRGB = (s) => {
    const m = /rgba?\(([^)]+)\)/.exec(s || '');
    if (!m) return null;
    const p = m[1].split(',').map((x) => parseFloat(x));
    return { r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1 };
  };

  const lum = ({ r, g, b }) => {
    const f = (v) => {
      v /= 255;
      return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    };
    return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
  };

  const ratio = (a, b) => {
    const x = lum(a), y = lum(b);
    return +(((Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05)).toFixed(2));
  };

  const hex = (c) =>
    c ? '#' + [c.r, c.g, c.b].map((v) => Math.round(v).toString(16).padStart(2, '0')).join('') : null;

  // Walk up for the first non-transparent background. Approximate — it ignores
  // images and gradients, which are reported separately so they are not silently
  // scored as passing.
  const effectiveBg = (el) => {
    let n = el;
    while (n && n !== document.documentElement) {
      const cs = getComputedStyle(n);
      if (cs.backgroundImage && cs.backgroundImage !== 'none') return { over: 'image' };
      const c = parseRGB(cs.backgroundColor);
      if (c && c.a > 0.95) return { colour: c };
      n = n.parentElement;
    }
    return { colour: { r: 255, g: 255, b: 255, a: 1 } };
  };

  const bump = (map, key) => { if (key) map[key] = (map[key] || 0) + 1; };

  // --- census --------------------------------------------------------------
  const colours = {}, bgs = {}, fonts = {}, sizes = {}, weights = {}, radii = {},
        tracking = {}, transforms = {};
  const contrastFails = [];
  let nodes = 0, textNodes = 0, gradients = 0;

  const all = document.querySelectorAll('body *');
  for (const el of all) {
    if (nodes++ > MAX_NODES) break;
    const cs = getComputedStyle(el);
    if (cs.display === 'none' || cs.visibility === 'hidden') continue;
    const rect = el.getBoundingClientRect();
    if (!rect.width || !rect.height) continue;
    // Visually-hidden text (skip links, sr-only labels) is clipped, not hidden,
    // so it still has a box and non-'hidden' visibility. Counting it inflated
    // the contrast failures — the Phase I sweep reported a 1.07:1 "failure" on
    // the search field's sr-only label, which no sighted user can see.
    if (cs.clipPath === 'inset(50%)' || cs.clip === 'rect(0px, 0px, 0px, 0px)') continue;
    // The OTHER visually-hidden idiom: width:1px;height:1px;overflow:hidden with no
    // clip at all. Its rect is 1x1, so the zero-size check above lets it through and
    // the clip check does not match. Phase M measured this reporting the newsletter
    // signup label as a 1.07:1 failure on EVERY page — dark text on the navy footer
    // that no sighted user can see. Screen readers take such text from the
    // accessibility tree, where colour is irrelevant.
    if (rect.width <= 2 && rect.height <= 2) continue;

    bump(colours, cs.color);
    bump(bgs, cs.backgroundColor !== 'rgba(0, 0, 0, 0)' ? cs.backgroundColor : null);
    bump(fonts, (cs.fontFamily || '').split(',')[0].replace(/['"]/g, '').trim());
    bump(sizes, cs.fontSize);
    bump(weights, cs.fontWeight);
    if (cs.borderRadius && cs.borderRadius !== '0px') bump(radii, cs.borderRadius);
    if (cs.letterSpacing && cs.letterSpacing !== 'normal') bump(tracking, cs.letterSpacing);
    if (cs.textTransform && cs.textTransform !== 'none') bump(transforms, cs.textTransform);
    if (cs.backgroundImage && cs.backgroundImage.includes('gradient')) gradients++;

    // contrast, only for elements holding their own visible text
    const own = [...el.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim().length > 1);
    if (!own) continue;
    textNodes++;
    const fg = parseRGB(cs.color);
    const bg = effectiveBg(el);
    if (!fg || !bg.colour) continue;
    const r = ratio(fg, bg.colour);
    const px = parseFloat(cs.fontSize);
    const bold = parseInt(cs.fontWeight, 10) >= 700;
    const large = px >= 24 || (px >= 18.66 && bold);
    const need = large ? 3 : 4.5;
    if (r < need) {
      contrastFails.push({
        ratio: r, need,
        fg: hex(fg), bg: hex(bg.colour),
        px, weight: cs.fontWeight,
        text: (el.textContent || '').trim().slice(0, 60),
        sel: el.tagName.toLowerCase() + (el.className && typeof el.className === 'string'
          ? '.' + el.className.trim().split(/\s+/).slice(0, 2).join('.') : ''),
      });
    }
  }

  // --- role probes ---------------------------------------------------------
  // Same visual role, different selector per side. Extend as pages are built.
  const ROLES = {
    price:        ['.price', '[class*="price"]'],
    productTitle: ['.product-item-name', '.product-item-link', 'h3'],
    primaryBtn:   ['.action.primary', 'button[class*="primary"]', '.tocart'],
    heading1:     ['h1'],
    bodyText:     ['p'],
    input:        ['input[type="text"]', 'input[type="search"]', 'input:not([type="hidden"])'],
    link:         ['a'],
  };
  const roles = {};
  for (const [role, sels] of Object.entries(ROLES)) {
    let el = null;
    for (const s of sels) { el = document.querySelector(s); if (el) break; }
    if (!el) { roles[role] = null; continue; }
    const cs = getComputedStyle(el);
    roles[role] = {
      fontFamily: (cs.fontFamily || '').split(',')[0].replace(/['"]/g, '').trim(),
      fontSize: cs.fontSize, fontWeight: cs.fontWeight, lineHeight: cs.lineHeight,
      color: hex(parseRGB(cs.color)), background: cs.backgroundColor,
      borderRadius: cs.borderRadius, padding: cs.padding,
      letterSpacing: cs.letterSpacing, textTransform: cs.textTransform,
    };
  }

  // Keep the payload small. The MCP javascript_tool truncates large returns,
  // and a truncated census silently reads as "fewer colours" — which would make
  // a failing page look like a passing one. Compact by default; raise the caps
  // only when inspecting one page in isolation.
  const top = (o, n = 12) =>
    Object.fromEntries(Object.entries(o).sort((a, b) => b[1] - a[1]).slice(0, n));

  //  Colours are normalised to hex here rather than in compare.py, because the
  //  browser reports a mix of rgb()/oklch()/oklab() and the raw strings are far
  //  longer than the hex they collapse to.
  const asHex = (o) => {
    const out = {};
    for (const [k, n] of Object.entries(o)) {
      const c = parseRGB(k);
      const key = c ? (c.a === 0 ? null : hex(c)) : k.slice(0, 24);
      if (key) out[key] = (out[key] || 0) + n;
    }
    return out;
  };

  return {
    meta: {
      url: location.href,
      dir: document.documentElement.dir || getComputedStyle(document.body).direction,
      lang: document.documentElement.lang || null,
      viewport: innerWidth + 'x' + innerHeight,
      scrollWidth: document.documentElement.scrollWidth,
      overflows: document.documentElement.scrollWidth > document.documentElement.clientWidth,
      nodesScanned: nodes, textNodes, gradients,
    },
    // accessibility signals the Figma build failed on — cheap to re-check
    a11y: {
      ariaLabels: document.querySelectorAll('[aria-label]').length,
      ariaLive: document.querySelectorAll('[aria-live]').length,
      labelsFor: document.querySelectorAll('label[for]').length,
      imgsNoAlt: document.querySelectorAll('img:not([alt])').length,
      imgsLazy: document.querySelectorAll('img[loading="lazy"]').length,
      imgsTotal: document.querySelectorAll('img').length,
      bdi: document.querySelectorAll('bdi, [dir]').length,
      h1: document.querySelectorAll('h1').length,
      skipLink: !!document.querySelector('a[href^="#"][class*="skip"]'),
    },
    colours: top(asHex(colours), 16), backgrounds: top(asHex(bgs), 12),
    fonts: top(fonts, 8), sizes: top(sizes, 14), weights: top(weights, 6),
    radii: top(radii, 8), tracking: top(tracking, 6), transforms: top(transforms, 4),
    contrast: {
      failures: contrastFails.length,
      worst: contrastFails
        .sort((a, b) => a.ratio - b.ratio)
        .slice(0, 8)
        .map((f) => ({ r: f.ratio, need: f.need, fg: f.fg, bg: f.bg, px: f.px, sel: f.sel })),
    },
    roles,
  };
})();
