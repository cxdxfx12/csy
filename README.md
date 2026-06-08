<p align="center">
  <img src="https://img.shields.io/badge/Vue-3.4-4FC08D?logo=vue.js" />
  <img src="https://img.shields.io/badge/TypeScript-5.0-3178C6?logo=typescript" />
  <img src="https://img.shields.io/badge/Vite-5.0-646CFF?logo=vite" />
  <img src="https://img.shields.io/badge/PHP-8.1-777BB4?logo=php" />
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql" />
  <img src="https://img.shields.io/badge/Three.js-0.160-000000?logo=three.js" />
  <img src="https://img.shields.io/badge/ECharts-5.x-AA344D?logo=apacheecharts" />
</p>

<h1 align="center">🐒 大圣智慧物业管理系统</h1>
<h3 align="center">Dasheng PMS · 杭州喵喵至家网络有限公司</h3>

<p align="center">
  <a href="https://www.hbdxm.com" target="_blank">🌐 官网</a> · 
  <a href="https://www.hbdxm.com/admin/" target="_blank">🖥 管理后台</a> · 
  <a href="https://www.hbdxm.com/3d-yanshi/" target="_blank">🏙 数字孪生</a> · 
  <a href="https://www.hbdxm.com/wg/" target="_blank">📊 产品官网</a>
</p>

---

## 📋 项目概述

大圣智慧物业管理系统是一套面向社区物业的全栈解决方案，覆盖 **PC管理后台 + 移动端业主/员工/经理 + 3D数字孪生大屏 + AI智能报修 + IoT设备管理** 六大场景，服务 500+ 社区、50 万+ 业主。

### ✨ 亮点功能

| 模块 | 说明 |
|------|------|
| 🏙 **3D 数字孪生** | 5 个真实小区建模（翡翠名苑/云栖别墅/中粮壹号/山水居/怡丰城），Three.js 渲染，IoT 设备数据实时叠加 |
| 🤖 **AI 报修助手** | 自然语言识别报修类型/位置/紧急程度，自动派单，支持业主端浮窗 + 3D 场景入口 |
| 📡 **IoT 设备管理** | 40+ 种设备类型、13 种通信协议、3D 坐标精准定位，管理端与数字孪生数据实时联动 |
| 🏢 **物业管理** | 小区/楼栋/房间三级房产管理，支持多小区数据隔离 |
| 💰 **收费管理** | 收费项目 → 账单生成 → 扫码支付（微信/支付宝）→ 抄表 → 欠费催缴 全闭环 |
| 🔧 **报修管理** | 业主报修 → AI 识别 → 自动派单 → 员工接单/完工 → 评价，端到端流程 |
| 🚗 **停车管理** | 车位、车辆、出入记录、费率配置、在线缴费 |
| 👤 **多端覆盖** | PC 后台 / 移动端 H5（业主+员工+经理）/ 独立 Vue 前端应用 / 3D 大屏 |
| 🔐 **安全机制** | JWT 认证、RBAC 权限、多小区隔离、IP 限流、密码 bcrypt、操作日志 |

---

## 🏗 技术架构

```
┌──────────────────────────────────────────────────────────────────┐
│                        前端层 (Vue 3.4)                           │
├──────────────┬──────────────────┬────────────────────────────────┤
│  PC 管理后台  │   独立前端应用     │        3D 数字孪生              │
│  admin/src/  │  frontend/src/   │   public/3d-yanshi/            │
│  Element Plus│  owner/staff/    │   Three.js + IoT 数据层         │
│  70+ 页面    │  manager 3入口   │   5 小区实时渲染                 │
└──────┬───────┴────────┬─────────┴─────────────┬──────────────────┘
       │                │                       │
       └────────────────┼───────────────────────┘
                        │ REST API (JSON)
       ┌────────────────▼────────────────────────┐
       │           PHP 后端 (自研轻量框架)          │
       │  admin/84 + api/15 + staff/11 +          │
       │  manager/4 + device/2 = 116 控制器        │
       │  JWT · RBAC · 中间件链 · 多小区隔离         │
       └────────────────┬────────────────────────┘
                        │
       ┌────────────────▼────────────────────────┐
       │        MySQL 8.0 · 40+ 张表 ds_ 前缀      │
       └─────────────────────────────────────────┘
```

| 层级 | 技术选型 |
|------|----------|
| **PC 管理面板** | Vue 3.4 + TypeScript 5 + Vite 5 + Element Plus + Pinia + ECharts 5 |
| **独立前端** | Vue 3.4 + Vite 多入口（owner/staff/manager） |
| **3D 数字孪生** | Three.js 0.160 (ES Module) + CSS2DRenderer + OrbitControls |
| **AI 助手** | 前端浮窗组件 + 后端 NLP 关键词匹配 + 自动派单 |
| **后端** | PHP 8.1+ · 自研轻量 MVC · firebase/php-jwt · topthink/think-orm |
| **数据库** | MySQL 8.0 · InnoDB · utf8mb4 |
| **Web 服务器** | Nginx + PHP-FPM |
| **部署** | SSH2 自动化 · GitHub Actions 就绪 |

---

## 🚀 快速开始

### 环境要求

- PHP >= 8.1
- MySQL >= 8.0
- Node.js >= 18 (推荐 v24)
- Nginx / Apache
- Composer

### 本地开发

```bash
# 1. 后端
cd e:\ds
php -S 127.0.0.1:8000 public/index.php

# 2. 管理后台 (开发模式)
cd e:\ds\admin
npm install
npm run dev          # → http://localhost:3000/admin/

# 3. 独立前端 (开发模式)
cd e:\ds\frontend
npm install
npm run dev

# 4. 生产构建
cd e:\ds\admin
npm run build        # → ../public/admin/
```

### 访问入口

| 端 | 本地 | 线上 |
|----|------|------|
| 🖥 管理后台 | `http://127.0.0.1/admin/` | [hbdxm.com/admin/](https://www.hbdxm.com/admin/) |
| 🏠 业主端 | `http://127.0.0.1/owner.html` | [hbdxm.com/owner.html](https://www.hbdxm.com/owner.html) |
| 👷 员工端 | `http://127.0.0.1/staff.html` | [hbdxm.com/staff.html](https://www.hbdxm.com/staff.html) |
| 🏙 数字孪生 | `http://127.0.0.1/3d-yanshi/` | [hbdxm.com/3d-yanshi/](https://www.hbdxm.com/3d-yanshi/) |
| 📊 产品官网 | `http://127.0.0.1/wg/` | [hbdxm.com/wg/](https://www.hbdxm.com/wg/) |

---

## 📊 功能全景

### 🖥 PC 管理后台（70+ 页面）

```
系统管理    房产管理    业主管理      收费管理         报修管理
 用户管理    小区管理    业主档案      收费项目         工单管理
 角色管理    楼栋管理    家庭成员      账单生成         维修人员
 菜单管理    房间管理    投票活动      缴费记录         派单接单
 系统配置               公告通知      抄表管理         完工评价
 操作日志               投诉建议      财务流水
                                      欠费催缴
安保管理    停车管理    设备管理      物业人员         更多模块
 访客登记    车位管理    设备台账      员工档案         供应商
 巡更路线    车辆管理    维保记录      考勤排班         采购合同
 门禁卡      停车记录    电梯管理      工资管理         租赁管理
            费率缴费    硬件接入                       短信平台
                                                       装修管理
新功能↓↓   IoT 设备      AI 助手       3D 联动         打印中心
           设备CRUD     关键词配置    坐标编辑 ⬌ 3D    收据打印
           类型协议      对话记录      数据5秒同步       催缴通知
           实时数据      统计图表
```

### 🏙 3D 数字孪生大屏

| 场景 | 建筑数 | 特色 |
|------|--------|------|
| 🏢 翡翠名苑 | 14 栋 26-33 层 | 中央花园 · 凉亭 · 篮球场 · 儿童区 · 会所 |
| 🏡 云栖别墅 | 48 栋独栋 | 红瓦屋顶 · 中央泳池 · 喷泉 |
| 🏬 中粮壹号 | 6 栋 42-55 层 | 玻璃幕墙超高层 · 天线 · 几何喷泉广场 |
| 🌿 山水居 | 12 栋花园洋房 | 叠水瀑布 · 石桥 · 中式凉亭 · 竹林 |
| 🏘 怡丰城 | 9 栋 8-12 层 | 真实布局建模 · 双入口 · 沿街商铺 |

**交互**：拖拽旋转缩放 · 键盘快捷键跳转视角 · 5 小区一键切换 · IoT 设备发光球体 · 实时数据轮询

### 🤖 AI 智能报修助手

```
用户输入                      AI 处理                      结果
──────────────────────────────────────────────────────────────
"厨房水龙头漏水"    →  类型:水电 + 位置:厨房    →  生成报修单
"客厅空调不制冷"    →  类型:空调 + 位置:客厅    →  自动派单
"电梯困人了快来人"  →  类型:电梯 + 紧急标记 🔴  →  置顶优先处理
```

- 9 种报修类型智能识别
- 13 个紧急关键词自动标记
- 从对话自动生成完整工单
- 管理端可配置关键词库
- 接入 3D 场景右下角浮窗 & 业主端全局浮窗

### 📡 IoT 设备管理

| 层级 | 说明 |
|------|------|
| **设备类型** (40+) | 烟感、温感、燃气、门禁、水电燃气表、摄像头、温湿度、PM2.5、电梯、消防栓、路灯、地磁车位 |
| **通信协议** (13) | MQTT、CoAP、HTTP、Modbus、BACnet、Zigbee、LoRaWAN、BLE、NB-IoT、OPC UA、Wi-Fi、RS485、4G |
| **3D 坐标** | 每个设备含 x/y/z 三维坐标，管理端可编辑，数字孪生实时定位 |
| **数据联动** | 管理端增删改 → 3D 场景 5 秒自动刷新 → 颜色变化（绿/黄/红/灰） |

### 📱 移动端

**业主端** (15 页)：登录 · 首页 · 账单缴费 · 报修 · 投诉 · 访客预约 · 车辆 · 投票 · 活动 · AI助手  
**员工端** (10 页)：登录 · 首页 · 接单维修 · 抄表 · 收费 · 巡更 · 访客登记 · 工单  
**经理端** (2 页)：登录 · 数据驾驶舱

---

## 🛡 安全机制

| 层级 | 方案 |
|------|------|
| 传输 | HTTPS |
| 认证 | JWT HS256 · 24h 过期 · type 跨端校验 |
| 密码 | bcrypt (cost=12) |
| 频率 | IP 限流 (5分钟 ≤10次) |
| 权限 | RBAC · 7 种预定义角色 · 控制器级拦截 |
| 隔离 | 多小区数据过滤 · 小区级别权限校验 |
| CORS | 动态 Origin 白名单 |
| 日志 | 操作日志 · 密码脱敏 · 请求截断 |

---

## 📂 关键目录

```
e:\ds\
├── admin/          PC 管理后台 (Vue3 + Element Plus)
├── frontend/       独立前端 (owner/staff/manager)
├── app/            PHP 后端 (admin/api/staff/manager/device)
├── config/         配置文件 (database/jwt/cors/middleware)
├── route/          路由表 (admin/api/staff/manager/device)
├── middleware/     中间件 (Auth/CORS/Log/Device)
├── public/         Web 根目录 + 3D 数字孪生 + 官网
│   ├── 3d-yanshi/  数字孪生大屏 (Three.js)
│   └── wg/         产品官网
├── database/       数据库脚本
├── extend/         扩展服务 (微信SDK等)
└── DEVELOPMENT.md  完整开发者手册
```

---

## 🔗 外部链接

| 资源 | 地址 |
|------|------|
| 🌐 官网 | [www.hbdxm.com](https://www.hbdxm.com) |
| 📧 邮箱 | mmzj@miaomiaozj.com |
| 📞 电话 | 17771300068 / 19171045360 |
| 📍 地址 | 杭州临平区怡丰城 12 幢 |
| 🐙 GitHub | [github.com/cxdxfx12/csy](https://github.com/cxdxfx12/csy) |

---

## 📄 许可证

© 2026 **杭州喵喵至家网络有限公司** 版权所有

> 🐒 **大圣智慧物业** — 让物业管理更简单、更智能
