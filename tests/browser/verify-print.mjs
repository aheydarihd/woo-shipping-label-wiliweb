import playwright from 'file:///C:/Users/Aheyd/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/index.js';
const { chromium } = playwright;

const executablePath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const browser = await chromium.launch({ headless: true, executablePath });
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
await browser.close();
