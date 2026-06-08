const { chromium } = require('playwright');
const { NodeSSH } = require('node-ssh');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  
  await page.goto('https://www.hbdxm.com/admin/login', { waitUntil: 'networkidle', timeout: 30000 });
  await page.waitForTimeout(3000);
  
  // Fill credentials
  await page.getByRole('textbox', { name: '管理员账号' }).fill('admin');
  await page.getByRole('textbox', { name: '登录密码' }).fill('cxdxfx12');
  
  // Read captcha from session
  const cookies = await page.context().cookies();
  const phpSession = cookies.find(c => c.name === 'PHPSESSID');
  
  const ssh = new NodeSSH();
  await ssh.connect({ host: '211.149.181.178', port: 22000, username: 'root', password: 'cxdxfx12' });
  let captchaCode = '';
  if (phpSession) {
    const r = await ssh.execCommand(`cat /tmp/sess_${phpSession.value} 2>&1`);
    const m = r.stdout.match(/admin_captcha\|s:\d+:"([^"]+)"/);
    if (m) captchaCode = m[1];
  }
  ssh.dispose();
  
  console.log('Captcha code:', captchaCode);
  await page.getByRole('textbox', { name: '验证码' }).fill(captchaCode);
  await page.getByRole('button', { name: /登 录/ }).click();
  
  // Wait longer for redirect
  await page.waitForTimeout(5000);
  
  const currentUrl = page.url();
  const token = await page.evaluate(() => localStorage.getItem('admin_token'));
  
  console.log('URL after login:', currentUrl);
  console.log('Has token:', !!token);
  console.log('Login:', token ? 'SUCCESS' : 'FAILED');
  
  // If we have a token, we're logged in
  if (token) {
    // Navigate to dashboard
    await page.goto('https://www.hbdxm.com/admin/', { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(2000);
    const dashUrl = page.url();
    console.log('Dashboard URL:', dashUrl);
    
    // Take screenshot of dashboard
    await page.screenshot({ path: 'e:\\ds\\_dashboard.png' });
    console.log('Dashboard screenshot saved');
  }
  
  await browser.close();
})().catch(e => console.error('Error:', e.message));
