# PetScript Rx Checkout

WooCommerce plugin that requires pet and veterinarian information before checkout on prescription-tagged products, and submits the resulting Rx to PetScript Pharmacy.

Current version: 1.1.2

## Structure

- `petscript-rx-checkout.php` — plugin bootstrap
- `src/` — PSR-4 autoloaded source (`PetScript\RxCheckout\`)
  - `Domain/` — Patient, Clinic, Cart, RxAssignment entities and repositories
  - `Http/Ajax/` — AJAX handlers for the cart modal
  - `WooCommerce/` — cart/checkout hooks, order snapshot, order submission to Pharmacy
  - `Integration/` — Pharmacy API client and payload mapping
  - `Admin/` — settings page (Pharmacy API config, Google Maps key, clinic CSV import, clinic approval queue)
  - `Install/` — DB table installer/migrator
  - `Support/` — Config constants and Settings accessor
- `templates/` — cart panel, modal, and item button markup
- `assets/build/` — panel.js (ES5), checkout-address.js, panel.css
- `vendor/` — Composer autoloader (PSR-4, not authoritative — classmap kept in sync manually)

## Install

Upload the zip via WP Admin → Plugins → Add New → Upload Plugin. Requires WooCommerce and PHP 8.1+.

## Changelog

- 1.1.2 — Stop loading our own Google Maps script on checkout and the cart
  modal. This site also runs other address-autocomplete plugins that load
  maps.googleapis.com/maps/api/js on their own; a second copy re-registers
  its Web Components and throws console errors. checkout-address.js and
  panel.js now check at runtime whether Maps is already loading/loaded
  before adding their own script tag.
- 1.1.1 — DB_VERSION bump to force the clinic table migration
  (vet_first_name/vet_last_name/status columns) to re-run on sites where
  it didn't apply cleanly on first install.
- 1.1.0 — Species dropdown, PetMed Pharmacy approval language, Add
  Pet/Vet Info relabel, Google Places address autocomplete, required vet
  first/last name, searchable clinic directory with CSV import and admin
  approval queue.
