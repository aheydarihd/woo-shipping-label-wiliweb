import fs from 'node:fs';
import vm from 'node:vm';

const layout = { value: '4' };
const portrait = { value: 'portrait', checked: true };
const landscape = { value: 'landscape', checked: false };
const sizes = Object.fromEntries(['margin_top','margin_right','margin_bottom','margin_left','gap_horizontal','gap_vertical','label_width','label_height'].map(key => [key, { value: '' }]));
const preview = {
  children: [],
  style: {},
  set innerHTML(value) { if (value === '') this.children = []; },
  appendChild(node) { this.children.push(node); },
};
let changeHandler;
const document = {
  addEventListener(type, handler) { if (type === 'change') changeHandler = handler; },
  createElement(tag) { return { tag }; },
  querySelector(selector) {
    if (selector === '[data-wsl-layout]') return layout;
    if (selector === '[data-wsl-preview]') return preview;
    if (selector === '[data-wsl-orientation]:checked') return landscape.checked ? landscape : portrait;
    if (selector === '[data-wsl-orientation][value="landscape"]') return landscape;
    if (selector === '[data-wsl-orientation][value="portrait"]') return portrait;
    const size = selector.match(/^\[data-wsl-size="([^"]+)"\]$/);
    if (size) return sizes[size[1]];
    return null;
  },
  querySelectorAll() { return []; },
};
const source = fs.readFileSync(new URL('../source/woo-shipping-label-wiliweb/assets/js/admin.js', import.meta.url), 'utf8');
vm.runInNewContext(source, { document, window: {} });

if (typeof changeHandler !== 'function') throw new Error('admin change handler was not registered');

const postal = {
  value: 'postal',
  matches(selector) { return selector.includes('[data-wsl-label-template]'); },
};
changeHandler({ target: postal });
if (layout.value !== '2') throw new Error(`postal layout count expected 2, got ${layout.value}`);
if (!landscape.checked || portrait.checked) throw new Error('postal template did not select landscape orientation');
if (preview.children.length !== 2) throw new Error(`postal preview expected 2 cells, got ${preview.children.length}`);
if (Number(sizes.label_width.value) <= Number(sizes.label_height.value)) throw new Error('postal preset must produce a wide label');

layout.value = '4';
const layoutTarget = {
  matches(selector) { return selector.includes('[data-wsl-layout]'); },
};
changeHandler({ target: layoutTarget });
if (layout.value !== '4' || preview.children.length !== 4) throw new Error('manual layout change was overridden by postal template');

console.log('PASS postal admin two-up behavior');
