import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  timeout: 60_000,
  expect: { timeout: 10_000 },
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: [
    ['html', { outputFolder: 'test-results/report' }],
    ['list'],
  ],
  outputDir: 'test-results/artifacts',

  use: {
    // 默认测试线上环境，可通过环境变量切换
    baseURL: process.env.TEST_URL || 'https://www.hbdxm.com',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },

  projects: [
    // 登录认证项目 — 首次运行需要手动输入验证码
    {
      name: 'setup',
      testMatch: /auth\.setup\.ts/,
    },
    // 核心测试项目 — 依赖已保存的登录态
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        storageState: 'tests/.auth/admin.json',
      },
      dependencies: ['setup'],
    },
  ],
});
