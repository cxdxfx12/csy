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
  
  // Get captcha key from the page's fetch call
  const captchaData = await page.evaluate(async () => {
    const res = await fetch('/api/admin/captcha');
    return await res.json();
  });
  const captchaKey = captchaData.data.key;
  console.log('Captcha key:', captchaKey);
  
  // Get the PHPSESSID
  const cookies = await page.context().cookies();
  const phpSession = cookies.find(c => c.name === 'PHPSESSID');
  console.log('Session:', phpSession?.value);
  
  // Read captcha code from server session
  const ssh = new NodeSSH();
  await ssh.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
  
  let captchaCode = '';
  if (phpSession) {
    const sessResult = await ssh.execCommand(`cat /tmp/sess_${phpSession.value} 2>&1`);
    console.log('Session:', sessResult.stdout);
    // Parse: admin_captcha|s:4:"WM8S";
    const match = sessResult.stdout.match(/admin_captcha\|s:\d+:"([^"]+)"/);
    if (match) captchaCode = match[1];
  }
  console.log('Captcha code:', captchaCode);
  
  ssh.dispose();
  
  if (!captchaCode) {
    console.log('Could not read captcha code');
    await browser.close();
    return;
  }
  
  // Fill the captcha code
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
  console.log('Token in localStorage:', token ? token.substring(0, 50) + '...' : 'none');
  
  await page.screenshot({ path: 'e:\\ds\\_login_result.png' });
  
  await browser.close();
})().catch(e => console.error('Error:', e.message));
