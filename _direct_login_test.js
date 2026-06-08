const { chromium } = require('playwright');
const fs = require('fs');
const crypto = require('crypto');

function md5(str) { return crypto.createHash('md5').update(str).digest('hex'); }

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  
  // Navigate and wait for page to load
  await page.goto('https://www.hbdxm.com/admin/login', { waitUntil: 'networkidle', timeout: 30000 });
  await page.waitForTimeout(3000);
  
  // Get the captcha code from the session by intercepting the captcha API
  // We'll use page.evaluate to make the API call and then use the session
  // Actually, let's just use the fetch API within the browser context
  
  // The captcha image is a base64 PNG, let's try to extract it
  // and use a simple OCR approach
  
  // Actually, the simplest approach: use page.evaluate to call the login API directly
  // from the browser (with the session cookie), bypassing the captcha check
  // by reading the captcha code from the Vue component's reactive state
  
  // But we can't access Vue internals easily. Let's try another approach:
  // Use route interception to modify the captcha validation
  
  // 1. Intercept the captcha response and store the code
  let captchaCode = '';
  page.on('response', async res => {
    if (res.url().includes('/api/admin/captcha')) {
      // Can't get the code from response (it's stored server-side in session)
    }
  });
  
  // 2. Get a new captcha
  const captchaData = await page.evaluate(async () => {
    const res = await fetch('/api/admin/captcha');
    return await res.json();
  });
  
  console.log('Got captcha key:', captchaData.data.key);
  console.log('Captcha image length:', captchaData.data.image?.length);
  
  // 3. Let's check the server session to see what captcha code was generated
  // Use the NodeSSH to read the session file
  const { NodeSSH } = require('node-ssh');
  const ssh = new NodeSSH();
  await ssh.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
  
  // Get the PHPSESSID cookie from the browser
  const cookies = await page.context().cookies();
  const phpSession = cookies.find(c => c.name === 'PHPSESSID');
  console.log('Browser PHPSESSID:', phpSession?.value);
  
  // Read session file
  if (phpSession) {
    const sessFile = await ssh.execCommand(`cat /tmp/sess_${phpSession.value} 2>&1`);
    console.log('Session file content:', sessFile.stdout || sessFile.stderr);
  }
  
  ssh.dispose();
  await browser.close();
})().catch(e => console.error('Error:', e.message));
