/**
 * 登录认证脚本
 * 首次运行时需要手动输入验证码，后续运行自动使用已保存的登录态
 *
 * 用法：
 *   方式一（推荐）：npx playwright test --project=setup --headed
 *     浏览器打开登录页，手动输入验证码后自动完成登录并保存状态
 *
 *   方式二（无头）：设置环境变量绕过验证码
 *     set CAPTCHA_TEXT=abcd && npx playwright test --project=setup
 */

import { test as setup, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const AUTH_FILE = path.join(__dirname, '.auth', 'admin.json');

// 确保 .auth 目录存在
const authDir = path.dirname(AUTH_FILE);
if (!fs.existsSync(authDir)) {
  fs.mkdirSync(authDir, { recursive: true });
}

// 测试账号
const TEST_USERS = {
  admin: { username: 'admin', password: 'cxdxfx12', community: '阳光花园' },
  finance: { username: 'yangguang_caiwu', password: 'cxdxfx12', community: '阳光花园' },
};

// 选择要测试的角色
const USER = TEST_USERS.admin;

setup('管理员登录并保存状态', async ({ page, request }) => {
  // ============ 第一步：尝试通过 API 登录（可能失败于验证码） ============
  let token: string | null = null;

  // 先尝试获取验证码 key
  const captchaRes = await request.get('/api/admin/captcha');
  const captchaData = await captchaRes.json();
  console.log('📋 验证码 key:', captchaData?.data?.key || 'N/A');

  // 如果有环境变量设置了验证码文本，直接尝试 API 登录
  const captchaText = process.env.CAPTCHA_TEXT;
  if (captchaText && captchaData?.data?.key) {
    const loginRes = await request.post('/api/admin/login', {
      data: {
        username: USER.username,
        password: USER.password,
        community: USER.community,
        captcha: captchaText,
        captchaKey: captchaData.data.key,
      },
    });
    const loginData = await loginRes.json();
    if (loginData.code === 0 && loginData.data?.token) {
      token = loginData.data.token;
      console.log('✅ API 登录成功');

      // 保存 token 到 storage state
      await page.goto('/admin/login');
      await page.evaluate((t) => {
        localStorage.setItem('admin_token', t);
        localStorage.setItem('admin_user', JSON.stringify({ username: 'admin', role_name: '超级管理员' }));
      }, token);

      await page.context().storageState({ path: AUTH_FILE });
      console.log('💾 登录态已保存');
      return;
    }
    console.log('⚠️ API 登录失败:', loginData.msg);
  }

  // ============ 第二步：浏览器手动登录（heeded 模式可见） ============
  console.log('\n🔐 打开浏览器登录页，请手动输入验证码...');
  await page.goto('/admin/login');

  // 等待登录表单加载 — 表单出现即可
  await page.waitForSelector('text=欢迎回来', { timeout: 15000 });

  // 自动填写用户名密码
  const accountInput = page.locator('input').nth(0); // 第一个 input = 账号
  const pwdInput = page.getByPlaceholder('登录密码');
  if (await accountInput.count() > 0) {
    await accountInput.fill(USER.username);
  }
  if (await pwdInput.count() > 0) {
    await pwdInput.fill(USER.password);
  }
  // 如果有小区选择，也选一下
  const communityTrigger = page.locator('.el-select__wrapper, .el-select .el-select__caret').first();
  if (await communityTrigger.count() > 0) {
    await communityTrigger.click();
    await page.waitForTimeout(500);
    const options = page.locator('.el-select-dropdown__item');
    const target = options.filter({ hasText: USER.community });
    if (await target.count() > 0) {
      await target.first().click();
    }
  }
  console.log('📝 已自动填写账号密码，请在浏览器中输入验证码后点击登录');

  // 等待用户手动输入验证码并登录（最多等 120 秒）
  console.log('⏳ 等待登录...（最多 120 秒）');

  // 等待登录成功 — 页面跳转到 /admin/dashboard
  await page.waitForURL('**/admin/dashboard**', { timeout: 120_000 });
  console.log('✅ 登录成功！');

  // 保存登录态
  await page.context().storageState({ path: AUTH_FILE });
  console.log('💾 登录态已保存到', AUTH_FILE);
});
