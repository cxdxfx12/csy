# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: auth.setup.ts >> 管理员登录并保存状态
- Location: tests\auth.setup.ts:37:1

# Error details

```
Error: apiRequestContext.get: read ECONNRESET
Call log:
  - → GET https://www.hbdxm.com/api/admin/captcha
    - user-agent: Playwright/1.60.0 (x64; windows 10.0) node/24.16
    - accept: */*
    - accept-encoding: gzip,deflate,br

```

# Test source

```ts
  1   | /**
  2   |  * 登录认证脚本
  3   |  * 首次运行时需要手动输入验证码，后续运行自动使用已保存的登录态
  4   |  *
  5   |  * 用法：
  6   |  *   方式一（推荐）：npx playwright test --project=setup --headed
  7   |  *     浏览器打开登录页，手动输入验证码后自动完成登录并保存状态
  8   |  *
  9   |  *   方式二（无头）：设置环境变量绕过验证码
  10  |  *     set CAPTCHA_TEXT=abcd && npx playwright test --project=setup
  11  |  */
  12  | 
  13  | import { test as setup, expect } from '@playwright/test';
  14  | import * as fs from 'fs';
  15  | import * as path from 'path';
  16  | import { fileURLToPath } from 'url';
  17  | 
  18  | const __filename = fileURLToPath(import.meta.url);
  19  | const __dirname = path.dirname(__filename);
  20  | const AUTH_FILE = path.join(__dirname, '.auth', 'admin.json');
  21  | 
  22  | // 确保 .auth 目录存在
  23  | const authDir = path.dirname(AUTH_FILE);
  24  | if (!fs.existsSync(authDir)) {
  25  |   fs.mkdirSync(authDir, { recursive: true });
  26  | }
  27  | 
  28  | // 测试账号
  29  | const TEST_USERS = {
  30  |   admin: { username: 'admin', password: 'cxdxfx12', community: '阳光花园' },
  31  |   finance: { username: 'yangguang_caiwu', password: 'cxdxfx12', community: '阳光花园' },
  32  | };
  33  | 
  34  | // 选择要测试的角色
  35  | const USER = TEST_USERS.admin;
  36  | 
  37  | setup('管理员登录并保存状态', async ({ page, request }) => {
  38  |   // ============ 第一步：尝试通过 API 登录（可能失败于验证码） ============
  39  |   let token: string | null = null;
  40  | 
  41  |   // 先尝试获取验证码 key
> 42  |   const captchaRes = await request.get('/api/admin/captcha');
      |                                    ^ Error: apiRequestContext.get: read ECONNRESET
  43  |   const captchaData = await captchaRes.json();
  44  |   console.log('📋 验证码 key:', captchaData?.data?.key || 'N/A');
  45  | 
  46  |   // 如果有环境变量设置了验证码文本，直接尝试 API 登录
  47  |   const captchaText = process.env.CAPTCHA_TEXT;
  48  |   if (captchaText && captchaData?.data?.key) {
  49  |     const loginRes = await request.post('/api/admin/login', {
  50  |       data: {
  51  |         username: USER.username,
  52  |         password: USER.password,
  53  |         community: USER.community,
  54  |         captcha: captchaText,
  55  |         captchaKey: captchaData.data.key,
  56  |       },
  57  |     });
  58  |     const loginData = await loginRes.json();
  59  |     if (loginData.code === 0 && loginData.data?.token) {
  60  |       token = loginData.data.token;
  61  |       console.log('✅ API 登录成功');
  62  | 
  63  |       // 保存 token 到 storage state
  64  |       await page.goto('/admin/login');
  65  |       await page.evaluate((t) => {
  66  |         localStorage.setItem('admin_token', t);
  67  |         localStorage.setItem('admin_user', JSON.stringify({ username: 'admin', role_name: '超级管理员' }));
  68  |       }, token);
  69  | 
  70  |       await page.context().storageState({ path: AUTH_FILE });
  71  |       console.log('💾 登录态已保存');
  72  |       return;
  73  |     }
  74  |     console.log('⚠️ API 登录失败:', loginData.msg);
  75  |   }
  76  | 
  77  |   // ============ 第二步：浏览器手动登录（heeded 模式可见） ============
  78  |   console.log('\n🔐 打开浏览器登录页，请手动输入验证码...');
  79  |   await page.goto('/admin/login');
  80  | 
  81  |   // 等待登录表单加载 — 表单出现即可
  82  |   await page.waitForSelector('text=欢迎回来', { timeout: 15000 });
  83  | 
  84  |   // 自动填写用户名密码
  85  |   const accountInput = page.locator('input').nth(0); // 第一个 input = 账号
  86  |   const pwdInput = page.getByPlaceholder('登录密码');
  87  |   if (await accountInput.count() > 0) {
  88  |     await accountInput.fill(USER.username);
  89  |   }
  90  |   if (await pwdInput.count() > 0) {
  91  |     await pwdInput.fill(USER.password);
  92  |   }
  93  |   // 如果有小区选择，也选一下
  94  |   const communityTrigger = page.locator('.el-select__wrapper, .el-select .el-select__caret').first();
  95  |   if (await communityTrigger.count() > 0) {
  96  |     await communityTrigger.click();
  97  |     await page.waitForTimeout(500);
  98  |     const options = page.locator('.el-select-dropdown__item');
  99  |     const target = options.filter({ hasText: USER.community });
  100 |     if (await target.count() > 0) {
  101 |       await target.first().click();
  102 |     }
  103 |   }
  104 |   console.log('📝 已自动填写账号密码，请在浏览器中输入验证码后点击登录');
  105 | 
  106 |   // 等待用户手动输入验证码并登录（最多等 120 秒）
  107 |   console.log('⏳ 等待登录...（最多 120 秒）');
  108 | 
  109 |   // 等待登录成功 — 页面跳转到 /admin/dashboard
  110 |   await page.waitForURL('**/admin/dashboard**', { timeout: 120_000 });
  111 |   console.log('✅ 登录成功！');
  112 | 
  113 |   // 保存登录态
  114 |   await page.context().storageState({ path: AUTH_FILE });
  115 |   console.log('💾 登录态已保存到', AUTH_FILE);
  116 | });
  117 | 
```