#!/usr/bin/env bash
# Hub Market customer app GraphQL smoke test (P2, the HubApp modules).
#
#   HM_ENDPOINT=https://<staging>/graphql [HM_EMAIL=.. HM_PASSWORD=..] dev/tools/hubapp/smoke.sh
#
# Public reads go as GET with the Store header and no Authorization header,
# exactly as the app sends them, and each line says whether the response came
# back cacheable (Cache-Control: public) and, with Varnish, HIT or MISS.
#
# THE LIVE STORE (hub-market.magento2.click) GETS GET REQUESTS ONLY. The
# script refuses to POST anything there — no login, no cart, no device, no
# store credit, no return — and stops after the public steps. A document
# containing a mutation is never sent as GET either.
#
# Optional:
#   HM_SKIP=stores,store,app-home-stores   leave out steps whose module is not deployed
#   HM_STORE_CODE=loly                     seller for S5 (default loly)
#   HM_BUNDLE_SKU=.. HM_BUNDLE_SELECTIONS='[{"selection_uid":"..."}]'   S9a quote (GET) and S9 add-to-cart
#   HM_CREDIT_AMOUNT=100                   S12 buy credit: an amount hmStoreCredit.top_up offers;
#                                          the line is taken out of the cart again
#   HM_STORE=en                            Store header of the POST steps
#   HM_GUEST_NUMBER=.. HM_GUEST_EMAIL=.. HM_GUEST_LASTNAME=..   a guest order for S14's guestOrder step
#
# WhatsApp sign-in (S10) is run by hand with QA's own number: every send is a
# real, paid WhatsApp message (smoke/whatsapp-send.graphql).
#
# Needs bash, curl and jq (1.6+).
set -euo pipefail

EP=${HM_ENDPOINT:?set HM_ENDPOINT, e.g. https://staging.example.com/graphql}
D=$(cd "$(dirname "$0")" && pwd)/smoke
T=$(mktemp -d)
trap 'rm -rf "$T"' EXIT
UA=HubMarketApp-smoke
SKIP=",${HM_SKIP:-},"
FAILS=0
LIVE=0
case "$EP" in
  *hub-market.magento2.click*) LIVE=1 ;;
esac

skipped() { [[ $SKIP == *",$1,"* ]]; }

# get <store> <document> [variables-json] -> body in $T/b
get() {
  local store=$1 doc=$2 vars=${3:-}
  if skipped "$doc"; then echo "skip $doc/$store"; return 0; fi
  if grep -qiE '^[[:space:]]*mutation' "$D/$doc.graphql"; then
    echo "REFUSED $doc: a mutation is never sent as GET"
    exit 2
  fi
  local args=(-sS -G "$EP" -A "$UA" -H "Store: $store" -D "$T/h" -o "$T/b"
              --data-urlencode "query@$D/$doc.graphql")
  if [[ -n $vars ]]; then args+=(--data-urlencode "variables=$vars"); fi
  if ! curl "${args[@]}"; then
    echo "FAIL $doc/$store: request failed"; FAILS=$((FAILS + 1)); return 0
  fi
  if ! jq -e '(.errors == null) and (.data != null)' "$T/b" >/dev/null 2>&1; then
    echo "FAIL $doc/$store"
    jq '.errors // .' "$T/b" 2>/dev/null || head -c 600 "$T/b"
    echo
    FAILS=$((FAILS + 1))
    return 0
  fi
  local cache="NOT cacheable" debug
  if grep -qi '^cache-control:.*public' "$T/h"; then cache="cacheable"; fi
  debug=$(grep -i '^x-magento-cache-debug:' "$T/h" | tr -d '\r' | awk '{print $2}' || true)
  echo "ok   $doc/$store ($cache${debug:+, $debug})"
}

# post <document> [variables-json] -> body in $T/m. Never against the live store.
post() {
  local doc=$1 vars=${2:-'{}'}
  if (( LIVE )); then
    echo "REFUSED $doc: nothing is POSTed to the live store"
    exit 2
  fi
  if skipped "$doc"; then echo "skip $doc"; return 0; fi
  jq -n --rawfile q "$D/$doc.graphql" --argjson v "$vars" '{query: $q, variables: $v}' >"$T/req"
  local args=(-sS "$EP" -A "$UA" -H "Store: ${HM_STORE:-en}" -H 'Content-Type: application/json'
              -o "$T/m" --data @"$T/req")
  if [[ -n ${TOKEN:-} ]]; then args+=(-H "Authorization: Bearer $TOKEN"); fi
  if ! curl "${args[@]}"; then
    echo "FAIL $doc: request failed"; FAILS=$((FAILS + 1)); return 0
  fi
  if ! jq -e '(.errors == null) and (.data != null)' "$T/m" >/dev/null 2>&1; then
    echo "FAIL $doc"
    jq '.errors // .' "$T/m" 2>/dev/null || head -c 600 "$T/m"
    echo
    FAILS=$((FAILS + 1))
    return 0
  fi
  echo "ok   $doc"
}

if (( LIVE )); then
  echo "== $EP is the LIVE store: public GET steps only"
else
  echo "== $EP"
fi

# S1-S4: public reads, both store views
# check_features <store>: hmAppConfig.features of the app-config body in $T/b is not empty.
# The app treats a flag it is not sent as off, so an empty list hides returns, store
# credit, WhatsApp sign-in and push (HubApp etc/config.xml ships the four on).
check_features() {
  local store=$1 list
  if skipped app-config; then return 0; fi
  # No hmAppConfig at all: get has already reported the failure.
  if ! jq -e '.data.hmAppConfig' "$T/b" >/dev/null 2>&1; then return 0; fi
  list=$(jq -r '[.data.hmAppConfig.features[]? | "\(.code)=\(if .enabled then "on" else "off" end)"] | join(" ")' \
    "$T/b" 2>/dev/null || true)
  if [[ -n $list ]]; then
    echo "ok   S1 features/$store: $list"
  else
    echo "FAIL S1 features/$store: hmAppConfig.features is empty, the app would hide every flagged feature"
    FAILS=$((FAILS + 1))
  fi
}

# check_capabilities <store>: hmAppConfig.capabilities of the app-config body in $T/b.
# The app asks for a satellite's fields (hm_seller on listing cards, the store page's
# reviews, contact and chips) only when its code is listed here.
check_capabilities() {
  local store=$1 list
  if skipped app-config; then return 0; fi
  if ! jq -e '.data.hmAppConfig' "$T/b" >/dev/null 2>&1; then return 0; fi
  list=$(jq -r '[.data.hmAppConfig.capabilities[]?] | join(" ")' "$T/b" 2>/dev/null || true)
  if [[ -n $list ]]; then
    echo "ok   S1 capabilities/$store: $list"
  else
    echo "WARN S1 capabilities/$store: none listed, the app sends no satellite fields"
  fi
}

# report_config <store>: the free-shipping threshold and the Algolia search layout of the
# app-config body in $T/b (informational: either may be absent on purpose).
report_config() {
  local store=$1 line
  if skipped app-config; then return 0; fi
  if ! jq -e '.data.hmAppConfig' "$T/b" >/dev/null 2>&1; then return 0; fi
  line=$(jq -r '.data.hmAppConfig | "free shipping over \(.shipping.free_over // {} | if .value then "\(.value) \(.currency)" else "none" end)"
    + "; algolia " + (if .algolia then "\(.algolia.facets | length) facets, \(.algolia.sorts | length) sorts, suggestions \(.algolia.suggestion_index // "off")" else "off" end)' \
    "$T/b" 2>/dev/null || true)
  echo "     S1 config/$store: ${line:-unreadable}"
}

for s in en ar; do
  get "$s" app-config
  check_features "$s"
  check_capabilities "$s"
  report_config "$s"
  get "$s" app-config '{"platform":"ANDROID"}'
  get "$s" app-home '{"audience":"GUEST"}'
  get "$s" app-home '{"audience":"CUSTOMER"}'
  get "$s" app-home-stores '{"audience":"GUEST"}'
  get "$s" deals
  get "$s" deals '{"sort":"PRICE_ASC","min_discount_percent":10}'
  get "$s" best-sellers
  get "$s" bundle-deals
  get "$s" brands
  get "$s" stores '{"sort":"TOP_RATED"}'

  # S4b: the Stores chips. A chip's count is the number of sellers its list shows.
  if ! skipped store-categories; then
    get "$s" store-categories
    chip=$(jq -r '.data.hmStoreCategories.items[0] | "\(.id) \(.count)"' "$T/b" 2>/dev/null || true)
    if [[ -n $chip && $chip != "null null" ]]; then
      read -r cid ccount <<<"$chip"
      get "$s" stores "{\"categoryId\":$cid}"
      listed=$(jq -r '.data.hmStores.total_count // empty' "$T/b" 2>/dev/null || true)
      if [[ $listed == "$ccount" ]]; then
        echo "ok   S4b chips/$s: category $cid holds $ccount sellers = its hmStores total"
      else
        echo "FAIL S4b chips/$s: category $cid chip says $ccount, hmStores category_id total $listed"
        FAILS=$((FAILS + 1))
      fi
    else
      echo "WARN S4b chips/$s: no category holds a seller"
    fi
  fi
done

# S5: a store page, and its products through the vendor_id (match) filter
if ! skipped store; then
  get en store "{\"code\":\"${HM_STORE_CODE:-loly}\"}"
  count=$(jq -r '.data.hmStore.card.product_count // empty' "$T/b" 2>/dev/null || true)
  id=$(jq -r '.data.hmStore.card.vendor_entity_id // empty' "$T/b" 2>/dev/null || true)
  reviews=$(jq -r '.data.hmStore.card.review_count // empty' "$T/b" 2>/dev/null || true)
  if [[ -n $id ]]; then
    get en vendor-products "{\"id\":\"$id\"}"
    total=$(jq -r '.data.products.total_count // empty' "$T/b" 2>/dev/null || true)
    if [[ $total == "$count" ]]; then
      echo "ok   S5 vendor_id filter: $total products = card product_count"
    else
      echo "FAIL S5 vendor_id filter: products total_count $total, card product_count $count"
      FAILS=$((FAILS + 1))
    fi
  fi

  # S5b: the store page's Reviews tab. Its summary is the card's rating (the same rule);
  # the list holds only the reviews written in this store view, so it may be shorter.
  if [[ -n $id ]] && ! skipped store-reviews; then
    for s in en ar; do
      get "$s" store-reviews "{\"code\":\"${HM_STORE_CODE:-loly}\"}"
      summary=$(jq -r '.data.hmStoreReviews.summary.review_count // empty' "$T/b" 2>/dev/null || true)
      listed=$(jq -r '.data.hmStoreReviews.total_count // empty' "$T/b" 2>/dev/null || true)
      if [[ -n $summary && $summary == "$reviews" ]]; then
        echo "ok   S5b reviews/$s: summary $summary = card review_count, $listed shown in this store view"
      else
        echo "FAIL S5b reviews/$s: summary review_count '$summary', card review_count '$reviews'"
        FAILS=$((FAILS + 1))
      fi
    done
  fi
fi

# S6: a brand's products through the mgs_brand filter, against its product_count
get en brands '{"pageSize":50,"with_products":true}'
option=$(jq -r '[.data.hmBrands.items[]? | select(.option_id > 0)][0].option_id // empty' "$T/b" 2>/dev/null || true)
counted=$(jq -r "[.data.hmBrands.items[]? | select(.option_id == ${option:-0})][0].product_count // empty" "$T/b" 2>/dev/null || true)
if [[ -n $option ]]; then
  get en brand-products "{\"option\":\"$option\"}"
  total=$(jq -r '.data.products.total_count // 0' "$T/b" 2>/dev/null || echo 0)
  if (( total > 0 )); then
    echo "ok   S6 mgs_brand filter: option $option has $total products (product_count ${counted:-?})"
    if [[ -n $counted && $counted != "$total" ]]; then
      echo "WARN S6 product_count $counted differs from the brand page's $total (search visibility or stock)"
    fi
  else
    echo "WARN S6 mgs_brand filter: option $option returned no products (check the brand has products)"
  fi
else
  echo "WARN S6: no brand with products to test"
fi

# S9a: a bundle package priced the way the cart prices it (public GET), for the S9 bundle
if [[ -n ${HM_BUNDLE_SKU:-} && -n ${HM_BUNDLE_SELECTIONS:-} ]]; then
  get en bundle-quote "$(jq -cn --arg s "$HM_BUNDLE_SKU" --argjson sel "$HM_BUNDLE_SELECTIONS" \
    '{sku: $s, quantity: 1, selections: $sel}')"
  quoted=$(jq -r '.data.hmBundleQuote | if .available then "\(.price.value) \(.price.currency)" else "unavailable: \(.message)" end' \
    "$T/b" 2>/dev/null || true)
  echo "     S9a quote: ${quoted:-none}"
else
  echo "skip bundle-quote (HM_BUNDLE_SKU / HM_BUNDLE_SELECTIONS not set)"
fi

# S7: other sellers of a product (Vnecoms "select and sell") and an offer's own page,
# which the app reads with route because offers are left out of product search.
# HM_OFFER_KEY: a main product with at least one copy (default joust-duffle-bag).
if ! skipped product-offers; then
  key=${HM_OFFER_KEY:-joust-duffle-bag}
  get en product-offers "{\"key\":\"$key\"}"
  n=$(jq -r '.data.products.items[0].hm_offer_count // empty' "$T/b" 2>/dev/null || true)
  listed=$(jq -r '.data.products.items[0].hm_other_offers | length' "$T/b" 2>/dev/null || true)
  main=$(jq -r '.data.products.items[0].sku // empty' "$T/b" 2>/dev/null || true)
  offer=$(jq -r '.data.products.items[0].hm_other_offers[0].url_key // empty' "$T/b" 2>/dev/null || true)
  if [[ -n $n && $n == "$listed" && $n != 0 ]]; then
    echo "ok   S7 $key: hm_offer_count $n = offers listed"
  elif [[ -n $n && $n == "$listed" ]]; then
    echo "WARN S7 $key: no other sellers (set HM_OFFER_KEY to a product with a select-and-sell copy)"
  else
    echo "FAIL S7 $key: hm_offer_count ${n:-none}, offers listed ${listed:-none}"
    FAILS=$((FAILS + 1))
  fi
  if [[ -n $offer ]]; then
    get en product-offer-page "{\"url\":\"$offer.html\"}"
    back=$(jq -r --arg s "$main" '[.data.route.hm_other_offers[]?.sku] | index($s) != null' "$T/b" 2>/dev/null || true)
    if [[ $back == "true" ]]; then
      echo "ok   S7 $offer.html: route reads the offer, and $key ($main) is among its other sellers"
    else
      echo "FAIL S7 $offer.html: route found no product, or $main is not among its other sellers"
      FAILS=$((FAILS + 1))
    fi
  fi
fi

if (( LIVE )); then
  echo
  echo "== live store: login, cart, device, credit and return steps are not run here"
  if (( FAILS )); then echo "$FAILS step(s) failed"; exit 1; fi
  echo "all public steps passed"
  exit 0
fi

# Token steps: staging only
if [[ -z ${HM_EMAIL:-} || -z ${HM_PASSWORD:-} ]]; then
  echo "== HM_EMAIL / HM_PASSWORD not set: token steps skipped"
else
  post login "$(jq -n --arg e "$HM_EMAIL" --arg p "$HM_PASSWORD" '{email: $e, password: $p}')"
  TOKEN=$(jq -r '.data.generateCustomerToken.token // empty' "$T/m" 2>/dev/null || true)
  if [[ -z $TOKEN ]]; then
    echo "FAIL login: no token, token steps skipped"
    FAILS=$((FAILS + 1))
  else
    post cart-id
    CART=$(jq -r '.data.customerCart.id // empty' "$T/m" 2>/dev/null || true)

    # S9: new_bundle with per-selection choices, then the cart by seller
    if [[ -n ${HM_BUNDLE_SKU:-} && -n ${HM_BUNDLE_SELECTIONS:-} && -n $CART ]]; then
      post cart-new-bundle "$(jq -n --arg c "$CART" --arg s "$HM_BUNDLE_SKU" \
        --argjson sel "$HM_BUNDLE_SELECTIONS" '{cart: $c, sku: $s, selections: $sel}')"
    else
      echo "skip cart-new-bundle (HM_BUNDLE_SKU / HM_BUNDLE_SELECTIONS not set)"
    fi
    if [[ -n $CART ]]; then post cart-items "{\"cart\":\"$CART\"}"; fi

    # S11: push device register / unregister (then check the DB row by hand)
    DEVICE="hmsmoke_$(date +%s)_abcdefghijklmnopqrstuvwxyz"
    post device-register "{\"token\":\"$DEVICE\"}"
    post device-unregister "{\"token\":\"$DEVICE\"}"

    # S12: store credit
    post credit-account
    CREDIT_SKUS=$(jq -r '[.data.hmStoreCredit.top_up // empty | (.sku, .presets[]?.sku)] | unique | join(",")' \
      "$T/m" 2>/dev/null || true)
    if [[ -n $CREDIT_SKUS ]]; then
      echo "ok   S12 top_up: $(jq -c '.data.hmStoreCredit.top_up | {sku, min: .min.value, max: .max.value, presets: [.presets[].credit.value]}' "$T/m")"
    else
      echo "info S12 top_up is null: the store sells no store credit product (Buy Credit page empty)"
    fi
    if [[ -n $CART ]]; then
      post credit-apply "{\"cart\":\"$CART\",\"amount\":1}"
      post credit-remove "{\"cart\":\"$CART\"}"
    fi

    # S12b: buy credit, then take the line out again
    if [[ -n ${HM_CREDIT_AMOUNT:-} && -n $CART && -n $CREDIT_SKUS ]]; then
      post credit-add "{\"cart\":\"$CART\",\"amount\":$HM_CREDIT_AMOUNT}"
      errors=$(jq -r '[.data.hmAddCreditToCart.user_errors[]?.message] | join("; ")' "$T/m" 2>/dev/null || true)
      line=$(jq -r --arg skus "$CREDIT_SKUS" \
        '($skus | split(",")) as $s | [.data.hmAddCreditToCart.cart.items[]? | select(.product.sku as $k | $s | index($k))] | last | .uid // empty' \
        "$T/m" 2>/dev/null || true)
      if [[ -n $errors || -z $line ]]; then
        echo "FAIL S12b credit-add: ${errors:-no credit line in the cart}"
        FAILS=$((FAILS + 1))
      else
        echo "ok   S12b credit line $line"
        post cart-remove-item "{\"cart\":\"$CART\",\"uid\":\"$line\"}"
      fi
    else
      echo "skip credit-add (HM_CREDIT_AMOUNT not set, or no cart or top-up)"
    fi

    # S13: returns (creating one and messaging it are done on a QA order by hand)
    post returns-config
    post returnable-orders
    post returns-list

    # S14: orders split by store; every top-level line sits in exactly one package
    post order-packages
    if ! skipped order-packages && jq -e '.data.customer.orders.items' "$T/m" >/dev/null 2>&1; then
      split=$(jq -r '[.data.customer.orders.items[]
        | select(([.items[]?.id] | sort) != ([.hm_packages[]?.item_uids[]] | sort)) | .number] | join(" ")' "$T/m")
      if [[ -z $split ]]; then
        echo "ok   S14 packages hold every line of every order once"
      else
        echo "FAIL S14 packages do not hold the lines of order(s): $split"
        FAILS=$((FAILS + 1))
      fi
    fi
  fi
fi

# S14 guest: a guest order split by store, asked without a customer token (staging only)
if [[ -n ${HM_GUEST_NUMBER:-} && -n ${HM_GUEST_EMAIL:-} && -n ${HM_GUEST_LASTNAME:-} ]]; then
  TOKEN='' post guest-order-packages "$(jq -n --arg n "$HM_GUEST_NUMBER" --arg e "$HM_GUEST_EMAIL" \
    --arg l "$HM_GUEST_LASTNAME" '{number: $n, email: $e, lastname: $l}')"
else
  echo "skip guest-order-packages (HM_GUEST_NUMBER / HM_GUEST_EMAIL / HM_GUEST_LASTNAME not set)"
fi

echo
if (( FAILS )); then echo "$FAILS step(s) failed"; exit 1; fi
echo "all steps passed"
