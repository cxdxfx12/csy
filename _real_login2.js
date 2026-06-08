const { chromium } = require('playwright');
const { NodeSSH } = require('node-ssh');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  
  // Navigate to login page
  await page.goto('https://www.hbdxm.com/admin/login', { waitUntil: 'networkidle', timeout: 30000 });
  await page.waitForTimeout(3000);
  
  // Fill credentials
  await page.getByRole('textbox', { name: '管理员账号' }).fill('admin');
  await page.getByRole('textbox', { name: '登录密码' }).fill('cxdxfx12');
  
  // Wait for the page's auto-fetched captcha to load
  // The page's fetchCaptcha() runs on mount, so captcha is already loaded
  await page.waitForTimeout(1000);
  
  // Get the PHPSESSID cookie (set by the page's captcha fetch)
  const cookies = await page.context().cookies();
  const phpSession = cookies.find(c => c.name === 'PHPSESSID');
  console.log('Session:', phpSession?.value);
  
  // Read captcha code from server session
  const ssh = new NodeSSH();
  await ssh.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
  
  let captchaCode = '';
  if (phpSession) {
    const sessResult = await ssh.execCommand(`cat /tmp/sess_${phpSession.value} 2>&1`);
    console.log('Session content:', sessResult.stdout);
    const match = sessResult.stdout.match(/admin_captcha\|s:\d+:"([^"]+)"/);
    if (match) captchaCode = match[1];
  }
  console.log('Captcha code from session:', captchaCode);
  ssh.dispose();
  
  if (!captchaCode) {
    console.log('Could not read captcha code');
    await browser.close();
    return;
  }
  
  // Fill the captcha code from the page's existing session
  await page.getByRole('textbox', { name: '验证码' }).fill(captchaCode);
  
  // Click login
  console.log('Clicking login...');
  await page.getByRole('button', { name: /登 录/ }).click();
  await page.waitForTimeout(3000);
  
  // Check result
  const currentUrl = page.url();
  console.log('Current URL after login:', currentUrl);
  
  // Check for error
  const errorMsg = await page.$('.error-msg');
  if (errorMsg) {
    const errorText = await errorMsg.textContent();
    console.log('Error message:', errorText);
  }
  
  // Check if we got redirected (login success)
  if (currentUrl.includes('/admin') && !currentUrl.includes('login')) {
    console.log('LOGIN SUCCESS! Redirected to:', currentUrl);
  }
  
  // Check localStorage for token
  const token = await page.evaluate(() => localStorage.getItem('admin_token'));
  console.log('Token:', token ? token.substring(0, 80) + '...' : 'none');
  
  await browser.close();
})().catch(e => console.error('Error:', e.message));
