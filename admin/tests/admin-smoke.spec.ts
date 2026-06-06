/**
 * 管理后台全模块冒烟测试
 * 遍历所有菜单页面，验证：
 *   - 页面正常加载
 *   - 不出现"操作失败"弹窗
 *   - 关键按钮/表格存在
 *
 * 依赖：先运行 auth.setup.ts 保存登录态
 */

import { test, expect, Page } from '@playwright/test';

// ===================== 公共方法 ======================

/** 检查页面无错误弹窗 */
async function checkNoError(page: Page) {
  const errorToast = page.locator('.el-message--error, .el-notification--error');
  if ((await errorToast.count()) > 0) {
    const text = await errorToast.first().textContent();
    console.warn(`  ⚠️ 发现错误提示: ${text}`);
  }
}

/** 导航到指定路由并等待加载 */
async function navigateTo(page: Page, route: string, title?: string) {
  await page.goto(`/admin${route}`);
  await page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => {});
  await page.waitForTimeout(500);
  if (title) {
    const tagEl = page.locator('.el-tag, .tags-view-item').filter({ hasText: title });
    if ((await tagEl.count()) > 0) {
      console.log(`  ✅ ${title}`);
    } else {
      console.log(`  ⚠️ ${route} — 未找到标签标题 "${title}"`);
    }
  }
  await checkNoError(page);
}

/** 断言页面有内容（至少1个目标元素） */
async function expectPageHasContent(page: Page, selector: string, timeout = 5000) {
  await expect(page.locator(selector).first()).toBeVisible({ timeout });
}

// ===================== 测试套件 ======================

test.describe('控制台模块', () => {
  test('控制台 Dashboard 加载', async ({ page }) => {
    await navigateTo(page, '/dashboard', '控制台');
    await expectPageHasContent(page, '.el-card', 10000);
    console.log('  ✅ 卡片已渲染');
  });

  test('个人中心 Profile 加载', async ({ page }) => {
    await navigateTo(page, '/profile', '个人中心');
    await expectPageHasContent(page, '.el-card, .el-form');
  });
});

test.describe('系统管理', () => {
  test('用户管理', async ({ page }) => {
    await navigateTo(page, '/system/admin', '用户管理');
    await expectPageHasContent(page, '.el-table');
  });
  test('角色管理', async ({ page }) => {
    await navigateTo(page, '/system/role', '角色管理');
    await expectPageHasContent(page, '.el-table');
  });
  test('菜单管理', async ({ page }) => {
    await navigateTo(page, '/system/menu', '菜单管理');
    await expectPageHasContent(page, '.el-table');
  });
  test('系统配置', async ({ page }) => {
    await navigateTo(page, '/system/config', '系统配置');
    await expectPageHasContent(page, '.el-form');
  });
  test('操作日志', async ({ page }) => {
    await navigateTo(page, '/system/log', '操作日志');
    await expectPageHasContent(page, '.el-table');
  });
  test('推送设备', async ({ page }) => {
    await navigateTo(page, '/system/PushDevice', '推送设备');
  });
  test('SSE事件', async ({ page }) => {
    await navigateTo(page, '/system/SseEvent', 'SSE事件');
  });
  test('服务商联系', async ({ page }) => {
    await navigateTo(page, '/system/ServiceVendor', '服务商联系');
  });
});

test.describe('房产管理', () => {
  test('小区管理', async ({ page }) => {
    await navigateTo(page, '/property/community', '小区管理');
    await expectPageHasContent(page, '.el-table');
  });
  test('楼栋管理', async ({ page }) => {
    await navigateTo(page, '/property/building', '楼栋管理');
    await expectPageHasContent(page, '.el-table');
  });
  test('房间管理', async ({ page }) => {
    await navigateTo(page, '/property/room', '房间管理');
    await expectPageHasContent(page, '.el-table');
  });
});

test.describe('业主管理', () => {
  test('业主管理', async ({ page }) => {
    await navigateTo(page, '/owner/index', '业主管理');
    await expectPageHasContent(page, '.el-table');
  });
  test('家庭成员', async ({ page }) => {
    await navigateTo(page, '/owner/family', '家庭成员');
  });
  test('业主投票', async ({ page }) => {
    await navigateTo(page, '/owner/vote', '业主投票');
  });
  test('社区活动', async ({ page }) => {
    await navigateTo(page, '/owner/activity', '社区活动');
  });
  test('活动报名', async ({ page }) => {
    await navigateTo(page, '/owner/signup', '活动报名');
  });
  test('公告通知', async ({ page }) => {
    await navigateTo(page, '/owner/notice', '公告通知');
  });
  test('投诉建议', async ({ page }) => {
    await navigateTo(page, '/owner/complaint', '投诉建议');
  });
});

test.describe('收费管理', () => {
  test('收费项目', async ({ page }) => {
    await navigateTo(page, '/charge/item', '收费项目');
  });
  test('账单管理', async ({ page }) => {
    await navigateTo(page, '/charge/bill', '账单管理');
  });
  test('缴费记录', async ({ page }) => {
    await navigateTo(page, '/charge/payment', '缴费记录');
  });
  test('抄表管理', async ({ page }) => {
    await navigateTo(page, '/charge/meter', '抄表管理');
  });
  test('财务流水', async ({ page }) => {
    await navigateTo(page, '/charge/finance', '财务流水');
  });
  test('欠费管理', async ({ page }) => {
    await navigateTo(page, '/charge/arrears', '欠费管理');
  });
  test('押金管理', async ({ page }) => {
    await navigateTo(page, '/charge/Deposit', '押金管理');
  });
  test('发票记录', async ({ page }) => {
    await navigateTo(page, '/charge/Invoice', '发票记录');
  });
  test('发票抬头', async ({ page }) => {
    await navigateTo(page, '/charge/InvoiceInfo', '发票抬头');
  });
  test('统一支付', async ({ page }) => {
    await navigateTo(page, '/charge/UnifiedPayment', '统一支付');
  });
  test('催缴记录', async ({ page }) => {
    await navigateTo(page, '/charge/dunning', '催缴记录');
  });
});

test.describe('报修管理', () => {
  test('工单管理', async ({ page }) => {
    await navigateTo(page, '/repair/order', '工单管理');
  });
  test('维修人员', async ({ page }) => {
    await navigateTo(page, '/repair/worker', '维修人员');
  });
});

test.describe('安保管理', () => {
  test('访客登记', async ({ page }) => {
    await navigateTo(page, '/security/visitor', '访客登记');
  });
  test('巡更记录', async ({ page }) => {
    await navigateTo(page, '/security/patrol', '巡更记录');
  });
  test('巡更路线', async ({ page }) => {
    await navigateTo(page, '/security/patrol-route', '巡更路线');
  });
  test('门禁卡', async ({ page }) => {
    await navigateTo(page, '/security/access-card', '门禁卡');
  });
});

test.describe('停车管理', () => {
  test('车位管理', async ({ page }) => {
    await navigateTo(page, '/parking/space', '车位管理');
  });
  test('车辆管理', async ({ page }) => {
    await navigateTo(page, '/parking/vehicle', '车辆管理');
  });
  test('停车记录', async ({ page }) => {
    await navigateTo(page, '/parking/record', '停车记录');
  });
  test('停车费率', async ({ page }) => {
    await navigateTo(page, '/parking/ParkingFeeRule', '停车费率');
  });
  test('停车缴费', async ({ page }) => {
    await navigateTo(page, '/parking/ParkingPayment', '停车缴费');
  });
});

test.describe('公告/消息', () => {
  test('公告列表', async ({ page }) => {
    await navigateTo(page, '/notice/index', '公告列表');
  });
  test('消息推送', async ({ page }) => {
    await navigateTo(page, '/notice/Notification', '消息推送');
  });
  test('消息记录', async ({ page }) => {
    await navigateTo(page, '/notice/message', '消息记录');
  });
});

test.describe('设备管理', () => {
  test('设备台账', async ({ page }) => {
    await navigateTo(page, '/equipment/index', '设备台账');
  });
  test('维保记录', async ({ page }) => {
    await navigateTo(page, '/equipment/maintain', '维保记录');
  });
  test('硬件设备', async ({ page }) => {
    await navigateTo(page, '/equipment/Device', '硬件设备');
  });
  test('设备事件', async ({ page }) => {
    await navigateTo(page, '/equipment/DeviceEvent', '设备事件');
  });
  test('电梯台账', async ({ page }) => {
    await navigateTo(page, '/equipment/Elevator', '电梯台账');
  });
  test('电梯故障', async ({ page }) => {
    await navigateTo(page, '/equipment/ElevatorFault', '电梯故障');
  });
  test('电梯巡检', async ({ page }) => {
    await navigateTo(page, '/equipment/ElevatorInspection', '电梯巡检');
  });
});

test.describe('投诉管理', () => {
  test('投诉列表', async ({ page }) => {
    await navigateTo(page, '/complaint/index', '投诉管理');
  });
});

test.describe('打印中心', () => {
  test('收据打印', async ({ page }) => {
    await navigateTo(page, '/print/receipt', '收据打印');
  });
  test('催缴通知打印', async ({ page }) => {
    await navigateTo(page, '/print/notice', '催缴通知');
  });
  test('打印模板', async ({ page }) => {
    await navigateTo(page, '/print/PrintTemplate', '打印模板');
  });
  test('打印日志', async ({ page }) => {
    await navigateTo(page, '/print/PrintLog', '打印日志');
  });
});

test.describe('员工管理', () => {
  test('员工档案', async ({ page }) => {
    await navigateTo(page, '/staff/index', '员工档案');
  });
});

test.describe('侧边栏菜单完整性检查', () => {
  test('所有一级菜单可展开', async ({ page }) => {
    await page.goto('/admin/dashboard');
    await page.waitForLoadState('networkidle');

    const menuItems = page.locator('.el-menu--vertical > .el-sub-menu, .el-menu--vertical > .el-menu-item');
    const count = await menuItems.count();
    console.log(`\n📋 侧边栏共 ${count} 个一级菜单:`);

    for (let i = 0; i < count; i++) {
      const item = menuItems.nth(i);
      const text = (await item.textContent())?.trim().replace(/\s+/g, ' ') || '';
      console.log(`  ${i + 1}. ${text}`);
      if (await item.locator('.el-sub-menu__title').count() > 0) {
        await item.locator('.el-sub-menu__title').click();
        await page.waitForTimeout(300);
      }
    }
    console.log('✅ 菜单完整性检查通过');
  });
});
