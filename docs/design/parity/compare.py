#!/usr/bin/env python3
"""
Hub Market — design parity comparison.

Usage:
    python3 compare.py reference.json target.json [--page home] [--md]

Takes two censuses produced by harness.js (reference = the Figma Make build,
target = the Magento storefront) and reports the drift between them.

It deliberately does NOT diff element-by-element: the two DOMs are unrelated.
It compares what each page actually renders — the colour set, the type scale,
the role probes and the accessibility signals.

Exit code is 1 if any gate fails, so this can be wired into a phase check.
"""
import json
import sys
import re
from collections import OrderedDict

# The approved token palette. Anything a page renders that is not in here (and
# not a neutral/alpha) is drift. Kept in sync with docs/design/tokens.css.
PALETTE = {
    '#0f2144': 'primary', '#16305f': 'primary-hover',
    '#f26522': 'accent', '#e85d18': 'accent-hover',
    '#c2410c': 'accent-strong', '#9a3412': 'accent-strong-hover',
    '#fff4ef': 'accent-subtle',
    '#ffffff': 'neutral-0', '#fafbfd': 'neutral-50', '#f5f7fa': 'neutral-100',
    '#e8ecf3': 'neutral-200', '#cbd3e2': 'neutral-300', '#9aa5bb': 'neutral-400',
    '#6b7280': 'neutral-500', '#535d70': 'neutral-600', '#3d4759': 'neutral-700',
    '#1a1a2e': 'neutral-900', '#7d879c': 'border-strong',
    '#b3261e': 'discount', '#d97706': 'rating-star', '#0f7b3f': 'stock-in',
    '#b45309': 'stock-low', '#1d4ed8': 'verified', '#c0392b': 'danger',
}
ALLOWED_FONTS = {'DM Sans', 'Playfair Display', 'IBM Plex Sans Arabic'}


def rgb_to_hex(s):
    m = re.match(r'rgba?\(([^)]+)\)', s or '')
    if not m:
        return s
    p = [float(x) for x in m.group(1).split(',')]
    if len(p) > 3 and p[3] == 0:
        return None
    return '#%02x%02x%02x' % (int(p[0]), int(p[1]), int(p[2]))


def census_colours(d):
    out = {}
    for key in ('colours', 'backgrounds'):
        for raw, n in (d.get(key) or {}).items():
            h = rgb_to_hex(raw)
            if h:
                out[h] = out.get(h, 0) + n
    return out


def load(p):
    with open(p) as f:
        return json.load(f)


def main():
    args = [a for a in sys.argv[1:] if not a.startswith('--')]
    md = '--md' in sys.argv
    if len(args) < 2:
        print(__doc__)
        return 2
    ref, tgt = load(args[0]), load(args[1])
    fails = []
    L = []

    def head(t):
        L.append(f"\n## {t}\n" if md else f"\n=== {t} ===")

    def row(a, b='', c=''):
        L.append(f"| {a} | {b} | {c} |" if md else f"  {a:<44} {b:<22} {c}")

    L.append(f"# Parity report\n" if md else "PARITY REPORT")
    row('', 'reference', 'target')
    if md:
        L.append('|---|---|---|')
    row('url', ref['meta']['url'][:28], tgt['meta']['url'][:28])
    row('direction', ref['meta']['dir'], tgt['meta']['dir'])
    row('viewport', ref['meta']['viewport'], tgt['meta']['viewport'])

    # --- palette drift -------------------------------------------------------
    head('Off-palette colours rendered by the TARGET')
    tc = census_colours(tgt)
    drift = {h: n for h, n in tc.items() if h not in PALETTE}
    if drift:
        for h, n in sorted(drift.items(), key=lambda x: -x[1])[:25]:
            row(h, f'{n}x', 'OFF-PALETTE')
        fails.append(f'{len(drift)} off-palette colours in target')
    else:
        row('none', '', 'PASS')

    head('Palette coverage')
    used = [PALETTE[h] for h in tc if h in PALETTE]
    row('token colours rendered', str(len(set(used))), '')
    row('total distinct colours', str(len(tc)),
        'PASS' if len(tc) <= len(PALETTE) else 'high')

    # --- fonts ---------------------------------------------------------------
    head('Fonts')
    for side, d in (('reference', ref), ('target', tgt)):
        for f, n in (d.get('fonts') or {}).items():
            flag = '' if f in ALLOWED_FONTS else 'UNEXPECTED'
            if flag and side == 'target':
                fails.append(f'unexpected font in target: {f}')
            row(f'{side}: {f}', f'{n}x', flag)

    # --- type scale ----------------------------------------------------------
    head('Type sizes below the 14px floor (target)')
    small = {s: n for s, n in (tgt.get('sizes') or {}).items()
             if s.endswith('px') and float(s[:-2]) < 14}
    if small:
        for s, n in sorted(small.items(), key=lambda x: float(x[0][:-2])):
            row(s, f'{n}x', 'BELOW FLOOR')
        fails.append(f'{sum(small.values())} elements below 14px in target')
    else:
        row('none', '', 'PASS')

    # --- contrast ------------------------------------------------------------
    head('Contrast')
    rf = ref.get('contrast', {}).get('failures', 0)
    tf = tgt.get('contrast', {}).get('failures', 0)
    row('AA failures', str(rf), str(tf) + ('  PASS' if tf == 0 else '  FAIL'))
    if tf:
        fails.append(f'{tf} contrast failures in target')
        for f in tgt['contrast']['worst'][:10]:
            row(f"  {f['sel'][:38]}", f"{f['ratio']}:1 (need {f['need']})",
                f"{f['fg']} on {f['bg']}")

    # --- accessibility -------------------------------------------------------
    head('Accessibility signals')
    ra, ta = ref.get('a11y', {}), tgt.get('a11y', {})
    gates = {
        'ariaLabels': ('>0', lambda v: v > 0),
        'labelsFor': ('>0', lambda v: v > 0),
        'h1': ('==1', lambda v: v == 1),
        'imgsNoAlt': ('==0', lambda v: v == 0),
        'skipLink': ('True', lambda v: bool(v)),
    }
    for k in sorted(set(ra) | set(ta)):
        want, ok = gates.get(k, ('', None))
        tv = ta.get(k)
        verdict = '' if ok is None else ('PASS' if ok(tv) else f'FAIL (want {want})')
        if ok is not None and not ok(tv):
            fails.append(f'a11y {k}={tv} (want {want})')
        row(k, f'{ra.get(k)} / {tv}', verdict)

    # --- role probes ---------------------------------------------------------
    head('Role probes (reference vs target)')
    for role in sorted(set(ref.get('roles', {})) | set(tgt.get('roles', {}))):
        r, t = (ref['roles'] or {}).get(role), (tgt['roles'] or {}).get(role)
        if not r or not t:
            row(role, 'missing on ' + ('reference' if not r else 'target'), '')
            continue
        for prop in ('fontFamily', 'fontSize', 'fontWeight', 'color', 'borderRadius'):
            rv, tv = r.get(prop), t.get(prop)
            if rv != tv:
                row(f'{role}.{prop}', f'{rv} -> {tv}', 'DIFF')

    head('Result')
    if fails:
        for f in fails:
            row('FAIL', f, '')
    else:
        row('all gates passed', '', 'PASS')

    print('\n'.join(L))
    return 1 if fails else 0


if __name__ == '__main__':
    sys.exit(main())
