# WooCommerce Shipping Label Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an installable Persian WooCommerce plugin that prints one or many order shipping labels with configurable A4 layouts and field visibility.

**Architecture:** A small bootstrap loads focused admin, settings, order-data, layout, rendering, and print-controller classes. All order reads use `WC_Order` CRUD; a protected admin-post endpoint renders a standalone, no-cache print document whose CSS uses millimetres.

**Tech Stack:** PHP 8.1+, WordPress 6.6+, WooCommerce 9+, WordPress Settings API, WooCommerce HPOS hooks, vanilla CSS/JavaScript, PHPUnit-compatible PHP test harness, Chromium print-to-PDF QA.

**Spec:** `docs/superpowers/specs/2026-09-12-woo-shipping-label-design.md`

## Global Constraints

- The plugin author is `WiliWeb` and Author URI is `https://wiliweb.com`.
- The plugin must not modify or depend on ParsKala theme files.
- Use WooCommerce CRUD only; support HPOS and legacy CPT order screens.
- Protect print requests with nonce and `edit_shop_orders`/order-management capability checks.
- Default paper is A4 and default layout is four labels in a 2 by 2 grid.
- Supported counts are 1, 2, 4, 6, and 8 labels per A4 page.
- Sender and recipient blocks are primary; every optional field has an independent visibility switch.
- Use RTL Persian UI, escaped output, sanitized input, and millimetre print dimensions.

---

### Task 1: Core Layout and Settings Domain

**Files:**
- Create: `source/woo-shipping-label-wiliweb/woo-shipping-label-wiliweb.php`
- Create: `source/woo-shipping-label-wiliweb/includes/class-wsl-settings.php`
- Create: `source/woo-shipping-label-wiliweb/includes/class-wsl-layout.php`
- Create: `tests/bootstrap.php`
- Create: `tests/test-layout.php`
- Create: `tests/test-settings.php`

**Interfaces:**
- Produces: `WSL_Layout::preset(int $count): array`, `WSL_Layout::validate(array $layout): array|WP_Error`, `WSL_Layout::paginate(array $ids, int $per_page): array`.
- Produces: `WSL_Settings::defaults(): array`, `WSL_Settings::sanitize(array $input): array`, `WSL_Settings::get(): array`.

- [ ] Write failing tests asserting presets for 1, 2, 4, 6, and 8; four uses 2 columns and 2 rows; five IDs at four per page produce arrays of four and one.
- [ ] Run `php tests/run.php --group=domain` and confirm failures are caused by missing classes.
- [ ] Implement layout presets in millimetres, reject unsupported counts and dimensions exceeding printable A4 width or height, and preserve ID order during pagination.
- [ ] Implement complete defaults and whitelist sanitization for template, layout, sender fields, colors, font size, border, and every field visibility key.
- [ ] Run the domain tests and confirm all assertions pass.
- [ ] Record a source-tree checkpoint with hashes because the workspace has no Git repository.

### Task 2: Order Data Normalization

**Files:**
- Create: `source/woo-shipping-label-wiliweb/includes/class-wsl-order-data.php`
- Create: `tests/test-order-data.php`

**Interfaces:**
- Consumes: `WC_Order` and `WSL_Settings::get()`.
- Produces: `WSL_Order_Data::from_order(WC_Order $order, array $settings): array` with `recipient`, `sender`, and `optional` keys.

- [ ] Write failing tests for shipping-first recipient data, billing fallback per empty field, sender settings, order metadata, item quantities, and empty optional values.
- [ ] Run `php tests/run.php --group=order-data` and confirm the missing class failure.
- [ ] Implement normalization through WC_Order getters only, including phone and postcode digit cleanup for display without changing stored order data.
- [ ] Run order-data and domain tests and confirm they pass.
- [ ] Record a checkpoint hash.

### Task 3: Secure Print Request and Pagination

**Files:**
- Create: `source/woo-shipping-label-wiliweb/includes/class-wsl-print-controller.php`
- Create: `tests/test-print-controller.php`

**Interfaces:**
- Consumes: explicit `order_ids` request values, nonce, current-user capabilities, `wc_get_order()`, and `WSL_Layout::paginate()`.
- Produces: validated ordered `WC_Order[]` plus skipped-count metadata; sends no-cache and no-index headers before rendering.

- [ ] Write failing tests for absent/invalid nonce, insufficient permission, invalid IDs, inaccessible orders, order preservation, skipped-count reporting, and page grouping.
- [ ] Run the controller test and confirm failures occur before implementation.
- [ ] Implement explicit request parsing with `wp_unslash`, integer filtering, nonce verification, capability checks, per-order access checks, and no-cache headers.
- [ ] Run all PHP tests and confirm they pass.
- [ ] Record a checkpoint hash.

### Task 4: Two Print Templates and A4 CSS

**Files:**
- Create: `source/woo-shipping-label-wiliweb/includes/class-wsl-renderer.php`
- Create: `source/woo-shipping-label-wiliweb/templates/label-table.php`
- Create: `source/woo-shipping-label-wiliweb/templates/label-simple.php`
- Create: `source/woo-shipping-label-wiliweb/templates/print-page.php`
- Create: `source/woo-shipping-label-wiliweb/assets/css/print.css`
- Create: `source/woo-shipping-label-wiliweb/assets/js/print.js`
- Create: `tests/test-renderer.php`

**Interfaces:**
- Consumes: normalized label arrays, sanitized settings, and paginated order groups.
- Produces: standalone RTL HTML with one `.wsl-sheet` per page and one `.wsl-label` per order.

- [ ] Write failing rendering tests proving each visibility key controls only its field, empty values make no empty row, template selection changes markup, and 5 orders at 4 per page render 2 sheets.
- [ ] Run renderer tests and confirm missing renderer/template failures.
- [ ] Implement both templates using escaped values; generate Code 128-like order barcode with deterministic SVG bars and a human-readable order number.
- [ ] Implement print CSS variables for paper, margins, gaps, rows, columns, label size, font size, color, border, page breaks, and screen preview.
- [ ] Add print controls, skipped-order warning, missing-required-data warning, scale-100 guidance, temporary template switch, and `window.print()`.
- [ ] Run all PHP rendering tests and confirm they pass.
- [ ] Record a checkpoint hash.

### Task 5: WooCommerce Admin Integration

**Files:**
- Create: `source/woo-shipping-label-wiliweb/includes/class-wsl-plugin.php`
- Create: `source/woo-shipping-label-wiliweb/includes/class-wsl-admin.php`
- Create: `source/woo-shipping-label-wiliweb/includes/class-wsl-settings-page.php`
- Create: `source/woo-shipping-label-wiliweb/assets/css/admin.css`
- Create: `source/woo-shipping-label-wiliweb/assets/js/admin.js`
- Create: `tests/test-admin-hooks.php`

**Interfaces:**
- Consumes: WordPress/WooCommerce hooks and settings domain.
- Produces: WooCommerce submenu, Settings API registration, HPOS/legacy order metabox, HPOS/legacy bulk actions, and protected print URLs.

- [ ] Write failing hook-registration tests for settings, order-page buttons, both bulk-action filters, and both bulk redirect handlers.
- [ ] Run admin-hook tests and confirm missing integration class failures.
- [ ] Implement dependency notice and HPOS compatibility declaration, registering admin code only in wp-admin.
- [ ] Implement the RTL settings page with template cards, live layout preview, sender fields, all visibility switches, size presets, and inline validation messages.
- [ ] Implement single-order print button and bulk action for legacy `edit-shop_order` and HPOS `woocommerce_page_wc-orders`.
- [ ] Load assets only on plugin settings and relevant order screens.
- [ ] Run all PHP tests and PHP syntax checks.
- [ ] Record a checkpoint hash.

### Task 6: Integration, Visual QA, and Release

**Files:**
- Create: `tests/run.php`
- Create: `tests/integration-smoke.php`
- Create: `tests/browser/print-fixture.html`
- Create: `tests/browser/verify-print.mjs`
- Create: `dist/INSTALL-FA.md`
- Create: `dist/TEST-REPORT.md`
- Create: `tools/package-release.ps1`
- Create: `readme.txt`

**Interfaces:**
- Consumes: complete source plugin and local disposable WordPress/WooCommerce runtime.
- Produces: installable ZIP, SHA-256 checksum, rendered screenshots/PDF measurements, and verification report.

- [ ] Build the test runner and verify every earlier red-green test is included.
- [ ] Install the plugin into the existing disposable WooCommerce runtime and test activation, settings save, single-order print, bulk print, HPOS, and legacy storage.
- [ ] Generate 1-, 2-, 4-, 5-, 6-, and 8-order fixtures and print them to PDF in Chromium at A4/100 percent.
- [ ] Verify page counts, 2 by 2 placement for four, overflow absence, RTL readability, field toggles, and both templates at desktop and narrow admin viewports.
- [ ] Run PHP syntax scanning over every shipped PHP file and scan the package for development files or nested plugin roots.
- [ ] Write Persian installation/printing instructions and a test report with exact commands and limitations.
- [ ] Package exactly one `woo-shipping-label-wiliweb` root, compute SHA-256, unpack it to a temporary directory, and repeat syntax/structure checks.
- [ ] Record final source and ZIP hashes.

## Execution Mode

Execute inline in this task using `superpowers:executing-plans`. This choice follows the user’s instruction to proceed and the current rule that subagents are used only when explicitly requested.
