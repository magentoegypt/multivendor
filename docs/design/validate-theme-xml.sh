#!/usr/bin/env bash
# Validate every XML file we own — the Hub Market theme AND the MagentoEgypt modules.
#
# Magento silently IGNORES a malformed config or layout file — no log entry, no
# error, the file simply never applies. A stray `--` inside an XML comment is
# enough, and that is easy to write when drawing a `---` underline in a doc block.
#
# This has now bitten three times. The third time was app/code/MagentoEgypt/
# SmsExtend/etc/frontend/sections.xml, which this script did not cover because it
# only scanned the theme — so the scope is now both. Run before every deploy.
set -u
fail=0
roots=(
  "app/design/frontend/MagentoEgypt/hub-market"
  "app/code/MagentoEgypt"
)
count=0
for root in "${roots[@]}"; do
  [ -d "$root" ] || continue
  while IFS= read -r f; do
    count=$((count + 1))
    if ! python3 -c "import xml.dom.minidom,sys; xml.dom.minidom.parse('$f')" 2>/dev/null; then
      echo "INVALID: $f"
      python3 -c "import xml.dom.minidom; xml.dom.minidom.parse('$f')" 2>&1 | tail -1
      fail=1
    fi
  done < <(find "$root" -name '*.xml')
done
[ $fail -eq 0 ] && echo "all $count XML files well-formed (theme + MagentoEgypt modules)"
exit $fail
