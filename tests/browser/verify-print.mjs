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
