# 项目关键信息

## 后台管理面板
- URL: http://127.0.0.1/admin/，用户名 admin，密码 cxdxfx12
- 数据库: host=127.0.0.1, user=root, password=cxdxfx12, database=dasheng, prefix=ds_
- 密码加密: md5(md5(密码) + auth_key)，auth_key=JUD6FCtZsqrmVXc2apev4TRn3O8gAhxbSlH9wfPN

## 构建部署
- Node.js: C:\Program Files\nodejs\ (v24.16.0)
- 构建命令: `cmd: set PATH=C:\Program Files\nodejs;%PATH% && cd /d e:\ds\admin && npm run build`
- ⚠️ nginx root = /www/wwwroot/www.hbdxm.com/public，前端文件必须放到 public/admin/assets/ 和 public/admin/index.html
- ⚠️ **每次构建后必须同步全部 assets/ 文件**！只上传 index.html + index-*.js/css 不够，因为 JS chunk 间有动态 import 依赖，缺文件会白屏
- SSH 部署专用参数：readyTimeout=10000，SFTP 比 shell exec 更稳定，批量写用 createWriteStream

## GitHub
- 仓库: https://github.com/cxdxfx12/csy.git
- 本机无法直连 github.com:443，但 ssh.github.com:443 和 api.github.com 可达
- SSH config 已配置 github.com 走 ssh.github.com:443
- SSH密钥 id_ed25519 已生成，公钥已添加到GitHub账号
- Push 可用 git@github.com SSH 方式
- ⚠️ 当前运行用户是 ccc（非 Administrator），SSH密钥在 C:\Users\ccc\.ssh\

## 服务器
- IP 211.149.181.178，SSH 端口 22000，用户名 root，密码 cxdxfx12
- nginx root: /www/wwwroot/www.hbdxm.com/public/（= /home/wwwroot/www.hbdxm.com/public/，/www → /home 软链接）
- **PHP 应用根目录**: /www/wwwroot/www.hbdxm.com/（app/、route/、extend/、config/ 等在根目录，不是在 public/ 下！）
- 前端文件: 部署到 /www/wwwroot/www.hbdxm.com/public/admin/
- PHP 文件: 部署到 /www/wwwroot/www.hbdxm.com/app/... 等对应的应用根目录
- ⚠️ public/app/ 是废弃副本，框架不会加载这里的代码！务必部署到根目录的 app/

## Nginx 缓存配置 (2026-06-08)
- /admin/index.html: no-cache, no-store, must-revalidate（确保用户获取最新版本）
- /admin/assets/* 的 JS/CSS: max-age=43200 (12h，带hash可安全缓存)
- 全局 JS/CSS 规则已排除 /admin/assets/ 避免覆盖

## 手机管理端图标系统
- 图标库：@iconify/vue + Phosphor Icons（`ph:` 前缀）
- 图标映射文件：`admin/src/utils/mobileIcons.ts` — 定义 100+ 图标常量(`ICONS`对象) + `getMenuIcon(name)`智能匹配 + `getMenuColor(name)`颜色映射
- 使用方式：`<Icon icon="ph:gauge-fill" />` 或 `import { ICONS } from '@/utils/mobileIcons'`
- 所有移动端页面已使用 Phosphor Icons 替代 Emoji

## 手机管理端重构 (2026-06-10)
- 全部 6 个页面用 Vue + Phosphor Icons 完全重写
- 设计语言：深色渐变 + 毛玻璃效果 + 圆角卡片 + Transition 动画
- 构建前需清除缓存：`rmdir /s /q e:\ds\admin\node_modules\.vite`
- `_mobile_view` 机制：手机端点菜单 → sessionStorage 标记 → MainLayout 切手机壳 → PC页面在480px容器内渲染

## 维修工登录 (2026-06-11)
- repair_worker 表也支持员工端登录，字段：phone/name/password（明文或bcrypt）
- StaffLogin::login() 先查 admin_user，未找到则依次按 phone → name → staff.job_no 查找 repair_worker
- verify_password() 兼容 bcrypt、旧版 md5(md5+salt)、明文三种格式
- ThinkPHP 6 闭包 WHERE(`where(function($q){$q->where(...)->whereOr(...);})`) 行为不可靠，OR 条件改用分步查询
