# WiliWeb Invoice Builder Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Upgrade the existing shipping-label plugin into the admin-only «فاکتور ساز ویلی وب» with independently switchable label and invoice modules, polished A4 invoices, product images, bulk printing, and a verified GitHub-delivered `1.1.0` update.

**Architecture:** Preserve the installed plugin identity (`woo-shipping-label-wiliweb`) and extend the existing `WSL_` architecture. Keep the proven signed print controller as the single entry point, add a document discriminator and dedicated invoice data/renderer/template classes, and extend the existing option without destructive migration. The invoice and label surfaces remain isolated so a disabled or broken invoice path cannot change existing label output.

**Tech Stack:** PHP 8.1+, WordPress Settings/Media/Admin APIs, WooCommerce CRUD/HPOS APIs, HTML/CSS print layouts, vanilla JavaScript, custom PHP test harness, real WordPress 7.1/WooCommerce 11.1 integration runtime, Playwright/Edge browser verification, GitHub Actions/Releases.

**Spec:** `docs/superpowers/specs/2026-11-06-wiliweb-invoice-builder-design.md`

## Global Constraints

- Display name is exactly `فاکتور ساز ویلی وب`; plugin folder, main file, Text Domain, Update URI, GitHub repository, and release asset slug remain `woo-shipping-label-wiliweb`.
- Release version is `1.1.0`; minimum versions remain WordPress `6.6`, PHP `8.1`, and WooCommerce `9.0`; HPOS compatibility remains declared.
- Existing `wsl_settings` data and label defaults/output must survive an update without reset or migration loss.
- Invoice access is admin-side only and requires WooCommerce management/order-edit capabilities; no My Account, email attachment, server-side PDF, licensing, telemetry, or external service is introduced.
- The invoice is called `فاکتور فروش`, not an official tax invoice; no Tax Administration compliance claim may appear.
- New links use the existing signed, user-bound, ten-minute HMAC request; old signed label payloads without a document type continue to work.
- All printed Persian text uses bundled Vazirmatn and remains theme-independent.
- Every task follows red-green-refactor TDD and preserves the existing test suite.

## Review Focus

- **Old options without `modules` or `invoice`:** Task 1 tests that they merge to label enabled, invoice enabled, and retain every prior label value.
- **Deleted products, missing variation images, and unsafe item metadata:** Task 2 tests placeholder fallback, parent-image fallback, sanitization, and no fatal.
- **Disabled module reached through a valid or manually crafted URL:** Task 3 and Task 6 test UI removal and server-side rejection.
- **Long and grouped invoices:** Task 7 tests repeating headers, unbroken item rows, exact page starts, and no trailing blank page.
- **Free, discounted, taxed, shipped, and refunded orders:** Task 2 and Task 4 test WooCommerce-derived financial rows and hide only genuinely empty rows.

---

### Task 1: Backward-Compatible Product Settings and Identity

**Files:**
- Modify: `source/woo-shipping-label-wiliweb/includes/class-wsl-settings.php`
- Modify: `source/woo-shipping-label-wiliweb/woo-shipping-label-wiliweb.php`
- Modify: `source/woo-shipping-label-wiliweb/includes/class-wsl-plugin.php`
- Test: `tests/test-settings.php`
- Add: `docs/superpowers/specs/2026-11-06-wiliweb-invoice-builder-design.md`
- Add: `docs/superpowers/plans/2026-11-06-wiliweb-invoice-builder.md`

**Interfaces:**
- Consumes: existing `WSL_Settings::OPTION`, `field_keys()`, `defaults()`, `sanitize()`, and `get()`.
- Produces: `WSL_Settings::invoice_field_keys(): array`, `WSL_Settings::module_enabled(array $settings, string $module): bool`, and expanded `defaults()/sanitize()` containing `modules` and `invoice` while retaining every existing top-level label key.

- [ ] **Step 1: Add failing settings tests**

Add assertions to `tests/test-settings.php` for:

- defaults contain `modules.labels_enabled === true` and `modules.invoice_enabled === true`;
- invoice defaults contain template `modern`, title `فاکتور فروش`, accent `#155eef`, footer note `از خرید شما سپاسگزاریم`, `logo_id` `0`, and all defined invoice field booleans;
- allowed templates are only `modern` and `classic`;
- invoice title/footer are sanitized, accent is validated, logo uses `absint`, and missing invoice checkboxes become false on an explicit invoice settings submission;
- an old stored array containing only existing label keys retains its template/layout/sender/fields and gains module/invoice defaults;
- `module_enabled()` rejects unknown module names.

- [ ] **Step 2: Run the settings group and verify failure**

Run: `php tests/run.php --group=domain`

Expected: FAIL because invoice settings and module helpers do not exist.

- [ ] **Step 3: Implement the extended settings schema**

In `WSL_Settings` add:

```php
public static function invoice_field_keys(): array;
public static function module_enabled(array $settings, string $module): bool;
```

Use these invoice field keys exactly: `logo`, `seller_contact`, `seller_address`, `buyer_phone`, `buyer_email`, `buyer_address`, `buyer_postcode`, `order_number`, `order_date`, `order_status`, `payment_method`, `shipping_method`, `product_image`, `sku`, `variation`, `unit_price`, `quantity`, `line_discount`, `line_total`, `subtotal`, `discount_total`, `shipping_total`, `tax_total`, `refund_total`, `grand_total`, `customer_note`, `footer_note`, `signature`, `barcode`.

Keep existing label keys at their existing locations. Merge stored arrays recursively against defaults in `get()` before sanitizing so pre-1.1.0 options are not interpreted as an explicit unchecked invoice form. Sanitize user-submitted arrays deterministically and preserve unrelated known label values.

- [ ] **Step 4: Update only the human-facing plugin identity**

Set the plugin header name and dependency notice to `فاکتور ساز ویلی وب`, and expand the description to mention invoices and labels. Do not change version yet and do not rename any path, constant, hook, option, text domain, updater slug, or repository reference.

- [ ] **Step 5: Run tests and inspect the settings diff**

Run: `php tests/run.php --group=domain`

Expected: PASS with existing layout tests unchanged and new compatibility assertions passing.

- [ ] **Step 6: Commit the settings foundation**

```powershell
git add source/woo-shipping-label-wiliweb/includes/class-wsl-settings.php source/woo-shipping-label-wiliweb/woo-shipping-label-wiliweb.php source/woo-shipping-label-wiliweb/includes/class-wsl-plugin.php tests/test-settings.php docs/superpowers/specs/2026-11-06-wiliweb-invoice-builder-design.md docs/superpowers/plans/2026-11-06-wiliweb-invoice-builder.md
git commit -m "feat: add modular invoice settings foundation"
```

### Task 2: Invoice Order View Model

**Files:**
- Modify: `source/woo-shipping-label-wiliweb/includes/class-wsl-order-data.php`
- Create: `tests/test-invoice-order-data.php`
- Modify: `tests/run.php`
- Modify: `tests/bootstrap.php`

**Interfaces:**
- Consumes: `WC_Order`, `WC_Order_Item_Product`, WooCommerce product/media/price APIs, and sanitized settings from Task 1.
- Produces: `WSL_Order_Data::invoice_from_order(WC_Order $order, array $settings): array` with stable `order`, `seller`, `buyer`, `items`, `totals`, and `notes` keys.

- [ ] **Step 1: Register an invoice-data test group and write the failing fixtures**

Create lightweight order/item/product stubs that cover:

- a variation with its own image, SKU, readable attributes, quantity `2`, subtotal `300000`, total `250000`, and tax;
- a variation without an image that falls back to the parent image;
- a deleted product with only the stored order-item name;
- HTML and private metadata that must not be printed;
- order subtotal, discount, shipping, tax, refund, total, billing/shipping fallback, payment, shipping, status, date, and notes.

Assert exact output keys and that monetary strings come from `wc_price()` rather than hand formatting.

- [ ] **Step 2: Run the invoice-data test and verify failure**

Run: `php tests/run.php --group=invoice-data`

Expected: FAIL because `invoice_from_order()` is undefined.

- [ ] **Step 3: Implement the invoice view model**

Add:

```php
public static function invoice_from_order(WC_Order $order, array $settings): array;
```

Return:

- `order`: `id`, `number`, `date`, `status`, `payment_method`, `shipping_method`, `barcode`;
- `seller`: name/contact/address/postcode/website/logo resolved from invoice logo first, then sender logo;
- `buyer`: name/phone/email/province/city/address/postcode using shipping-first fallback rules;
- `items[]`: `name`, `image_url`, `sku`, `variation`, `quantity`, `unit_price`, `subtotal`, `discount`, `total`;
- `totals`: `subtotal`, `discount`, `shipping`, `tax`, `refund`, `grand_total`, each with raw numeric value and escaped-ready formatted value;
- `notes`: `customer` and configured `footer`.

Use WooCommerce CRUD methods only, ignore metadata keys beginning with `_`, strip tags from display strings, and return an empty image URL when no valid attachment exists.

- [ ] **Step 4: Run order-data tests**

Run: `php tests/run.php --group=order-data`

Expected: PASS for existing label data and all new invoice fixtures, including free/refunded/deleted-product cases.

- [ ] **Step 5: Commit the invoice data layer**

```powershell
git add source/woo-shipping-label-wiliweb/includes/class-wsl-order-data.php tests/test-invoice-order-data.php tests/test-order-data.php tests/run.php tests/bootstrap.php
git commit -m "feat: build WooCommerce invoice order data"
```

### Task 3: Signed Multi-Document Print Requests

**Files:**
- Modify: `source/woo-shipping-label-wiliweb/includes/class-wsl-print-controller.php`
- Modify: `tests/test-print-controller.php`
- Modify: `tests/integration-render-endpoint.php`

**Interfaces:**
- Consumes: existing HMAC payload, nonce, user binding, expiry, and order-ID validation.
- Produces: `WSL_Print_Controller::create_url(array $ids, string $document = 'label'): string`, `create_request(array $ids, ?int $expires = null, string $document = 'label'): array`, and `verify_document_request(string $payload, string $signature): array` returning `['ids' => int[], 'document' => 'label'|'invoice']`; existing `verify_request()` continues returning only IDs.

- [ ] **Step 1: Add failing controller tests**

Assert:

- default `create_url([12])` still produces a label request;
- `create_url([12], 'invoice')` produces a verifiable invoice request;
- old payload JSON without `document` verifies as `label`;
- unknown document types, tampering, expiry, wrong user, and empty IDs are rejected;
- `verify_request()` still returns `[12]` for old callers;
- signed requests remain free of `&amp;` and transients.

- [ ] **Step 2: Run controller tests and verify failure**

Run: `php tests/run.php --group=controller`

Expected: FAIL on missing document support.

- [ ] **Step 3: Extend payload creation and verification**

Implement the interfaces above. Include `document` in new payloads, default absent values to `label`, whitelist only `label` and `invoice`, keep the nonce action tied to the signature, and never accept a caller-provided class/template path.

- [ ] **Step 4: Add safe, module-aware document dispatch**

Update `render()` so it verifies the request once, checks the requested module server-side, and preserves the existing label path. A disabled document returns a Persian `wp_die()` message and status `403`; invalid or expired payload remains rejected. Guard the future invoice branch with `class_exists('WSL_Invoice_Renderer')` and return a controlled `503` message until Task 4 registers the renderer, so this commit can never fatal on a manually crafted invoice URL.

- [ ] **Step 5: Extend request endpoint integration coverage**

Run the endpoint integration for a current label payload and an old label payload; assert that a disabled invoice request is rejected and that an enabled invoice request returns the controlled unavailable response rather than a fatal before Task 4.

Run: `php tests/run.php --group=controller`

Expected: PASS.

- [ ] **Step 6: Commit the secure document routing**

```powershell
git add source/woo-shipping-label-wiliweb/includes/class-wsl-print-controller.php tests/test-print-controller.php tests/integration-render-endpoint.php
git commit -m "feat: route signed label and invoice print requests"
```

### Task 4: Invoice Renderer and Two Print Templates

**Files:**
- Create: `source/woo-shipping-label-wiliweb/includes/class-wsl-invoice-renderer.php`
- Create: `source/woo-shipping-label-wiliweb/templates/invoice-modern.php`
- Create: `source/woo-shipping-label-wiliweb/templates/invoice-classic.php`
- Create: `source/woo-shipping-label-wiliweb/templates/invoice-print-page.php`
- Modify: `source/woo-shipping-label-wiliweb/woo-shipping-label-wiliweb.php`
- Modify: `source/woo-shipping-label-wiliweb/includes/class-wsl-print-controller.php`
- Create: `tests/test-invoice-renderer.php`
- Modify: `tests/run.php`
- Modify: `tests/integration-render-endpoint.php`

**Interfaces:**
- Consumes: Task 2 invoice view model and Task 1 invoice settings.
- Produces: `WSL_Invoice_Renderer::invoice(array $invoice, array $settings): string`, `page(array $invoices, array $settings, int $skipped = 0): void`, `enabled(array $settings, string $key): bool`, escaped helper output for rows/totals, and the documented filters `wsl_invoice_data`, `wsl_invoice_title`, `wsl_invoice_logo_url`, and `wsl_invoice_html`.

- [ ] **Step 1: Write failing renderer tests**

Test that:

- `modern` output contains `wsl-invoice--modern`, logo/name, buyer/seller blocks, product image, SKU, variation, quantities, and totals;
- `classic` output contains `wsl-invoice--classic` and signature space;
- disabled fields disappear without hiding unrelated data;
- empty optional values produce no empty labelled row;
- user/order text is escaped;
- two invoices create two `.wsl-invoice-document` roots and the second has a forced page start;
- barcode HTML remains escaped and scoped;
- each public invoice filter receives the documented value/order/settings arguments and cannot select a filesystem template.

- [ ] **Step 2: Run renderer tests and verify failure**

Run: `php tests/run.php --group=invoice-renderer`

Expected: FAIL because the renderer/templates do not exist.

- [ ] **Step 3: Implement the renderer and template whitelist**

Create `WSL_Invoice_Renderer` with a hard whitelist mapping `modern` and `classic` to fixed local template paths. Helpers must escape late and must not accept raw template filenames from settings.

- [ ] **Step 4: Build the modern template**

Implement a branded header, order badge, seller/buyer cards, item table with image and optional columns, summary card, shipping/payment strip, notes, barcode, and optional signature area. Use semantic table markup with `<thead>` for repeatability.

- [ ] **Step 5: Build the classic template and shared print page**

Implement a grayscale-friendly official-looking layout without tax-authority claims. `invoice-print-page.php` owns the toolbar, alerts, theme-independent font/style links, and one root per invoice.

- [ ] **Step 6: Complete controller dispatch and endpoint integration**

Replace Task 3's guarded unavailable branch with invoice view-model creation and `WSL_Invoice_Renderer::page()`. Run the real endpoint for one valid invoice and assert `wsl-invoice-document`, order data, and no expiry/fatal message.

- [ ] **Step 7: Run renderer and full unit tests**

Run: `php tests/run.php`

Expected: PASS with label renderer tests unchanged.

- [ ] **Step 8: Commit invoice rendering**

```powershell
git add source/woo-shipping-label-wiliweb/includes/class-wsl-invoice-renderer.php source/woo-shipping-label-wiliweb/includes/class-wsl-print-controller.php source/woo-shipping-label-wiliweb/templates/invoice-*.php source/woo-shipping-label-wiliweb/woo-shipping-label-wiliweb.php tests/test-invoice-renderer.php tests/integration-render-endpoint.php tests/run.php
git commit -m "feat: add modern and classic invoice templates"
```

### Task 5: Commercial Admin Settings Experience

**Files:**
- Modify: `source/woo-shipping-label-wiliweb/includes/class-wsl-settings-page.php`
- Modify: `source/woo-shipping-label-wiliweb/assets/css/admin.css`
- Modify: `source/woo-shipping-label-wiliweb/assets/js/admin.js`
- Create: `tests/test-settings-page.php`
- Modify: `tests/run.php`

**Interfaces:**
- Consumes: Task 1 schema and existing WordPress media picker.
- Produces: one WooCommerce submenu named `فاکتور ساز ویلی وب` with `general`, `invoice`, and `labels` tab renderers; existing option submission remains `wsl_settings_group`.

- [ ] **Step 1: Write failing settings-page tests**

Stub the required admin functions and assert:

- menu/page title is `فاکتور ساز ویلی وب` and capability is `manage_woocommerce`;
- allowed tabs are only `general`, `invoice`, and `labels`;
- general tab includes both module switches;
- invoice tab includes both template choices and every `invoice_field_keys()` switch;
- labels tab still includes every existing layout/sender/field control name;
- an invalid tab falls back to `general`.

- [ ] **Step 2: Run admin tests and verify failure**

Run: `php tests/run.php --group=admin`

Expected: FAIL because the tabbed page and invoice controls do not exist.

- [ ] **Step 3: Refactor the page into focused tab renderers**

Keep `register_setting()` and the media picker. Add private render methods for general, invoice, and labels, but preserve exact existing label input names. Use query links only for navigation; one settings form saves the complete known schema without dropping fields from inactive tabs.

- [ ] **Step 4: Add invoice controls and preview**

Add commercial-looking template cards, grouped field switches, logo selection with fallback explanation, accent/title/footer controls, and a clearly labelled sample preview. Do not add AJAX or a remote dependency.

- [ ] **Step 5: Update scoped CSS and JavaScript**

Keep all selectors under `.wsl-admin`, retain Vazirmatn, support RTL/mobile, and add module-state and invoice-template preview behavior without changing label size calculations. Reuse one media frame helper for sender and invoice logos.

- [ ] **Step 6: Run PHP and JavaScript checks**

Run:

```powershell
php tests/run.php --group=admin
node --check source/woo-shipping-label-wiliweb/assets/js/admin.js
```

Expected: all admin tests PASS and JavaScript exits `0`.

- [ ] **Step 7: Commit the settings UI**

```powershell
git add source/woo-shipping-label-wiliweb/includes/class-wsl-settings-page.php source/woo-shipping-label-wiliweb/assets/css/admin.css source/woo-shipping-label-wiliweb/assets/js/admin.js tests/test-settings-page.php tests/run.php
git commit -m "feat: add commercial invoice settings interface"
```

### Task 6: Single and Bulk Admin Invoice Actions

**Files:**
- Modify: `source/woo-shipping-label-wiliweb/includes/class-wsl-admin.php`
- Modify: `tests/test-admin-hooks.php`
- Modify: `tests/integration-shop-manager.php`
- Modify: `tests/integration-smoke.php`

**Interfaces:**
- Consumes: `WSL_Settings::module_enabled()` and `WSL_Print_Controller::create_url($ids, $document)`.
- Produces: order metabox `اسناد سفارش`, bulk keys `wsl_print_labels` and `wsl_print_invoices`, and server-side module-aware handlers for CPT and HPOS.

- [ ] **Step 1: Add failing admin-action tests**

Assert:

- both HPOS and legacy bulk filters remain registered;
- both modules enabled show separate print links and no nested form;
- invoice URL is created with document `invoice` and label URL with `label`;
- each disabled module removes only its own metabox button and bulk action;
- a manually submitted disabled bulk action does not create a print URL;
- unsupported bulk actions return the original redirect.

- [ ] **Step 2: Run admin tests and verify failure**

Run: `php tests/run.php --group=admin`

Expected: FAIL on missing invoice action and module gating.

- [ ] **Step 3: Implement the unified order-documents metabox**

Rename the box title to `اسناد سفارش`; render module buttons only after order-edit capability and module checks. Keep anchor links with `target="_blank"` and `rel="noopener noreferrer"`.

- [ ] **Step 4: Implement module-aware bulk actions**

Add `چاپ فاکتور فروش` alongside the existing label action for both order storage screens. Map action keys to fixed document names, check `manage_woocommerce`/order-edit capability, reject disabled modules, and call the signed controller.

- [ ] **Step 5: Extend real-role integration tests**

Test `shop_manager` can see/use both enabled actions, cannot see a disabled invoice action, and a lower role cannot reach either endpoint. Run on both HPOS and CPT modes in the existing runtime.

- [ ] **Step 6: Run admin/unit integration tests**

Run:

```powershell
php tests/run.php --group=admin
php tests/run.php
```

Expected: PASS.

- [ ] **Step 7: Commit admin actions**

```powershell
git add source/woo-shipping-label-wiliweb/includes/class-wsl-admin.php tests/test-admin-hooks.php tests/integration-shop-manager.php tests/integration-smoke.php
git commit -m "feat: add admin-only invoice print actions"
```

### Task 7: A4 Invoice Styling and Browser Print Verification

**Files:**
- Create: `source/woo-shipping-label-wiliweb/assets/css/invoice-print.css`
- Modify: `source/woo-shipping-label-wiliweb/templates/invoice-print-page.php`
- Modify: `tests/browser/fixture.php`
- Modify: `tests/browser/verify-print.mjs`

**Interfaces:**
- Consumes: Task 4 HTML classes and bundled `assets/fonts/Vazirmatn.woff2`.
- Produces: A4 portrait invoice CSS and browser fixtures for short, long, grouped, modern, and classic invoices.

- [ ] **Step 1: Add failing browser scenarios**

Extend the fixture with `document=invoice`, `template=modern|classic`, `items=N`, and `invoices=N`. Add assertions for:

- one short invoice prints without overflow or a blank second page;
- a long 35-item invoice spans pages, repeats `<thead>`, and no `.wsl-invoice-item` is split;
- three grouped invoices each begin on a new page and no empty final page exists;
- both templates load bundled Vazirmatn;
- product thumbnails are bounded and do not expand row width;
- print media is A4 portrait and toolbars/alerts are hidden.

- [ ] **Step 2: Run browser verification and verify failure**

Start the fixture server:

```powershell
php -S 127.0.0.1:8792 -t E:\Codex\Wiliweb\woo-shipping-label-wiliweb
```

In another process run: `node tests/browser/verify-print.mjs`

Expected: invoice scenarios FAIL because invoice print CSS is missing.

- [ ] **Step 3: Implement scoped invoice print CSS**

Use `.wsl-invoice-*` selectors, `@page { size: A4 portrait; margin: 0; }`, safe inner page margins, `thead { display: table-header-group; }`, `tr { break-inside: avoid; }`, and `.wsl-invoice-document + .wsl-invoice-document { break-before: page; }`. Style modern output for color and classic output for grayscale while preserving legibility when background graphics are disabled.

- [ ] **Step 4: Run browser verification and inspect PDFs/screenshots**

Run: `node tests/browser/verify-print.mjs`

Expected: all previous label checks and every invoice scenario PASS; generated PDFs contain no clipped content or trailing blank page.

- [ ] **Step 5: Commit print styling**

```powershell
git add source/woo-shipping-label-wiliweb/assets/css/invoice-print.css source/woo-shipping-label-wiliweb/templates/invoice-print-page.php tests/browser/fixture.php tests/browser/verify-print.mjs
git commit -m "feat: polish and verify A4 invoice printing"
```

### Task 8: Bootstrap, Metadata, Documentation, and Version 1.1.0

**Files:**
- Modify: `source/woo-shipping-label-wiliweb/woo-shipping-label-wiliweb.php`
- Modify: `source/woo-shipping-label-wiliweb/includes/class-wsl-github-updater.php`
- Modify: `source/woo-shipping-label-wiliweb/readme.txt`
- Modify: `README.md`
- Modify: `.github/workflows/release.yml`
- Modify: `tests/test-github-updater.php`
- Modify: `tests/integration-github-updater.php`

**Interfaces:**
- Consumes: all new classes and the unchanged GitHub repository/asset contract.
- Produces: complete version `1.1.0` metadata and updater information displayed as `فاکتور ساز ویلی وب`.

- [ ] **Step 1: Add failing metadata/updater assertions**

Assert that a `v1.1.0` release with `woo-shipping-label-wiliweb-1.1.0.zip` is accepted, updater information uses the new display name/description, and the old repository/slug remain exact.

- [ ] **Step 2: Run updater tests and verify failure**

Run: `php tests/run.php --group=admin`

Expected: FAIL on old display/version metadata expectations.

- [ ] **Step 3: Register all invoice classes and bump version metadata**

Require invoice renderer before the controller, set header/`WSL_VERSION`/Stable tag to `1.1.0`, update descriptions/tags/changelog, and document admin-only invoice printing, module switches, templates, product images, permissions, and backward compatibility.

- [ ] **Step 4: Preserve and rebrand the release workflow**

Keep tag validation and asset path unchanged. Change only the GitHub Release title to `WiliWeb Invoice Builder $GITHUB_REF_NAME` and retain exact ZIP root `woo-shipping-label-wiliweb`.

- [ ] **Step 5: Run unit tests and metadata scans**

Run:

```powershell
php tests/run.php
rg -n "Version: 1.1.0|WSL_VERSION', '1.1.0|Stable tag: 1.1.0" source/woo-shipping-label-wiliweb
rg -n "woo-shipping-label-wiliweb" source/woo-shipping-label-wiliweb .github/workflows/release.yml
```

Expected: tests PASS, exactly three version declarations match, and all technical slugs remain unchanged.

- [ ] **Step 6: Commit release metadata**

```powershell
git add source/woo-shipping-label-wiliweb/woo-shipping-label-wiliweb.php source/woo-shipping-label-wiliweb/includes/class-wsl-github-updater.php source/woo-shipping-label-wiliweb/readme.txt README.md .github/workflows/release.yml tests/test-github-updater.php tests/integration-github-updater.php
git commit -m "chore: prepare invoice builder version 1.1.0"
```

### Task 9: Full Compatibility, Packaging, GitHub Release, and Update Offer

**Files:**
- Modify if needed: `tests/integration-smoke.php`
- Modify if needed: `tests/integration-render-endpoint.php`
- Modify if needed: `tests/integration-shop-manager.php`
- Generated: `dist/woo-shipping-label-wiliweb-1.1.0.zip`
- Generated: `dist/woo-shipping-label-wiliweb-1.1.0.zip.sha256`

**Interfaces:**
- Consumes: completed `1.1.0` source and existing public GitHub release workflow.
- Produces: a tested release tag `v1.1.0`, canonical GitHub ZIP/checksum, and a live WordPress updater offer from `1.0.6` to `1.1.0`.

- [ ] **Step 1: Run syntax and unit verification**

Run:

```powershell
Get-ChildItem source\woo-shipping-label-wiliweb -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
node --check source/woo-shipping-label-wiliweb/assets/js/admin.js
node --check source/woo-shipping-label-wiliweb/assets/js/print.js
php tests/run.php
```

Expected: every PHP file reports no syntax errors, JavaScript checks exit `0`, and all unit test files PASS.

- [ ] **Step 2: Install the source into the real WordPress runtime**

Copy the source folder to `E:\Codex\Wiliweb\woo-crm-v2\tmp\runtime\wordpress\wp-content\plugins\woo-shipping-label-wiliweb` without renaming it. Activate and run `tests/integration-smoke.php`, `tests/integration-render-endpoint.php`, and `tests/integration-shop-manager.php` once with HPOS enabled and once with CPT storage enabled.

Expected: activation, old settings upgrade, label print, invoice print, module gating, shop-manager access, and endpoint security all PASS in both modes.

- [ ] **Step 3: Run all browser print checks**

Start the local fixture server and run: `node tests/browser/verify-print.mjs`

Expected: existing label pagination/orientation/font checks and all invoice A4 scenarios PASS.

- [ ] **Step 4: Build and inspect the local ZIP**

Run: `powershell -ExecutionPolicy Bypass -File tools/package-release.ps1 -Version 1.1.0`

Inspect archive entries and assert:

- one root folder named `woo-shipping-label-wiliweb/`;
- no `.git`, `tests`, `docs`, `tmp`, or `dist` entries;
- invoice classes/templates/CSS and Vazirmatn are present;
- plugin header inside the archive is `1.1.0`.

- [ ] **Step 5: Commit any final test-only fixes and verify a clean tree**

Run: `git status --short`

Expected: no uncommitted source/test/document changes. `dist/` and `tmp/` remain ignored.

- [ ] **Step 6: Push `main`, tag, and publish**

Run:

```powershell
git push origin main
git tag -a v1.1.0 -m "فاکتور ساز ویلی وب 1.1.0"
git push origin v1.1.0
```

Do not tag until every earlier check passes and the design/plan commits are on `main`.

- [ ] **Step 7: Verify GitHub Actions and canonical release assets**

Wait for the release workflow to succeed, then verify the public release API returns non-draft/non-prerelease `v1.1.0`, exact asset URL:

`https://github.com/aheydarihd/woo-shipping-label-wiliweb/releases/download/v1.1.0/woo-shipping-label-wiliweb-1.1.0.zip`

Download metadata/checksum only as needed and confirm the published SHA256 matches `SHA256SUMS.txt`.

- [ ] **Step 8: Verify the live WordPress update offer**

Run `tests/integration-github-updater.php` with the installed version fixture set to `1.0.6` and clear the updater transient before the check.

Expected: `UPDATER_API=PASS`, `UPDATER_OFFER=PASS`, version `1.1.0`, and the exact GitHub asset URL.

- [ ] **Step 9: Final human-facing handoff**

Report the release URL, canonical ZIP URL, SHA256, tests performed, unchanged technical slug/folder, and the exact dashboard path to update. Clearly state that invoice access is admin/shop-manager only and that this version does not claim official tax-invoice compliance.

