import playwright from 'file:///C:/Users/Aheyd/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/index.js';
const { chromium } = playwright;

const executablePath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const browser = await chromium.launch({ headless: true, executablePath });
function pdfPages(buffer) {
  return (buffer.toString('latin1').match(/\/Type\s*\/Page\b/g) || []).length;
}
for (const count of [4, 5]) {
  const page = await browser.newPage({ viewport: { width: 1200, height: 900 } });
  await page.goto(`http://127.0.0.1:8792/tests/browser/fixture.php?count=${count}&template=table`, { waitUntil: 'networkidle' });
  await page.emulateMedia({ media: 'print' });
  const metrics = await page.evaluate(() => ({
    bodyHeight: document.body.scrollHeight,
    sheets: [...document.querySelectorAll('.wsl-sheet')].map(el => ({
      height: el.getBoundingClientRect().height,
      scrollHeight: el.scrollHeight,
      top: el.getBoundingClientRect().top,
      breakBefore: getComputedStyle(el).breakBefore,
      breakAfter: getComputedStyle(el).breakAfter,
    })),
  }));
  const output = new URL(`./playwright-table-${count}.pdf`, import.meta.url).pathname.replace(/^\/(.:)/, '$1');
  await page.pdf({ path: output, format: 'A4', printBackground: true, margin: { top: 0, right: 0, bottom: 0, left: 0 } });
  console.log(JSON.stringify({ count, metrics, output }));
  await page.close();
}
{
  const page = await browser.newPage({ viewport: { width: 1200, height: 900 } });
  await page.goto('http://127.0.0.1:8792/tests/browser/fixture.php?count=4&template=table&orientation=landscape', { waitUntil: 'networkidle' });
  const result = await page.evaluate(async () => {
    await document.fonts.ready;
    const label = document.querySelector('.wsl-label').getBoundingClientRect();
    return {
      labels: document.querySelectorAll('.wsl-label').length,
      columns: getComputedStyle(document.querySelector('.wsl-sheet')).gridTemplateColumns.split(' ').length,
      width: label.width,
      height: label.height,
      fontLoaded: document.fonts.check('12px "WSL Vazirmatn"'),
      computedFont: getComputedStyle(document.querySelector('.wsl-label')).fontFamily,
    };
  });
  if (result.labels !== 4 || result.columns !== 1 || result.width <= result.height || !result.fontLoaded || !result.computedFont.includes('WSL Vazirmatn')) {
    throw new Error(`Landscape/font verification failed: ${JSON.stringify(result)}`);
  }
  console.log(JSON.stringify({ landscape: 'PASS', result }));
  await page.close();
}
{
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  await page.goto('http://127.0.0.1:8792/tests/browser/fixture.php?count=2&template=postal', { waitUntil: 'networkidle' });
  await page.emulateMedia({ media: 'print' });
  const result = await page.evaluate(async () => {
    await document.fonts.ready;
    const sheet = document.querySelector('.wsl-sheet');
    const labels = [...document.querySelectorAll('.wsl-label--postal')];
    const first = labels[0];
    const frame = first ? getComputedStyle(first) : null;
    return {
      sheets: document.querySelectorAll('.wsl-sheet').length,
      labels: labels.length,
      columns: sheet ? getComputedStyle(sheet).gridTemplateColumns.split(' ').length : 0,
      rows: sheet ? getComputedStyle(sheet).gridTemplateRows.split(' ').length : 0,
      wide: first ? first.getBoundingClientRect().width > first.getBoundingClientRect().height : false,
      noOverflow: labels.every(label => label.scrollWidth <= label.clientWidth && label.scrollHeight <= label.clientHeight),
      stripedFrame: !!frame && frame.backgroundImage.includes('repeating-linear-gradient'),
      exactColors: !!frame && (frame.printColorAdjust === 'exact' || frame.webkitPrintColorAdjust === 'exact'),
      logo: !!document.querySelector('.wsl-postal__logo img'),
      barcode: !!document.querySelector('.wsl-postal__barcode .wsl-barcode'),
      fontLoaded: document.fonts.check('12px "WSL Vazirmatn"'),
    };
  });
  const pdfOutput = new URL('./postal-two-up.pdf', import.meta.url).pathname.replace(/^\/(.:)/, '$1');
  const screenshotOutput = new URL('./postal-two-up.png', import.meta.url).pathname.replace(/^\/(.:)/, '$1');
  const pdf = await page.pdf({ path: pdfOutput, format: 'A4', printBackground: true, margin: { top: 0, right: 0, bottom: 0, left: 0 } });
  const pages = pdfPages(pdf);
  if (pages !== 1 || result.sheets !== 1 || result.labels !== 2 || result.columns !== 1 || result.rows !== 2 || !result.wide || !result.noOverflow || !result.stripedFrame || !result.exactColors || !result.logo || !result.barcode || !result.fontLoaded) {
    throw new Error(`Postal two-up verification failed: ${JSON.stringify({ pages, result })}`);
  }
  await page.emulateMedia({ media: 'screen' });
  await page.screenshot({ path: screenshotOutput, fullPage: true });
  console.log(JSON.stringify({ postal: 'two-up', pages, result, pdfOutput, screenshotOutput }));
  await page.close();
}
{
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  await page.goto('http://127.0.0.1:8792/tests/browser/fixture.php?count=5&template=postal', { waitUntil: 'networkidle' });
  await page.emulateMedia({ media: 'print' });
  const pdf = await page.pdf({ format: 'A4', printBackground: true, margin: { top: 0, right: 0, bottom: 0, left: 0 } });
  const pages = pdfPages(pdf);
  const result = await page.evaluate(() => ({ sheets: document.querySelectorAll('.wsl-sheet').length, labels: document.querySelectorAll('.wsl-label--postal').length, counts: [...document.querySelectorAll('.wsl-sheet')].map(sheet => sheet.querySelectorAll('.wsl-label--postal').length) }));
  if (pages !== 3 || result.sheets !== 3 || result.labels !== 5 || JSON.stringify(result.counts) !== '[2,2,1]') throw new Error(`Postal pagination verification failed: ${JSON.stringify({ pages, result })}`);
  console.log(JSON.stringify({ postal: 'five-labels', pages, result }));
  await page.close();
}
{
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  const measure = async size => {
    await page.goto(`http://127.0.0.1:8792/tests/browser/fixture.php?count=2&template=postal&logo=${size}`, { waitUntil: 'networkidle' });
    return page.evaluate(async () => {
      await document.fonts.ready;
      const logo = document.querySelector('.wsl-postal__logo img')?.getBoundingClientRect();
      const orderNumber = document.querySelector('.wsl-postal__footer .wsl-field-order_number');
      const orderStyle = orderNumber ? getComputedStyle(orderNumber) : null;
      return {
        logoWidth: logo?.width || 0,
        printDate: document.body.textContent.includes('تاریخ چاپ'),
        orderBorderLeft: orderStyle?.borderLeftWidth || '0px',
        orderBorderRight: orderStyle?.borderRightWidth || '0px',
      };
    });
  };
  const small = await measure(24);
  const large = await measure(52);
  if (large.logoWidth <= small.logoWidth + 40 || !large.printDate || parseFloat(large.orderBorderLeft) <= 0 || parseFloat(large.orderBorderRight) <= 0) {
    throw new Error(`Postal controls verification failed: ${JSON.stringify({ small, large })}`);
  }
  console.log(JSON.stringify({ postal: 'logo-and-footer-controls', small, large }));
  await page.close();
}
{
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  await page.goto('http://127.0.0.1:8792/tests/browser/fixture.php?count=2&template=postal&stress=1&font=16', { waitUntil: 'networkidle' });
  await page.waitForFunction(() => [...document.querySelectorAll('.wsl-label--postal')].every(label => label.dataset.wslFitted === 'true'));
  const result = await page.evaluate(async () => {
    await document.fonts.ready;
    const sections = [...document.querySelectorAll('.wsl-postal__recipient, .wsl-postal__sender, .wsl-postal__footer')];
    const fits = section => {
      const bounds = section.getBoundingClientRect();
      const childrenInside = [...section.querySelectorAll('*')].every(child => {
        const childBounds = child.getBoundingClientRect();
        return childBounds.right <= bounds.right + 1 && childBounds.bottom <= bounds.bottom + 1;
      });
      return section.scrollHeight <= section.clientHeight + 1 && section.scrollWidth <= section.clientWidth + 1 && childrenInside;
    };
    return {
      labels: document.querySelectorAll('.wsl-label--postal').length,
      fitted: [...document.querySelectorAll('.wsl-label--postal')].every(label => label.dataset.wslFitted === 'true'),
      overflowWarning: !!document.querySelector('.wsl-postal--overflow'),
      sectionsFit: sections.every(fits),
      fontSizes: [...document.querySelectorAll('.wsl-label--postal')].map(label => parseFloat(getComputedStyle(label).fontSize)),
    };
  });
  if (result.labels !== 2 || !result.fitted || result.overflowWarning || !result.sectionsFit || result.fontSizes.some(size => size < 8)) {
    throw new Error(`Postal content-fit verification failed: ${JSON.stringify(result)}`);
  }
  console.log(JSON.stringify({ postal: 'content-fit', result }));
  await page.close();
}
{
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  await page.goto('http://127.0.0.1:8792/tests/browser/fixture.php?count=2&template=postal&disabled=1', { waitUntil: 'networkidle' });
  const result = await page.evaluate(() => ({ postal: document.querySelectorAll('.wsl-label--postal').length, phone: document.body.textContent.includes('09121234567'), address: document.body.textContent.includes('خیابان آزادی'), logo: !!document.querySelector('.wsl-postal__logo'), barcode: !!document.querySelector('.wsl-postal__barcode') }));
  if (result.postal !== 2 || result.phone || result.address || result.logo || result.barcode) throw new Error(`Postal disabled-field verification failed: ${JSON.stringify(result)}`);
  console.log(JSON.stringify({ postal: 'disabled-fields', result }));
  await page.close();
}
for (const template of ['modern', 'classic']) {
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  await page.goto(`http://127.0.0.1:8792/tests/browser/fixture.php?document=invoice&template=${template}&items=3&invoices=1`, { waitUntil: 'networkidle' });
  await page.emulateMedia({ media: 'print' });
  const result = await page.evaluate(async () => {
    await document.fonts.ready;
    const image = document.querySelector('.wsl-invoice-item img')?.getBoundingClientRect();
    const toolbar = document.querySelector('.wsl-toolbar');
    const rules = [...document.styleSheets].flatMap(sheet => {
      try { return [...sheet.cssRules]; } catch { return []; }
    });
    const pageRules = rules.map(rule => rule.cssText.toLowerCase()).filter(css => css.includes('@page'));
    const pageRule = pageRules.some(css => css.includes('a4') && !css.includes('landscape'));
    return {
      documents: document.querySelectorAll('.wsl-invoice-document').length,
      fontLoaded: document.fonts.check('12px "WSL Vazirmatn"'),
      computedFont: getComputedStyle(document.querySelector('.wsl-invoice')).fontFamily,
      toolbarDisplay: toolbar ? getComputedStyle(toolbar).display : '',
      pageRule,
      pageRules,
      image: image ? { width: image.width, height: image.height } : null,
      rowBreak: getComputedStyle(document.querySelector('.wsl-invoice-item')).breakInside,
      theadDisplay: getComputedStyle(document.querySelector('.wsl-invoice__items thead')).display,
    };
  });
  const pdfOutput = new URL(`./invoice-${template}.pdf`, import.meta.url).pathname.replace(/^\/(.:)/, '$1');
  const screenshotOutput = new URL(`./invoice-${template}.png`, import.meta.url).pathname.replace(/^\/(.:)/, '$1');
  const pdf = await page.pdf({ path: pdfOutput, format: 'A4', printBackground: true, margin: { top: 0, right: 0, bottom: 0, left: 0 } });
  const pages = pdfPages(pdf);
  if (result.documents !== 1 || pages !== 1 || !result.fontLoaded || !result.computedFont.includes('WSL Vazirmatn') || result.toolbarDisplay !== 'none' || !result.pageRule || !result.image || result.image.width > 50 || result.image.height > 50 || result.rowBreak !== 'avoid' || result.theadDisplay !== 'table-header-group') {
    throw new Error(`Invoice ${template} verification failed: ${JSON.stringify({ result, pages })}`);
  }
  await page.emulateMedia({ media: 'screen' });
  await page.screenshot({ path: screenshotOutput, fullPage: true });
  console.log(JSON.stringify({ invoice: template, pages, result, pdfOutput, screenshotOutput }));
  await page.close();
}
{
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  await page.goto('http://127.0.0.1:8792/tests/browser/fixture.php?document=invoice&template=modern&items=35&invoices=1', { waitUntil: 'networkidle' });
  await page.emulateMedia({ media: 'print' });
  const pdf = await page.pdf({ format: 'A4', printBackground: true, margin: { top: 0, right: 0, bottom: 0, left: 0 } });
  const pages = pdfPages(pdf);
  const metrics = await page.evaluate(() => ({ rows: document.querySelectorAll('.wsl-invoice-item').length, rowBreaks: [...document.querySelectorAll('.wsl-invoice-item')].every(row => getComputedStyle(row).breakInside === 'avoid'), thead: getComputedStyle(document.querySelector('thead')).display }));
  if (pages < 2 || pages > 4 || metrics.rows !== 35 || !metrics.rowBreaks || metrics.thead !== 'table-header-group') throw new Error(`Long invoice verification failed: ${JSON.stringify({ pages, metrics })}`);
  console.log(JSON.stringify({ invoice: 'long', pages, metrics }));
  await page.close();
}
{
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  await page.goto('http://127.0.0.1:8792/tests/browser/fixture.php?document=invoice&template=modern&items=3&invoices=3', { waitUntil: 'networkidle' });
  await page.emulateMedia({ media: 'print' });
  const pdf = await page.pdf({ format: 'A4', printBackground: true, margin: { top: 0, right: 0, bottom: 0, left: 0 } });
  const pages = pdfPages(pdf);
  const result = await page.evaluate(() => ({ documents: document.querySelectorAll('.wsl-invoice-document').length, breaks: [...document.querySelectorAll('.wsl-invoice-document')].slice(1).map(el => getComputedStyle(el).breakBefore) }));
  if (pages !== 3 || result.documents !== 3 || result.breaks.some(value => value !== 'page')) throw new Error(`Grouped invoice verification failed: ${JSON.stringify({ pages, result })}`);
  console.log(JSON.stringify({ invoice: 'grouped', pages, result }));
  await page.close();
}
await browser.close();
