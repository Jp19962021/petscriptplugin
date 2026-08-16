# PetScript Rx Checkout

WooCommerce plugin that requires pet and veterinarian information before checkout on prescription-tagged products, and submits the resulting Rx to PetScript Pharmacy.

Current version: 1.1.1

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
