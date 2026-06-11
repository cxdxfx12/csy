const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

  const pages = ['index', 'product_full', 'about_full', 'pricing_full', 'features_full', 'scenes_full', 'solutions_full', 'contact_full'];

  for (const name of pages) {
    const url = name === 'index'
      ? 'http://www.hbdxm.com/readdy-local/index.html'
      : `http://www.hbdxm.com/readdy-local/${name}.html`;

    console.log(`Loading: ${url}`);
    const resp = await page.goto(url, { waitUntil: 'networkidle', timeout: 15000 });

    // Check which images failed
    const failedImgs = await page.evaluate(() => {
      const imgs = document.querySelectorAll('img');
      const failed = [];
      imgs.forEach((img, i) => {
        if (img.naturalWidth === 0 && img.naturalHeight === 0) {
          failed.push({
            index: i,
            src: img.src.substring(0, 120),
            width: img.clientWidth,
            height: img.clientHeight,
            class: img.className
          });
        }
      });
      return failed;
    });

    if (failedImgs.length > 0) {
      console.log(`  FAILED IMAGES (${failedImgs.length}):`);
      failedImgs.forEach(f => {
        console.log(`    [${f.width}x${f.height}] ${f.src}`);
      });
    } else {
      console.log('  All images loaded OK');
    }

    // Screenshot
    await page.screenshot({ path: path.join(__dirname, `_debug_${name}.png`), fullPage: false });
  }

  await browser.close();
  console.log('\nScreenshots saved as _debug_*.png');
})();
