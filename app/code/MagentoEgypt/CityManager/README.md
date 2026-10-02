# Hub Market City Manager

Deployed target: `hub-market.magento2.click`, source `/var/www/multi.magento2.click` (PHP 8.4 / Magento 2.4.8-p5). Do not deploy to the separate Magento Egypt demo.

## Behavior
- Egypt: Governorate / City; Saudi Arabia: Region / City; USA: State / City.
- UAE: City / Locality; Magento region mapping is internal. Locality is required only when the city has active configured localities (owner approved 2 October 2026).
- English/Arabic dropdowns on standard address forms in frontend, adminhtml and vendors areas; the two Flutter apps consume the same directory.
- Customer, quote, order and vendor models validate managed geography. `cm_city_id` and `cm_locality_id` are persisted; standard city text is `City / Locality` for compatibility with existing documents and APIs.
- Existing unchanged historical addresses are preserved; edited geography must resolve to an active directory entry. Existing order text is not rewritten when an administrator renames a location.
- Stores > City Manager provides add/edit/deactivate, hierarchy validation and an audit trail. The module does not calculate delivery coverage or shipping charges.

## Directory APIs
GET `/citymanager/directory/index?country=EG&level=region` returns `{items: [...]}`. Select a city list with `level=city&region=<Magento region ID>`. UAE uses `level=city` and `level=locality&parent=<City Manager location ID>`.
GET `/rest/en/V1/citymanager/locations` exposes the same read-only directory as an array. Invalid geography is rejected when an address is saved.

## Deployment
Back up database and application configuration. Enable `MagentoEgypt_CityManager`, apply its declarative schema and data patches, seed the directory, and compile DI. Ensure the customer/quote generated AddressExtension classes contain the new fields. Deploy both `address.js` and `address.min.js` to the active frontend/adminhtml/vendors locales. Update Magento's static content version using a value without a trailing newline, then clean relevant caches. Set `citymanager/general/enabled` to `1` only after validating address persistence. The default is off.

The live deployment deliberately preserved unrelated pre-existing schema drift rather than applying a global schema upgrade. Its four data patches include address attribute-set registration and normalization of imported apostrophes. For a normal clean installation, use the standard Magento setup upgrade process after reviewing all pending changes.

Emergency disable: `php8.4 bin/magento config:set citymanager/general/enabled 0` followed by a clean of config/layout/block_html/full_page caches. Retain directory and address columns to preserve data.

## Geographic data
Imported from [Countries States Cities Database](https://github.com/dr5hn/countries-states-cities-database), ODbL 1.0. UAE localities are currently configured for Dubai and Abu Dhabi only. This directory does not certify seller delivery coverage. Administrative edits remain the source of truth.

## Verified scope
See the Hub Market City Manager QA suite in ClickUp: https://app.clickup.com/t/14zb93nvrmu. Backend checks and real customer address saves passed for EG, SA, US and AE, including ZIP+4, UAE locality optionality, and network failure/retry. Do not infer full order, carrier, Odoo, invoice/PDF or installed iOS acceptance from those checks. These require separate recorded results.
