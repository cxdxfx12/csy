# 项目关键信息

## 后台管理面板
- URL: http://127.0.0.1/admin/，用户名 admin，密码 cxdxfx12
- 数据库: host=127.0.0.1, user=root, password=cxdxfx12, database=dasheng, prefix=ds_
- 密码加密: md5(md5(密码) + auth_key)，auth_key=JUD6FCtZsqrmVXc2apev4TRn3O8gAhxbSlH9wfPN

## 构建部署
- Node.js: C:\Program Files\nodejs\ (v24.16.0)
- 构建命令: `cmd: set PATH=C:\Program Files\nodejs;%PATH% && cd /d e:\ds\admin && npm run build`
- ⚠️ nginx root = /www/wwwroot/www.hbdxm.com/public，前端文件必须放到 public/admin/assets/ 和 public/admin/index.html

## GitHub
- 仓库: https://github.com/cxdxfx12/csy.git
- 本机无法直连 github.com:443，但 ssh.github.com:443 和 api.github.com 可达
- SSH config 已配置 github.com 走 ssh.github.com:443
- SSH密钥 id_ed25519 已生成，公钥未添加到GitHub账号
- 当前远程URL为 HTTPS

## 服务器
- IP 211.149.181.178，SSH 端口 22000，用户名 root，密码 cxdxfx12
- 部署路径 /www/wwwroot/www.hbdxm.com/public/

## Nginx 缓存配置 (2026-06-08)
- /admin/index.html: no-cache, no-store, must-revalidate（确保用户获取最新版本）
- /admin/assets/* 的 JS/CSS: max-age=43200 (12h，带hash可安全缓存)
- 全局 JS/CSS 规则已排除 /admin/assets/ 避免覆盖
