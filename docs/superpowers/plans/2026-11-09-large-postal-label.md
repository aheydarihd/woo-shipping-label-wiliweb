# Large Postal Label Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a large colored postal label template that automatically configures two stacked labels per A4 and ships as a local beta ZIP for user testing.

**Architecture:** Extend the existing label template whitelist and renderer with a `postal` template while preserving `table` and `simple`. Reuse the current label view model and field switches, apply the two-up layout in the admin UI when the template is selected, and verify the exact A4 result in Chromium.

**Tech Stack:** PHP 8.1, WordPress/WooCommerce APIs, CSS print layout, vanilla JavaScript, Playwright/Edge, existing PHP test runner.

**Spec:** `docs/superpowers/specs/2026-11-09-large-postal-label-design.md`

## Global Constraints

- Keep the technical folder and slug `woo-shipping-label-wiliweb` unchanged.
- Preserve the existing `table` and `simple` templates and all stored settings.
- The `postal` template must honor every existing label field toggle.
- Selecting `postal` in admin applies `per_page=2` and `orientation=landscape`, while later manual sizing remains possible.
- Use the existing sender logo and embedded Vazirmatn font; add no network dependency.
- Build local beta version `1.1.1-beta.1`; do not push, tag, or publish on GitHub.

## Review Focus

- Missing logo: the postal label must remain balanced and readable without an empty broken-image slot.
- Long recipient/sender addresses: text must wrap inside the two-up A4 box without overflow.
- Disabled fields: the new template must not reveal any unchecked recipient, sender, metadata, logo, or barcode field.
- Five selected orders: pagination must produce three A4 sheets with two, two, and one postal labels and no trailing blank page.
- Browser print colors: the red-blue border and brand accents must be retained in print CSS and PDF output.

---

### Task 1: Register the postal template and admin two-up preset

**Files:**
- Modify: `source/woo-shipping-label-wiliweb/includes/class-wsl-settings.php`
- Modify: `source/woo-shipping-label-wiliweb/includes/class-wsl-settings-page.php`
- Modify: `source/woo-shipping-label-wiliweb/assets/js/admin.js`
- Test: `tests/test-settings.php`
- Test: `tests/test-settings-page.php`
- Create: `tests/test-admin-js.mjs`

**Interfaces:**
- Produces: stored template value `postal` and radio control `data-wsl-label-template="postal"`.
- Produces: admin selection behavior setting layout count `2`, orientation `landscape`, and the existing two-up dimensions.

- [ ] **Step 1: Write failing PHP tests for the `postal` whitelist and admin radio control**

Assert `WSL_Settings::sanitize(['template' => 'postal'])['template'] === 'postal'`, the settings page contains `value="postal"`, and the two older template values remain present.

- [ ] **Step 2: Run the focused tests and verify RED**

Run: `php tests/run.php --group=domain` and `php tests/run.php --group=admin`

Expected: FAIL because `postal` is not accepted or rendered.

- [ ] **Step 3: Implement the whitelist and admin template card**

Add `postal` without changing the default `table` value. Mark all label template radios with `data-wsl-label-template` and describe the new card as two large labels per A4.

- [ ] **Step 4: Add a browser-independent JavaScript behavior test and implement selection behavior**

In `tests/test-admin-js.mjs`, execute the real `admin.js` inside a minimal DOM fixture, dispatch a `change` event from the postal radio, and assert the existing layout select becomes `2`, the landscape radio becomes checked, the portrait radio becomes unchecked, and the preview contains two cells. Then implement that behavior in `admin.js`. It must not reapply after the user manually edits layout controls.

- [ ] **Step 5: Run PHP and JavaScript syntax tests and verify GREEN**

Run: `php tests/run.php --group=domain`, `php tests/run.php --group=admin`, `node tests/test-admin-js.mjs`, and `node --check source/woo-shipping-label-wiliweb/assets/js/admin.js`

Expected: all PASS / exit `0`.

### Task 2: Render and visually style the large postal label

**Files:**
- Modify: `source/woo-shipping-label-wiliweb/includes/class-wsl-renderer.php`
- Create: `source/woo-shipping-label-wiliweb/templates/label-postal.php`
- Modify: `source/woo-shipping-label-wiliweb/assets/css/print.css`
- Test: `tests/test-renderer.php`

**Interfaces:**
- Consumes: `settings['template'] === 'postal'` and the existing label data/field switches.
- Produces: root class `wsl-label--postal` with `wsl-postal__inner`, recipient, sender, footer, logo, and barcode regions.

- [ ] **Step 1: Write failing renderer tests**

Assert the postal class and all named regions render; logo and barcode use existing switches; a disabled phone/address/logo/barcode does not appear; untrusted values remain escaped; `table` and `simple` still render their original root classes.

- [ ] **Step 2: Run renderer tests and verify RED**

Run: `php tests/run.php --group=renderer`

Expected: FAIL because the renderer falls back to `table`.

- [ ] **Step 3: Add template routing and `label-postal.php`**

Use an explicit allow-list in `WSL_Renderer::label()`. Structure the new template into a dominant recipient column, a logo-led sender column, a bottom metadata strip, and a barcode area; generate every optional value through `WSL_Renderer::row()` or an equivalent field check with late escaping.

- [ ] **Step 4: Add postal print CSS**

Create a red-blue striped frame using CSS only, with a white bordered inner panel, strong Persian typography, fixed wrapping/min-width rules, large logo and barcode, and `print-color-adjust: exact`. Keep rules scoped under `.wsl-label--postal`.

- [ ] **Step 5: Run renderer and full PHP suites and verify GREEN**

Run: `php tests/run.php --group=renderer` then `php tests/run.php`

Expected: all test files PASS.

### Task 3: Verify two-up A4 output and build the local beta ZIP

**Files:**
- Modify: `tests/browser/fixture.php`
- Modify: `tests/browser/verify-print.mjs`
- Modify: `source/woo-shipping-label-wiliweb/woo-shipping-label-wiliweb.php`
- Modify: `source/woo-shipping-label-wiliweb/readme.txt`
- Generated: `dist/woo-shipping-label-wiliweb-1.1.1-beta.1.zip`

**Interfaces:**
- Consumes: postal renderer/CSS and `WSL_Layout::preset(2, 'landscape')`.
- Produces: verified two-up/three-sheet PDFs, screenshot preview, and installable beta ZIP; no remote side effects.

- [ ] **Step 1: Add failing browser assertions for postal output**

Add fixture support for `template=postal`, a real logo data URI, long addresses, and disabled-field mode. Assert exactly two stacked labels on one sheet, label width greater than height, colored frame styles present, logo and barcode visible, no overflow, Vazirmatn loaded, and five labels yield exactly three sheets/PDF pages.

- [ ] **Step 2: Run browser verification and verify RED before fixture/production completion**

Run the local PHP fixture server and `node tests/browser/verify-print.mjs`.

Expected: postal assertions FAIL before the fixture and production template are fully wired.

- [ ] **Step 3: Complete the fixture, set beta metadata, and rerun browser verification**

Set plugin header, `WSL_VERSION`, and stable tag to `1.1.1-beta.1`. Preserve the GitHub updater repository/slug and make no live release.

- [ ] **Step 4: Run syntax, unit, browser, and real WooCommerce integration checks**

Run PHP lint, JS syntax, `php tests/run.php`, browser verification, then HPOS and CPT smoke/endpoint/shop-manager integration against the existing WordPress 7.1 / WooCommerce 11.1 runtime.

Expected: all checks PASS with no label/invoice regressions.

- [ ] **Step 5: Build and inspect the local beta package**

Run: `powershell -ExecutionPolicy Bypass -File tools/package-release.ps1 -Version 1.1.1-beta.1`

Assert one `woo-shipping-label-wiliweb/` root, no tests/docs/tmp/dist entries, postal template and print CSS present, embedded font present, and header version `1.1.1-beta.1`.

- [ ] **Step 6: Hand off local testing artifacts**

Report the ZIP path, SHA256, screenshot/PDF paths, and exact WordPress upload steps. Explicitly confirm no push, tag, or GitHub release occurred.
