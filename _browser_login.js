// Use playwright to automate login and capture the result
const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  
  // Collect console messages
  const consoleMsgs = [];
  page.on('console', msg => consoleMsgs.push(`${msg.type()}: ${msg.text()}`));
  
  // Collect network failures
  const failedReqs = [];
  page.on('requestfailed', req => failedReqs.push(`${req.url()} - ${req.failure().errorText}`));
  
  // Collect API responses
  const apiResponses = [];
  page.on('response', async res => {
    const url = res.url();
    if (url.includes('/api/')) {
      try {
        const body = await res.text();
        apiResponses.push({ url, status: res.status(), body: body.substring(0, 300) });
      } catch(e) {}
    }
  });
  
  // Navigate to login page
  console.log('1. Loading login page...');
  await page.goto('https://www.hbdxm.com/admin/login', { waitUntil: 'networkidle', timeout: 30000 });
  console.log('   Page loaded');
  
  // Wait for captcha to load
  await page.waitForTimeout(3000);
  
  // Take screenshot
  await page.screenshot({ path: 'e:\\ds\\_login_step1.png' });
  console.log('   Screenshot saved');
  
  // Fill in credentials
  console.log('2. Filling credentials...');
  await page.getByRole('textbox', { name: '管理员账号' }).fill('admin');
  await page.getByRole('textbox', { name: '登录密码' }).fill('cxdxfx12');
  
  // Get captcha image src to verify it loaded
  const captchaImg = await page.$('.captcha-img');
  const captchaSrc = captchaImg ? await captchaImg.getAttribute('src') : 'NOT FOUND';
  console.log('   Captcha img src length:', captchaSrc?.length || 0);
  console.log('   Captcha img starts with:', captchaSrc?.substring(0, 50) || 'N/A');
  
  // Fill captcha with a test value
  await page.getByRole('textbox', { name: '验证码' }).fill('test');
  
  // Click login
  console.log('3. Clicking login...');
  await page.getByRole('button', { name: /登 录/ }).click();
  
  // Wait for response
  await page.waitForTimeout(3000);
  
  // Check for error message
  const errorMsg = await page.$('.error-msg');
  const errorText = errorMsg ? await errorMsg.textContent() : 'no error msg element';
  console.log('   Error message:', errorText);
  
  // Take screenshot after login attempt
  await page.screenshot({ path: 'e:\\ds\\_login_step2.png' });
  
  // Print API responses
  console.log('\n=== API Responses ===');
  for (const r of apiResponses) {
    console.log(`${r.url} [${r.status}]: ${r.body}`);
  }
  
  // Print console messages
  if (consoleMsgs.length) {
    console.log('\n=== Console Messages ===');
    consoleMsgs.forEach(m => console.log(m));
  }
  
  // Print failed requests
  if (failedReqs.length) {
    console.log('\n=== Failed Requests ===');
    failedReqs.forEach(r => console.log(r));
  }
  
  await browser.close();
})().catch(e => console.error('Error:', e.message));
